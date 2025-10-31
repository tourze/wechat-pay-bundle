<?php

namespace WechatPayBundle\DataFixtures;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\TradeBill;
use WechatPayBundle\Enum\BillType;

/**
 * 交易账单数据填充
 * 创建测试用的交易账单数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class TradeBillFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const ALL_TRADE_BILL_REFERENCE = 'all-trade-bill';
    public const SUCCESS_TRADE_BILL_REFERENCE = 'success-trade-bill';
    public const REFUND_TRADE_BILL_REFERENCE = 'refund-trade-bill';

    public static function getGroups(): array
    {
        return ['test', 'dev'];
    }

    public function getDependencies(): array
    {
        return [
            MerchantFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $testMerchant = $this->getReference(MerchantFixtures::TEST_MERCHANT_REFERENCE, Merchant::class);

        // 创建全部交易账单
        $allTradeBill = new TradeBill();
        $allTradeBill->setMerchant($testMerchant);
        $allTradeBill->setBillDate(CarbonImmutable::yesterday());
        $allTradeBill->setBillType(BillType::ALL);
        $allTradeBill->setHashType('SHA1');
        $allTradeBill->setHashValue('1a2b3c4d5e6f7890abcdef1234567890abcdef12');
        $allTradeBill->setDownloadUrl('https://api.mch.weixin.qq.com/v3/bill/tradebill?bill_date=2024-01-15&bill_type=ALL');
        $allTradeBill->setLocalFile('/var/bills/all_trade_bill_' . date('Ymd') . '.csv');
        $manager->persist($allTradeBill);
        $this->addReference(self::ALL_TRADE_BILL_REFERENCE, $allTradeBill);

        // 创建成功支付账单
        $successTradeBill = new TradeBill();
        $successTradeBill->setMerchant($testMerchant);
        $successTradeBill->setBillDate(CarbonImmutable::yesterday());
        $successTradeBill->setBillType(BillType::SUCCESS);
        $successTradeBill->setHashType('SHA1');
        $successTradeBill->setHashValue('abcdef1234567890abcdef1234567890abcdef12');
        $successTradeBill->setDownloadUrl('https://api.mch.weixin.qq.com/v3/bill/tradebill?bill_date=2024-01-15&bill_type=SUCCESS');
        $successTradeBill->setLocalFile('/var/bills/success_trade_bill_' . date('Ymd') . '.csv');
        $manager->persist($successTradeBill);
        $this->addReference(self::SUCCESS_TRADE_BILL_REFERENCE, $successTradeBill);

        // 创建退款账单
        $refundTradeBill = new TradeBill();
        $refundTradeBill->setMerchant($testMerchant);
        $refundTradeBill->setBillDate(CarbonImmutable::yesterday());
        $refundTradeBill->setBillType(BillType::REFUND);
        $refundTradeBill->setHashType('SHA1');
        $refundTradeBill->setHashValue('567890abcdef1234567890abcdef1234567890ab');
        $refundTradeBill->setDownloadUrl('https://api.mch.weixin.qq.com/v3/bill/tradebill?bill_date=2024-01-15&bill_type=REFUND');
        $refundTradeBill->setLocalFile('/var/bills/refund_trade_bill_' . date('Ymd') . '.csv');
        $manager->persist($refundTradeBill);
        $this->addReference(self::REFUND_TRADE_BILL_REFERENCE, $refundTradeBill);

        $manager->flush();
    }
}
