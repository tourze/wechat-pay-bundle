<?php

namespace WechatPayBundle\Command;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Doctrine\ORM\EntityManagerInterface;
use HttpClientBundle\Service\SmartHttpClient;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;
use WechatPayBundle\Entity\FundFlowBill;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Enum\AccountType;
use WechatPayBundle\Repository\FundFlowBillRepository;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * 账单下载
 *
 * @see https://pay.weixin.qq.com/docs/merchant/apis/mini-program-payment/get-fund-bill.html
 */
#[AsCronTask('0 10 * * *')]
#[AsCronTask('0 11 * * *')]
#[AsCommand(name: self::NAME, description: '资金账单下载')]
class DownloadFundFlowBillCommand extends Command
{
    public const NAME = 'wechat:pay:download-fund-flow-bill';

    public function __construct(
        private readonly MerchantRepository $merchantRepository,
        private readonly WechatPayBuilder $payBuilder,
        private readonly FundFlowBillRepository $fundFlowBillRepository,
        private readonly FilesystemOperator $mountManager,
        private readonly SmartHttpClient $httpClient,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 总是拉最近一周的数据
        $startDate = Carbon::yesterday()->subWeek();
        $endDate = Carbon::yesterday()->startOfDay();
        $dateList = CarbonPeriod::between($startDate, $endDate)->toArray();

        foreach ($dateList as $date) {
            $date = $date->startOfDay();

            foreach ($this->merchantRepository->findBy(['valid' => true]) as $merchant) {
                $this->syncItem($date, $merchant);
            }
        }

        return Command::SUCCESS;
    }

    private function syncItem(CarbonInterface $date, Merchant $merchant): void
    {
        $builder = $this->payBuilder->genBuilder($merchant);
        foreach (AccountType::cases() as $accountType) {
            $fundFlowBill = $this->fundFlowBillRepository->findOneBy([
                'merchant' => $merchant,
                'billDate' => $date,
                'accountType' => $accountType,
            ]);
            // 有保存过，跳过
            if ($fundFlowBill) {
                continue;
            }

            $response = $builder->chain('v3/bill/fundflowbill')->get([
                'query' => [
                    'bill_date' => $date->format('Y-m-d'),
                    'account_type' => $accountType->value,
                ],
            ]);
            $response = $response->getBody()->getContents();
            $response = Json::decode($response);

            $fundFlowBill = new FundFlowBill();
            $fundFlowBill->setMerchant($merchant);
            $fundFlowBill->setBillDate($date);
            $fundFlowBill->setAccountType($accountType);

            $fundFlowBill->setHashType($response['hash_type']);
            $fundFlowBill->setHashValue($response['hash_value']);
            $fundFlowBill->setDownloadUrl($response['download_url']);

            // 【下载地址】 供下一步请求账单文件的下载地址，该地址5min内有效。
            $billData = $this->httpClient->request('GET', $fundFlowBill->getDownloadUrl())->getContent();
            $key = $this->mountManager->saveContent($billData, 'txt');
            $fundFlowBill->setLocalFile($key);

            $this->entityManager->persist($fundFlowBill);
            $this->entityManager->flush();
        }
    }
}
