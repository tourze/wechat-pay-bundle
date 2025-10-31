<?php

namespace WechatPayBundle\DataFixtures;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Entity\RefundOrder;

/**
 * 微信退款订单数据填充
 * 创建测试用的退款订单数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class RefundOrderFixtures extends Fixture implements FixtureGroupInterface
{
    public const PARTIAL_REFUND_REFERENCE = 'partial-refund-order';
    public const FULL_REFUND_REFERENCE = 'full-refund-order';
    public const SUCCESS_REFUND_REFERENCE = 'success-refund-order';

    public static function getGroups(): array
    {
        return ['test', 'dev'];
    }

    public function load(ObjectManager $manager): void
    {
        // 创建部分退款订单 (不依赖 PayOrder，简化测试)
        $partialRefund = new RefundOrder();
        $partialRefund->setAppId('wx5555666677778888');
        $partialRefund->setReason('用户申请部分退款');
        $partialRefund->setNotifyUrl('https://test.localhost/wechat/refund/notify');
        $partialRefund->setMoney(300);
        $partialRefund->setStatus('PROCESSING');
        $partialRefund->setRequestJson('{"refund_amount":300,"reason":"部分退款"}');
        $manager->persist($partialRefund);
        $this->addReference(self::PARTIAL_REFUND_REFERENCE, $partialRefund);

        // 创建全额退款订单
        $fullRefund = new RefundOrder();
        $fullRefund->setAppId('wx5555666677778888');
        $fullRefund->setReason('商品质量问题全额退款');
        $fullRefund->setNotifyUrl('https://test.localhost/wechat/refund/notify');
        $fullRefund->setMoney(1000);
        $fullRefund->setStatus('PENDING');
        $fullRefund->setRequestJson('{"refund_amount":1000,"reason":"全额退款"}');
        $manager->persist($fullRefund);
        $this->addReference(self::FULL_REFUND_REFERENCE, $fullRefund);

        // 创建成功退款订单
        $successRefund = new RefundOrder();
        $successRefund->setAppId('wx5555666677778888');
        $successRefund->setReason('已确认退款');
        $successRefund->setNotifyUrl('https://test.localhost/wechat/refund/notify');
        $successRefund->setMoney(500);
        $successRefund->setStatus('SUCCESS');
        $successRefund->setRefundId('50000123456789012345678901234567890');
        $successRefund->setRefundChannel('ORIGINAL');
        $successRefund->setUserReceiveAccount('招商银行信用卡0403');
        $successRefund->setSuccessTime(CarbonImmutable::now()->subMinutes(30));
        $successRefund->setRequestJson('{"refund_amount":500,"reason":"确认退款"}');
        $successRefund->setResponseJson('{"refund_id":"50000123456789012345678901234567890","status":"SUCCESS"}');
        $successRefund->setCallbackResponse('{"return_code":"SUCCESS","refund_status":"SUCCESS"}');
        $manager->persist($successRefund);
        $this->addReference(self::SUCCESS_REFUND_REFERENCE, $successRefund);

        $manager->flush();
    }
}
