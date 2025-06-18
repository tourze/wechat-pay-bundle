<?php

namespace WechatPayBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Enum\PayOrderStatus;

class PayOrderStatusTest extends TestCase
{
    /**
     * 测试枚举值
     */
    public function testEnumValues(): void
    {
        $this->assertEquals('init', PayOrderStatus::INIT->value);
        $this->assertEquals('success', PayOrderStatus::SUCCESS->value);
        $this->assertEquals('failed', PayOrderStatus::FAILED->value);
    }
    
    /**
     * 测试标签方法
     */
    public function testGetLabel(): void
    {
        $this->assertEquals('未回调', PayOrderStatus::INIT->getLabel());
        $this->assertEquals('回调成功', PayOrderStatus::SUCCESS->getLabel());
        $this->assertEquals('回调失败', PayOrderStatus::FAILED->getLabel());
    }
    
    /**
     * 测试从值获取枚举
     */
    public function testFromValue(): void
    {
        $this->assertSame(PayOrderStatus::INIT, PayOrderStatus::from('init'));
        $this->assertSame(PayOrderStatus::SUCCESS, PayOrderStatus::from('success'));
        $this->assertSame(PayOrderStatus::FAILED, PayOrderStatus::from('failed'));
    }
    
    /**
     * 测试无效值处理
     */
    public function testInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        PayOrderStatus::from('invalid_value');
    }
    
    /**
     * 测试尝试获取无效值
     */
    public function testTryFromWithInvalidValue(): void
    {
        $this->assertNull(PayOrderStatus::tryFrom('invalid_value'));
    }
    
    /**
     * 测试尝试获取有效值
     */
    public function testTryFromWithValidValue(): void
    {
        $this->assertSame(PayOrderStatus::INIT, PayOrderStatus::tryFrom('init'));
    }
    
    /**
     * 测试 cases 方法
     */
    public function testCases(): void
    {
        $cases = PayOrderStatus::cases();
        $this->assertCount(3, $cases);
        $this->assertContains(PayOrderStatus::INIT, $cases);
        $this->assertContains(PayOrderStatus::SUCCESS, $cases);
        $this->assertContains(PayOrderStatus::FAILED, $cases);
    }

} 