<?php

namespace WechatPayBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use WechatPayBundle\Entity\RefundGoodsDetail;
use WechatPayBundle\Entity\RefundOrder;

/**
 * 退款商品明细数据填充
 * 创建测试用的退款商品明细数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class RefundGoodsDetailFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const PHONE_GOODS_DETAIL_REFERENCE = 'phone-goods-detail';
    public const CASE_GOODS_DETAIL_REFERENCE = 'case-goods-detail';
    public const CHARGER_GOODS_DETAIL_REFERENCE = 'charger-goods-detail';

    public static function getGroups(): array
    {
        return ['test', 'dev'];
    }

    public function getDependencies(): array
    {
        return [
            RefundOrderFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $partialRefundOrder = $this->getReference(RefundOrderFixtures::PARTIAL_REFUND_REFERENCE, RefundOrder::class);
        $fullRefundOrder = $this->getReference(RefundOrderFixtures::FULL_REFUND_REFERENCE, RefundOrder::class);

        // 创建手机商品退款明细
        $phoneDetail = new RefundGoodsDetail();
        $phoneDetail->setRefundOrder($partialRefundOrder);
        $phoneDetail->setMerchantGoodsId('PHONE_001');
        $phoneDetail->setWechatpayGoodsId('WX_PHONE_001');
        $phoneDetail->setGoodsName('iPhone 15 Pro 256GB');
        $phoneDetail->setUnitPrice(899900);
        $phoneDetail->setRefundAmount(30000);
        $phoneDetail->setRefundQuantity(1);
        $manager->persist($phoneDetail);
        $this->addReference(self::PHONE_GOODS_DETAIL_REFERENCE, $phoneDetail);

        // 创建手机壳商品退款明细
        $caseDetail = new RefundGoodsDetail();
        $caseDetail->setRefundOrder($fullRefundOrder);
        $caseDetail->setMerchantGoodsId('CASE_001');
        $caseDetail->setWechatpayGoodsId('WX_CASE_001');
        $caseDetail->setGoodsName('透明硅胶手机壳');
        $caseDetail->setUnitPrice(2900);
        $caseDetail->setRefundAmount(2900);
        $caseDetail->setRefundQuantity(1);
        $manager->persist($caseDetail);
        $this->addReference(self::CASE_GOODS_DETAIL_REFERENCE, $caseDetail);

        // 创建充电器商品退款明细
        $chargerDetail = new RefundGoodsDetail();
        $chargerDetail->setRefundOrder($fullRefundOrder);
        $chargerDetail->setMerchantGoodsId('CHARGER_001');
        $chargerDetail->setWechatpayGoodsId('WX_CHARGER_001');
        $chargerDetail->setGoodsName('20W USB-C 快充充电器');
        $chargerDetail->setUnitPrice(14900);
        $chargerDetail->setRefundAmount(14900);
        $chargerDetail->setRefundQuantity(1);
        $manager->persist($chargerDetail);
        $this->addReference(self::CHARGER_GOODS_DETAIL_REFERENCE, $chargerDetail);

        $manager->flush();
    }
}
