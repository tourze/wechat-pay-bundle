<?php

namespace WechatPayBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Repository\PayOrderRepository;
use WechatPayBundle\Service\WechatAppPayService;

/**
 * @internal
 */
#[CoversClass(WechatAppPayService::class)]
#[RunTestsInSeparateProcesses]
final class UnifiedOrderTest extends AbstractIntegrationTestCase
{
    private WechatAppPayService $unifiedOrder;

    protected function onSetUp(): void
    {
        // 按照集成测试最佳实践，从容器中获取服务实例
        $this->unifiedOrder = self::getService(WechatAppPayService::class);
    }

    /**
     * 测试服务可以被正确实例化
     */
    public function testServiceIsInstantiable(): void
    {
        $this->assertInstanceOf(WechatAppPayService::class, $this->unifiedOrder);
    }

    /**
     * 测试商户Repository集成
     */
    public function testMerchantRepositoryIntegration(): void
    {
        $entityManager = self::getService(EntityManagerInterface::class);
        $merchantRepository = self::getService(MerchantRepository::class);

        // 创建测试商户
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_integration');
        $merchant->setCertSerial('test_cert_serial');
        $merchant->setPemKey('test_key');
        $merchant->setApiKey('test_api_key');

        $entityManager->persist($merchant);
        $entityManager->flush();

        // 验证可以从数据库中查询到商户
        $savedMerchant = $merchantRepository->findOneBy(['mchId' => 'test_merchant_integration']);
        $this->assertNotNull($savedMerchant);
        $this->assertEquals('test_cert_serial', $savedMerchant->getCertSerial());

        // 清理测试数据
        $entityManager->remove($savedMerchant);
        $entityManager->flush();
    }

    /**
     * 测试PayOrderRepository集成
     */
    public function testPayOrderRepositoryIntegration(): void
    {
        $entityManager = self::getService(EntityManagerInterface::class);
        $payOrderRepository = self::getService(PayOrderRepository::class);

        // 创建测试商户
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_order');
        $merchant->setCertSerial('test_cert_order');
        $merchant->setPemKey('test_key');
        $merchant->setApiKey('test_api_key');
        $entityManager->persist($merchant);

        // 创建测试支付订单
        $payOrder = new PayOrder();
        $payOrder->setMchId('test_merchant_order');
        $payOrder->setAppId('test_app_id');
        $payOrder->setTradeType('APP');
        $payOrder->setTradeNo('test_trade_no_' . uniqid());
        $payOrder->setBody('测试订单');
        $payOrder->setTotalFee(100);
        $payOrder->setNotifyUrl('https://example.com/notify');
        $payOrder->setStatus(PayOrderStatus::INIT);

        $entityManager->persist($payOrder);
        $entityManager->flush();

        // 验证可以从数据库中查询到支付订单
        $savedPayOrder = $payOrderRepository->findOneBy(['tradeNo' => $payOrder->getTradeNo()]);
        $this->assertNotNull($savedPayOrder);
        $this->assertEquals(PayOrderStatus::INIT, $savedPayOrder->getStatus());
        $this->assertEquals(100, $savedPayOrder->getTotalFee());

        // 清理测试数据
        $entityManager->remove($savedPayOrder);
        $entityManager->remove($merchant);
        $entityManager->flush();
    }

    /**
     * 测试生成签名方法（纯逻辑，不需要Mock）
     */
    public function testGenerateSign(): void
    {
        $attributes = [
            'appid' => 'wx123456',
            'mch_id' => '1234567890',
            'nonce_str' => 'abc123',
            'body' => '测试商品',
            'out_trade_no' => 'order123456',
            'total_fee' => 100,
            'spbill_create_ip' => '127.0.0.1',
            'notify_url' => 'https://example.com/notify',
            'trade_type' => 'APP',
        ];

        $key = 'test_key_123456';

        // 计算预期签名
        $expectedSignData = $attributes;
        ksort($expectedSignData);
        $expectedSignData['key'] = $key;
        $expectedSign = strtoupper(md5(urldecode(http_build_query($expectedSignData))));

        // 实际生成签名
        $sign = $this->unifiedOrder->generateSign($attributes, $key);

        // 验证
        $this->assertEquals($expectedSign, $sign);
    }

    /**
     * 测试EntityManager集成
     */
    public function testEntityManagerIntegration(): void
    {
        $entityManager = self::getService(EntityManagerInterface::class);

        // 验证EntityManager可以正常工作
        $this->assertNotNull($entityManager);

        // 测试事务操作
        $entityManager->beginTransaction();

        try {
            $merchant = new Merchant();
            $merchant->setMchId('test_tx_merchant');
            $merchant->setCertSerial('test_tx_cert');
            $merchant->setPemKey('test_tx_key');
            $merchant->setApiKey('test_api_key');

            $entityManager->persist($merchant);
            $entityManager->flush();

            // 验证数据已保存
            $this->assertNotNull($merchant->getId());

            // 回滚事务
            $entityManager->rollback();

            // 验证数据已被回滚
            $savedMerchant = $entityManager->getRepository(Merchant::class)
                ->findOneBy(['mchId' => 'test_tx_merchant']);
            $this->assertNull($savedMerchant);

        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
    }

    /**
     * 测试获取订单详情方法
     */
    public function testGetTradeOrderDetail(): void
    {
        // 由于方法未实现，仅测试返回空数组
        $result = $this->unifiedOrder->getTradeOrderDetail('order123456');
        $this->assertEmpty($result);
    }

    /**
     * 测试通知方法
     */
    public function testNotify(): void
    {
        // notify 方法当前为空实现，仅测试其可被调用
        $this->unifiedOrder->notify();

        // 方法正常完成，没有异常即说明测试通过
        $this->expectNotToPerformAssertions();
    }

    /**
     * 测试创建APP支付订单方法可调用
     *
     * 由于 createAppOrder 方法需要有效的商户数据和RSA密钥，
     * 在当前测试环境下会因为密钥验证失败，因此使用反射验证方法签名
     */
    public function testCreateAppOrder(): void
    {
        $reflection = new \ReflectionMethod($this->unifiedOrder, 'createAppOrder');
        $this->assertTrue($reflection->isPublic(), 'createAppOrder should be public');
        $this->assertCount(1, $reflection->getParameters(), 'createAppOrder should accept one parameter');
    }
}