<?php

namespace WechatPayBundle\DataFixtures;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use WechatPayBundle\Entity\FundFlowBill;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Enum\AccountType;

/**
 * 资金流水账单数据填充
 * 创建测试用的资金流水账单数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class FundFlowBillFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const BASIC_FUND_BILL_REFERENCE = 'basic-fund-bill';
    public const OPERATION_FUND_BILL_REFERENCE = 'operation-fund-bill';
    public const FEES_FUND_BILL_REFERENCE = 'fees-fund-bill';

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

        // 创建基本账户资金流水账单
        $basicBill = new FundFlowBill();
        $basicBill->setMerchant($testMerchant);
        $basicBill->setBillDate(CarbonImmutable::yesterday());
        $basicBill->setAccountType(AccountType::BASIC);
        $basicBill->setHashType('SHA1');
        $basicBill->setHashValue('a1b2c3d4e5f6789012345678901234567890abcd');
        $basicBill->setDownloadUrl('https://api.mch.weixin.qq.com/v3/bill/fundflowbill?bill_date=2024-01-15&account_type=Basic');
        $basicBill->setLocalFile('/var/bills/basic_fund_flow_' . date('Ymd') . '.csv');
        $manager->persist($basicBill);
        $this->addReference(self::BASIC_FUND_BILL_REFERENCE, $basicBill);

        // 创建运营账户资金流水账单
        $operationBill = new FundFlowBill();
        $operationBill->setMerchant($testMerchant);
        $operationBill->setBillDate(CarbonImmutable::yesterday());
        $operationBill->setAccountType(AccountType::OPERATION);
        $operationBill->setHashType('SHA1');
        $operationBill->setHashValue('f6e5d4c3b2a1098765432109876543210fedcba9');
        $operationBill->setDownloadUrl('https://api.mch.weixin.qq.com/v3/bill/fundflowbill?bill_date=2024-01-15&account_type=Operation');
        $operationBill->setLocalFile('/var/bills/operation_fund_flow_' . date('Ymd') . '.csv');
        $manager->persist($operationBill);
        $this->addReference(self::OPERATION_FUND_BILL_REFERENCE, $operationBill);

        // 创建手续费账户资金流水账单
        $feesBill = new FundFlowBill();
        $feesBill->setMerchant($testMerchant);
        $feesBill->setBillDate(CarbonImmutable::yesterday());
        $feesBill->setAccountType(AccountType::FEES);
        $feesBill->setHashType('SHA1');
        $feesBill->setHashValue('9876543210abcdef9876543210abcdef98765432');
        $feesBill->setDownloadUrl('https://api.mch.weixin.qq.com/v3/bill/fundflowbill?bill_date=2024-01-15&account_type=Fees');
        $feesBill->setLocalFile('/var/bills/fees_fund_flow_' . date('Ymd') . '.csv');
        $manager->persist($feesBill);
        $this->addReference(self::FEES_FUND_BILL_REFERENCE, $feesBill);

        $manager->flush();
    }
}
