<?php

namespace WechatPayBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use WechatPayBundle\Entity\RefundOrder;

/**
 * @internal
 */
#[CoversClass(RefundOrder::class)]
final class RefundOrderTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new RefundOrder();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'appId' => ['appId', 'test_app_id'],
            'reason' => ['reason', '测试退款原因'],
            'notifyUrl' => ['notifyUrl', 'https://example.com/refund-notify'],
            'currency' => ['currency', 'CNY'],
            'money' => ['money', 50],
            'requestJson' => ['requestJson', '{"test": "data"}'],
            'responseJson' => ['responseJson', '{"response": "data"}'],
            'callbackResponse' => ['callbackResponse', '{"callback": "data"}'],
            'refundId' => ['refundId', 'test_refund_id'],
            'refundChannel' => ['refundChannel', 'ORIGINAL'],
            'userReceiveAccount' => ['userReceiveAccount', 'test_account'],
            'status' => ['status', 'SUCCESS'],
        ];
    }

    private RefundOrder $entity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = new RefundOrder();
    }

    public function testEntityCreation(): void
    {
        $this->assertInstanceOf(RefundOrder::class, $this->entity);
    }
}
