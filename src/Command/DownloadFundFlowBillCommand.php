<?php

namespace WechatPayBundle\Command;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;
use WeChatPay\BuilderChainable;
use WechatPayBundle\Entity\FundFlowBill;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Enum\AccountType;
use WechatPayBundle\Exception\PaymentParameterException;
use WechatPayBundle\Repository\FundFlowBillRepository;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * 账单下载
 *
 * @see https://pay.weixin.qq.com/docs/merchant/apis/mini-program-payment/get-fund-bill.html
 */
#[AsCronTask(expression: '0 10 * * *')]
#[AsCronTask(expression: '0 11 * * *')]
#[AsCommand(name: self::NAME, description: '资金账单下载')]
#[WithMonologChannel(channel: 'wechat_pay')]
class DownloadFundFlowBillCommand extends Command
{
    public const NAME = 'wechat:pay:download-fund-flow-bill';

    public function __construct(
        private readonly MerchantRepository $merchantRepository,
        private readonly WechatPayBuilder $payBuilder,
        private readonly FundFlowBillRepository $fundFlowBillRepository,
        private readonly FilesystemOperator $mountManager,
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 总是拉最近一周的数据
        $startDate = CarbonImmutable::yesterday()->subWeek();
        $endDate = CarbonImmutable::yesterday()->startOfDay();
        $dateList = CarbonPeriod::between($startDate, $endDate)->toArray();

        foreach ($dateList as $date) {
            $date = $date->startOfDay();

            $merchants = $this->merchantRepository->findBy(['valid' => true]);
            foreach ($merchants as $merchant) {
                $this->syncItem($date, $merchant);
            }
        }

        return Command::SUCCESS;
    }

    private function syncItem(CarbonInterface $date, Merchant $merchant): void
    {
        $builder = $this->payBuilder->genBuilder($merchant);
        foreach (AccountType::cases() as $accountType) {
            if ($this->billExists($date, $merchant, $accountType)) {
                continue;
            }

            $this->downloadAndSaveBill($date, $merchant, $accountType, $builder);
        }
    }

    private function billExists(CarbonInterface $date, Merchant $merchant, AccountType $accountType): bool
    {
        $fundFlowBill = $this->fundFlowBillRepository->findOneBy([
            'merchant' => $merchant,
            'billDate' => $date,
            'accountType' => $accountType,
        ]);

        return null !== $fundFlowBill;
    }

    /**
     * @param BuilderChainable $builder
     */
    private function downloadAndSaveBill(CarbonInterface $date, Merchant $merchant, AccountType $accountType, $builder): void
    {
        $startTime = microtime(true);
        try {
            $this->logger->info('开始下载资金账单', [
                'merchant_id' => $merchant->getId(),
                'bill_date' => $date->format('Y-m-d'),
                'account_type' => $accountType->value,
            ]);

            $responseData = $this->fetchBillMetadata($date, $accountType, $builder);
            $fundFlowBill = $this->createFundFlowBill($date, $merchant, $accountType, $responseData);
            $billData = $this->downloadBillFile($fundFlowBill);

            $this->logSuccess($merchant, $date, $accountType, $billData, $startTime);
            $this->saveBillToStorage($fundFlowBill, $date, $accountType, $billData);

            $this->entityManager->persist($fundFlowBill);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logError($merchant, $date, $accountType, $e, $startTime);
        }
    }

    /**
     * @param BuilderChainable $builder
     * @return array<string, mixed>
     */
    private function fetchBillMetadata(CarbonInterface $date, AccountType $accountType, $builder): array
    {
        $chainable = $builder->chain('v3/bill/fundflowbill');
        $response = $chainable->get([
            'query' => [
                'bill_date' => $date->format('Y-m-d'),
                'account_type' => $accountType->value,
            ],
        ]);
        $body = $response->getBody();
        $contents = $body->getContents();

        /** @var array<string, mixed> */
        return Json::decode($contents);
    }

    /**
     * @param array<string, mixed> $responseData
     */
    private function createFundFlowBill(CarbonInterface $date, Merchant $merchant, AccountType $accountType, array $responseData): FundFlowBill
    {
        $fundFlowBill = new FundFlowBill();
        $fundFlowBill->setMerchant($merchant);
        $fundFlowBill->setBillDate($date);
        $fundFlowBill->setAccountType($accountType);

        $hashType = \is_string($responseData['hash_type'] ?? null) ? $responseData['hash_type'] : '';
        $fundFlowBill->setHashType($hashType);

        $hashValue = isset($responseData['hash_value']) && \is_string($responseData['hash_value']) ? $responseData['hash_value'] : null;
        $fundFlowBill->setHashValue($hashValue);

        $downloadUrl = \is_string($responseData['download_url'] ?? null) ? $responseData['download_url'] : '';
        $fundFlowBill->setDownloadUrl($downloadUrl);

        return $fundFlowBill;
    }

    private function downloadBillFile(FundFlowBill $fundFlowBill): string
    {
        $this->logger->info('开始下载账单文件', [
            'download_url' => $fundFlowBill->getDownloadUrl(),
        ]);

        $downloadUrl = $fundFlowBill->getDownloadUrl();
        if (null === $downloadUrl) {
            throw new PaymentParameterException('Download URL is null');
        }

        return $this->httpClient->request('GET', $downloadUrl)->getContent();
    }

    private function logSuccess(Merchant $merchant, CarbonInterface $date, AccountType $accountType, string $billData, float $startTime): void
    {
        $endTime = microtime(true);
        $this->logger->info('资金账单下载成功', [
            'merchant_id' => $merchant->getId(),
            'bill_date' => $date->format('Y-m-d'),
            'account_type' => $accountType->value,
            'file_size' => strlen($billData),
            'duration_ms' => round(($endTime - $startTime) * 1000, 2),
        ]);
    }

    private function saveBillToStorage(FundFlowBill $fundFlowBill, CarbonInterface $date, AccountType $accountType, string $billData): void
    {
        $filename = 'bill_' . $date->format('Y-m-d') . '_' . $accountType->value . '_' . uniqid() . '.txt';
        $stream = fopen('php://memory', 'r+');
        if (false === $stream) {
            throw new PaymentParameterException('Failed to create memory stream');
        }
        fwrite($stream, $billData);
        rewind($stream);
        $this->mountManager->writeStream($filename, $stream);
        fclose($stream);

        $fundFlowBill->setLocalFile($filename);
    }

    private function logError(Merchant $merchant, CarbonInterface $date, AccountType $accountType, \Throwable $e, float $startTime): void
    {
        $endTime = microtime(true);
        $this->logger->error('资金账单下载失败', [
            'merchant_id' => $merchant->getId(),
            'bill_date' => $date->format('Y-m-d'),
            'account_type' => $accountType->value,
            'exception' => $e->getMessage(),
            'duration_ms' => round(($endTime - $startTime) * 1000, 2),
        ]);
    }
}
