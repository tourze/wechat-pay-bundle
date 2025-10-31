<?php

namespace WechatPayBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use WechatPayBundle\Enum\BillType;

/**
 * @internal
 */
#[CoversClass(BillType::class)]
final class BillTypeTest extends AbstractEnumTestCase
{
    public function testGetLabel(): void
    {
        $this->assertSame('当日所有订单信息（不含充值退款订单）', BillType::ALL->getLabel());
        $this->assertSame('当日成功支付的订单（不含充值退款订单）', BillType::SUCCESS->getLabel());
        $this->assertSame('当日退款订单（不含充值退款订单）', BillType::REFUND->getLabel());
        $this->assertSame('当日充值退款订单', BillType::RECHARGE_REFUND->getLabel());
        $this->assertSame('个性化账单当日所有订单信息', BillType::ALL_SPECIAL->getLabel());
        $this->assertSame('个性化账单当日成功支付的订单', BillType::SUC_SPECIAL->getLabel());
        $this->assertSame('个性化账单当日退款订单', BillType::REF_SPECIAL->getLabel());
    }

    public function testToArray(): void
    {
        $expected = [
            'value' => 'ALL',
            'label' => '当日所有订单信息（不含充值退款订单）',
        ];

        $this->assertSame($expected, BillType::ALL->toArray());
    }

    public function testGenOptions(): void
    {
        $options = BillType::genOptions();
        $this->assertCount(7, $options);

        // 测试第一个选项的结构
        $this->assertArrayHasKey('label', $options[0]);
        $this->assertArrayHasKey('text', $options[0]);
        $this->assertArrayHasKey('value', $options[0]);
        $this->assertArrayHasKey('name', $options[0]);

        // 测试特定选项
        $allOption = $options[0];
        $this->assertSame('ALL', $allOption['value']);
        $this->assertSame('当日所有订单信息（不含充值退款订单）', $allOption['label']);
        $this->assertSame('当日所有订单信息（不含充值退款订单）', $allOption['text']);
        $this->assertSame('当日所有订单信息（不含充值退款订单）', $allOption['name']);
    }
}
