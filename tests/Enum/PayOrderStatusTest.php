<?php

namespace WechatPayBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use WechatPayBundle\Enum\PayOrderStatus;

/**
 * @internal
 */
#[CoversClass(PayOrderStatus::class)]
final class PayOrderStatusTest extends AbstractEnumTestCase
{
    /**
     * 测试标签方法
     */
    public function testGetLabel(): void
    {
        $this->assertEquals('未回调', PayOrderStatus::INIT->getLabel());
        $this->assertEquals('支付中', PayOrderStatus::PAYING->getLabel());
        $this->assertEquals('回调成功', PayOrderStatus::SUCCESS->getLabel());
        $this->assertEquals('回调失败', PayOrderStatus::FAILED->getLabel());
        $this->assertEquals('已关闭', PayOrderStatus::CLOSED->getLabel());
    }

    public function testToArray(): void
    {
        $array = PayOrderStatus::INIT->toArray();

        $this->assertCount(2, $array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('init', $array['value']);
        $this->assertEquals('未回调', $array['label']);
    }
}
