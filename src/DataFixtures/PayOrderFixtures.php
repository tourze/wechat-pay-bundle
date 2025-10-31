<?php

namespace WechatPayBundle\DataFixtures;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;

/**
 * 微信支付订单数据填充
 * 创建测试用的支付订单数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class PayOrderFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const JSAPI_ORDER_REFERENCE = 'jsapi-pay-order';
    public const APP_ORDER_REFERENCE = 'app-pay-order';
    public const NATIVE_ORDER_REFERENCE = 'native-pay-order';
    public const SUCCESS_ORDER_REFERENCE = 'success-pay-order';

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

        // 创建JSAPI支付订单
        $jsapiOrder = new PayOrder();
        $jsapiOrder->setMerchant($testMerchant);
        $jsapiOrder->setAppId('wx1234567890abcdef');
        $jsapiOrder->setMchId('1234567890');
        $jsapiOrder->setTradeType('JSAPI');
        $jsapiOrder->setTradeNo('TEST_JSAPI_' . time() . '_001');
        $jsapiOrder->setBody('测试商品-JSAPI支付');
        $jsapiOrder->setTotalFee(100);
        $jsapiOrder->setNotifyUrl('https://test.localhost/wechat/notify');
        $jsapiOrder->setOpenId('oUpF8uMuAJO_M2pxb1Q9zNjWeS6o');
        $jsapiOrder->setDescription('JSAPI支付测试订单');
        $jsapiOrder->setStatus(PayOrderStatus::INIT);
        $manager->persist($jsapiOrder);
        $this->addReference(self::JSAPI_ORDER_REFERENCE, $jsapiOrder);

        // 创建APP支付订单
        $appOrder = new PayOrder();
        $appOrder->setMerchant($testMerchant);
        $appOrder->setAppId('wx0987654321fedcba');
        $appOrder->setMchId('1234567890');
        $appOrder->setTradeType('APP');
        $appOrder->setTradeNo('TEST_APP_' . time() . '_002');
        $appOrder->setBody('测试商品-APP支付');
        $appOrder->setTotalFee(299);
        $appOrder->setNotifyUrl('https://test.localhost/wechat/notify');
        $appOrder->setDescription('APP支付测试订单');
        $appOrder->setStatus(PayOrderStatus::PAYING);
        $manager->persist($appOrder);
        $this->addReference(self::APP_ORDER_REFERENCE, $appOrder);

        // 创建Native支付订单
        $nativeOrder = new PayOrder();
        $nativeOrder->setMerchant($testMerchant);
        $nativeOrder->setAppId('wx1111222233334444');
        $nativeOrder->setMchId('1234567890');
        $nativeOrder->setTradeType('NATIVE');
        $nativeOrder->setTradeNo('TEST_NATIVE_' . time() . '_003');
        $nativeOrder->setBody('测试商品-Native支付');
        $nativeOrder->setTotalFee(500);
        $nativeOrder->setNotifyUrl('https://test.localhost/wechat/notify');
        $nativeOrder->setDescription('Native支付测试订单');
        $nativeOrder->setStatus(PayOrderStatus::CLOSED);
        $manager->persist($nativeOrder);
        $this->addReference(self::NATIVE_ORDER_REFERENCE, $nativeOrder);

        // 创建成功支付订单
        $successOrder = new PayOrder();
        $successOrder->setMerchant($testMerchant);
        $successOrder->setAppId('wx5555666677778888');
        $successOrder->setMchId('1234567890');
        $successOrder->setTradeType('JSAPI');
        $successOrder->setTradeNo('TEST_SUCCESS_' . time() . '_004');
        $successOrder->setBody('测试商品-成功支付');
        $successOrder->setTotalFee(1000);
        $successOrder->setNotifyUrl('https://test.localhost/wechat/notify');
        $successOrder->setOpenId('oUpF8uMuAJO_M2pxb1Q9zNjWeS6o');
        $successOrder->setDescription('成功支付测试订单');
        $successOrder->setStatus(PayOrderStatus::SUCCESS);
        $successOrder->setTransactionId('4200001234567890123456789012345678');
        $successOrder->setTradeState('SUCCESS');
        $successOrder->setCallbackTime(CarbonImmutable::now()->subHours(1));
        $successOrder->setCallbackResponse('{"return_code":"SUCCESS","return_msg":"OK"}');
        $manager->persist($successOrder);
        $this->addReference(self::SUCCESS_ORDER_REFERENCE, $successOrder);

        $manager->flush();
    }
}
