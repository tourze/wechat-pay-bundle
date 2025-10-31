<?php

namespace WechatPayBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use WechatPayBundle\Entity\RefundGoodsDetail;

/**
 * @internal
 */
#[CoversClass(RefundGoodsDetail::class)]
final class RefundGoodsDetailTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new RefundGoodsDetail();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'merchantGoodsId' => ['merchantGoodsId', 'test_merchant_goods_id'],
            'wechatpayGoodsId' => ['wechatpayGoodsId', 'test_wechatpay_goods_id'],
            'goodsName' => ['goodsName', '测试商品名称'],
            'unitPrice' => ['unitPrice', 100],
            'refundAmount' => ['refundAmount', 50],
            'refundQuantity' => ['refundQuantity', 1],
        ];
    }

    private RefundGoodsDetail $entity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = new RefundGoodsDetail();
    }

    public function testEntityCreation(): void
    {
        $this->assertInstanceOf(RefundGoodsDetail::class, $this->entity);
    }
}
