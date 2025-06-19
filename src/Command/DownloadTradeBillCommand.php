<?php

namespace WechatPayBundle\Command;

use Carbon\Carbon;
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
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\TradeBill;
use WechatPayBundle\Enum\BillType;
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
#[AsCronTask('0 10 * * *')]
#[AsCronTask('0 11 * * *')]
#[AsCommand(name: 'wechat:pay:download-trade-bill', description: '交易账单下载')]
class DownloadTradeBillCommand extends Command
{
    public const NAME = 'download-trade-bill';

    public function __construct(
        private readonly MerchantRepository $merchantRepository,
        private readonly WechatPayBuilder $payBuilder,
        private readonly TradeBillRepository $tradeBillRepository,
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
        foreach (BillType::cases() as $billType) {
            $tradeBill = $this->tradeBillRepository->findOneBy([
                'merchant' => $merchant,
                'billDate' => $date,
                'billType' => $billType,
            ]);
            // 有保存过，跳过
            if ($tradeBill) {
                continue;
            }

            $response = $builder->chain('v3/bill/tradebill')->get([
                'query' => [
                    'bill_date' => $date->format('Y-m-d'),
                    'bill_type' => $billType->value,
                ],
            ]);
            $response = $response->getBody()->getContents();
            $response = Json::decode($response);

            $tradeBill = new TradeBill();
            $tradeBill->setMerchant($merchant);
            $tradeBill->setBillDate($date);
            $tradeBill->setBillType($billType);

            $tradeBill->setHashType($response['hash_type']);
            $tradeBill->setHashValue($response['hash_value']);
            $tradeBill->setDownloadUrl($response['download_url']);

            // 【下载地址】 供下一步请求账单文件的下载地址，该地址5min内有效。
            $billData = $this->httpClient->request('GET', $tradeBill->getDownloadUrl())->getContent();
            $key = $this->mountManager->saveContent($billData, 'txt');
            $tradeBill->setLocalFile($key);

            $this->entityManager->persist($tradeBill);
            $this->entityManager->flush();
        }
    }
}
