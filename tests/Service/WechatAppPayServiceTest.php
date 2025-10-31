<?php

namespace WechatPayBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use WechatPayBundle\Exception\PaymentParameterException;
use WechatPayBundle\Request\AppOrderParams;
use WechatPayBundle\Service\WechatAppPayService;

/**
 * @internal
 */
#[CoversClass(WechatAppPayService::class)]
#[RunTestsInSeparateProcesses]
final class WechatAppPayServiceTest extends AbstractIntegrationTestCase
{
    private WechatAppPayService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(WechatAppPayService::class);
    }

    /**
     * 测试服务可以被正确实例化
     */
    public function testServiceIsInstantiable(): void
    {
        $this->assertInstanceOf(WechatAppPayService::class, $this->service);
    }

    /**
     * 测试生成签名
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
        $sign = $this->service->generateSign($attributes, $key);

        // 验证
        $this->assertEquals($expectedSign, $sign);
    }

    /**
     * 测试获取订单详情
     */
    public function testGetTradeOrderDetail(): void
    {
        // 由于方法未实现，仅测试返回空数组
        $result = $this->service->getTradeOrderDetail('order123456');
        $this->assertEmpty($result);
    }

    /**
     * 测试通知方法
     */
    public function testNotify(): void
    {
        // notify 方法当前为空实现，仅测试其可被调用
        $this->service->notify();

        // 方法正常完成，没有异常即说明测试通过
        $this->expectNotToPerformAssertions();
    }

    /**
     * 测试签名生成输入参数验证
     */
    public function testGenerateSignWithDifferentEncryptMethod(): void
    {
        $attributes = [
            'appid' => 'wx123456',
            'mch_id' => '1234567890',
        ];

        $key = 'test_key_123456';

        // 使用sha1而不是默认的md5
        $expectedSignData = $attributes;
        ksort($expectedSignData);
        $expectedSignData['key'] = $key;
        $expectedSign = strtoupper(sha1(urldecode(http_build_query($expectedSignData))));

        $sign = $this->service->generateSign($attributes, $key, 'sha1');

        $this->assertEquals($expectedSign, $sign);
    }

    /**
     * 测试创建应用订单 - 验证参数缺失的异常处理
     */
    public function testCreateAppOrderWithMissingMerchant(): void
    {
        // 创建一个包含必要参数的AppOrderParams
        $appOrderParams = new AppOrderParams();
        $appOrderParams->setAppId('wx123456');
        $appOrderParams->setMchId('nonexistent_mch_id');
        $appOrderParams->setDescription('测试商品');
        $appOrderParams->setContractId('contract123');
        $appOrderParams->setMoney(100);
        $appOrderParams->setCurrency('CNY');
        $appOrderParams->setAttach('test_attach');

        // 期望抛出PaymentParameterException，因为商户不存在
        $this->expectException(PaymentParameterException::class);
        $this->expectExceptionMessage('Merchant not found');

        $this->service->createAppOrder($appOrderParams);
    }
}
