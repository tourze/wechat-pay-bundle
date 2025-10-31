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
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\TradeBill;
use WechatPayBundle\Enum\BillType;
use WechatPayBundle\Exception\PaymentParameterException;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Repository\TradeBillRepository;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * 账单下载
 *
 * @see https://pay.weixin.qq.com/docs/merchant/apis/mini-program-payment/get-trade-bill.html
 * @see https://pay.weixin.qq.com/docs/merchant/products/bill-download/format-trade.html
 */
#[AsCronTask(expression: '0 10 * * *')]
#[AsCronTask(expression: '0 11 * * *')]
#[AsCommand(name: self::NAME, description: '交易账单下载')]
#[WithMonologChannel(channel: 'wechat_pay')]
class DownloadTradeBillCommand extends Command
{
    public const NAME = 'wechat:pay:download-trade-bill';

    public function __construct(
        private readonly MerchantRepository $merchantRepository,
        private readonly WechatPayBuilder $payBuilder,
        private readonly TradeBillRepository $tradeBillRepository,
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
        foreach (BillType::cases() as $billType) {
            if ($this->billExists($date, $merchant, $billType)) {
                continue;
            }

            $this->downloadAndSaveBill($date, $merchant, $billType, $builder);
        }
    }

    private function billExists(CarbonInterface $date, Merchant $merchant, BillType $billType): bool
    {
        $tradeBill = $this->tradeBillRepository->findOneBy([
            'merchant' => $merchant,
            'billDate' => $date,
            'billType' => $billType,
        ]);

        return null !== $tradeBill;
    }

    /**
     * @param BuilderChainable $builder
     */
    private function downloadAndSaveBill(CarbonInterface $date, Merchant $merchant, BillType $billType, $builder): void
    {
        $startTime = microtime(true);
        try {
            $this->logger->info('开始下载交易账单', [
                'merchant_id' => $merchant->getId(),
                'bill_date' => $date->format('Y-m-d'),
                'bill_type' => $billType->value,
            ]);

            $responseData = $this->fetchBillMetadata($date, $billType, $builder);
            $tradeBill = $this->createTradeBill($date, $merchant, $billType, $responseData);
            $billData = $this->downloadBillFile($tradeBill);

            $this->logSuccess($merchant, $date, $billType, $billData, $startTime);
            $this->saveBillToStorage($tradeBill, $date, $billType, $billData);

            $this->entityManager->persist($tradeBill);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logError($merchant, $date, $billType, $e, $startTime);
        }
    }

    /**
     * @param BuilderChainable $builder
     * @return array<string, mixed>
     */
    private function fetchBillMetadata(CarbonInterface $date, BillType $billType, $builder): array
    {
        $chainable = $builder->chain('v3/bill/tradebill');
        $response = $chainable->get([
            'query' => [
                'bill_date' => $date->format('Y-m-d'),
                'bill_type' => $billType->value,
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
    private function createTradeBill(CarbonInterface $date, Merchant $merchant, BillType $billType, array $responseData): TradeBill
    {
        $tradeBill = new TradeBill();
        $tradeBill->setMerchant($merchant);
        $tradeBill->setBillDate($date);
        $tradeBill->setBillType($billType);

        $hashType = \is_string($responseData['hash_type'] ?? null) ? $responseData['hash_type'] : '';
        $tradeBill->setHashType($hashType);

        $hashValue = isset($responseData['hash_value']) && \is_string($responseData['hash_value']) ? $responseData['hash_value'] : null;
        $tradeBill->setHashValue($hashValue);

        $downloadUrl = \is_string($responseData['download_url'] ?? null) ? $responseData['download_url'] : '';
        $tradeBill->setDownloadUrl($downloadUrl);

        return $tradeBill;
    }

    private function downloadBillFile(TradeBill $tradeBill): string
    {
        $this->logger->info('开始下载交易账单文件', [
            'download_url' => $tradeBill->getDownloadUrl(),
        ]);

        $downloadUrl = $tradeBill->getDownloadUrl();
        if (null === $downloadUrl) {
            throw new PaymentParameterException('Download URL is null');
        }

        return $this->httpClient->request('GET', $downloadUrl)->getContent();
    }

    private function logSuccess(Merchant $merchant, CarbonInterface $date, BillType $billType, string $billData, float $startTime): void
    {
        $endTime = microtime(true);
        $this->logger->info('交易账单下载成功', [
            'merchant_id' => $merchant->getId(),
            'bill_date' => $date->format('Y-m-d'),
            'bill_type' => $billType->value,
            'file_size' => strlen($billData),
            'duration_ms' => round(($endTime - $startTime) * 1000, 2),
        ]);
    }

    private function saveBillToStorage(TradeBill $tradeBill, CarbonInterface $date, BillType $billType, string $billData): void
    {
        $filename = 'trade_bill_' . $date->format('Y-m-d') . '_' . $billType->value . '_' . uniqid() . '.txt';
        $stream = fopen('php://memory', 'r+');
        if (false === $stream) {
            throw new PaymentParameterException('Failed to create memory stream');
        }
        fwrite($stream, $billData);
        rewind($stream);
        $this->mountManager->writeStream($filename, $stream);
        fclose($stream);

        $tradeBill->setLocalFile($filename);
    }

    private function logError(Merchant $merchant, CarbonInterface $date, BillType $billType, \Throwable $e, float $startTime): void
    {
        $endTime = microtime(true);
        $this->logger->error('交易账单下载失败', [
            'merchant_id' => $merchant->getId(),
            'bill_date' => $date->format('Y-m-d'),
            'bill_type' => $billType->value,
            'exception' => $e->getMessage(),
            'duration_ms' => round(($endTime - $startTime) * 1000, 2),
        ]);
    }
}
