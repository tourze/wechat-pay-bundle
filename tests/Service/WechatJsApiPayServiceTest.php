<?php

namespace WechatPayBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use WechatPayBundle\Service\UnifiedOrder;
use WechatPayBundle\Service\WechatJsApiPayService;

/**
 * @internal
 */
#[CoversClass(WechatJsApiPayService::class)]
#[RunTestsInSeparateProcesses]
final class WechatJsApiPayServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // WechatJsApiPayService 测试不需要特殊的设置
    }

    /**
     * 测试交易类型
     */
    public function testTradeType(): void
    {
        $service = self::getService(WechatJsApiPayService::class);

        $reflection = new \ReflectionProperty($service, 'tradeType');
        $reflection->setAccessible(true);
        $this->assertEquals('JSAPI', $reflection->getValue($service));
    }

    /**
     * 测试继承关系
     */
    public function testExtendsUnifiedOrder(): void
    {
        $service = self::getService(WechatJsApiPayService::class);

        $this->assertInstanceOf(UnifiedOrder::class, $service);
    }

    /**
     * 测试服务可实例化
     */
    public function testServiceIsInstantiable(): void
    {
        $service = self::getService(WechatJsApiPayService::class);

        $this->assertInstanceOf(WechatJsApiPayService::class, $service);
    }

    /**
     * 测试类的基本结构
     */
    public function testClassStructure(): void
    {
        $reflection = new \ReflectionClass(WechatJsApiPayService::class);

        // 验证类可实例化
        $this->assertTrue($reflection->isInstantiable());

        // 验证继承关系
        $this->assertTrue($reflection->isSubclassOf(UnifiedOrder::class));

        // 验证有 tradeType 属性
        $this->assertTrue($reflection->hasProperty('tradeType'));

        // 验证属性值
        $service = self::getService(WechatJsApiPayService::class);

        $reflection = new \ReflectionProperty($service, 'tradeType');
        $reflection->setAccessible(true);
        $this->assertEquals('JSAPI', $reflection->getValue($service));
    }
}
