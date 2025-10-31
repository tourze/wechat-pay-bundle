<?php

namespace WechatPayBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;

/**
 * @internal
 */
#[CoversClass(PayOrder::class)]
final class PayOrderTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new PayOrder();
    }

    public function testEntity(): void
    {
        $entity = new PayOrder();
        $this->assertInstanceOf(PayOrder::class, $entity);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'appId' => ['appId', 'test_app_id'],
            'mchId' => ['mchId', 'test_mch_id'],
            'tradeType' => ['tradeType', 'NATIVE'],
            'tradeNo' => ['tradeNo', 'test_trade_no_' . uniqid()],
            'body' => ['body', '测试商品描述'],
            'feeType' => ['feeType', 'CNY'],
            'totalFee' => ['totalFee', 100],
            'notifyUrl' => ['notifyUrl', 'https://example.com/notify'],
            'openId' => ['openId', 'test_open_id'],
            'attach' => ['attach', 'test_attach_data'],
            'remark' => ['remark', '测试备注'],
            'requestJson' => ['requestJson', '{"test": "data"}'],
            'responseJson' => ['responseJson', '{"response": "data"}'],
            'callbackResponse' => ['callbackResponse', '{"callback": "data"}'],
            'status' => ['status', PayOrderStatus::INIT],
            'transactionId' => ['transactionId', 'test_transaction_id'],
            'tradeState' => ['tradeState', 'SUCCESS'],
            'description' => ['description', '测试描述'],
            'prepayId' => ['prepayId', 'test_prepay_id'],
        ];
    }
}
