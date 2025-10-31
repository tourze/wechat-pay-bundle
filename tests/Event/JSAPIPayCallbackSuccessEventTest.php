<?php

namespace WechatPayBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Event\JSAPIPayCallbackSuccessEvent;

/**
 * @internal
 */
#[CoversClass(JSAPIPayCallbackSuccessEvent::class)]
final class JSAPIPayCallbackSuccessEventTest extends AbstractEventTestCase
{
    public function testEvent(): void
    {
        $event = new JSAPIPayCallbackSuccessEvent();
        $this->assertInstanceOf(JSAPIPayCallbackSuccessEvent::class, $event);
    }

    public function testSetAndGetPayOrder(): void
    {
        $event = new JSAPIPayCallbackSuccessEvent();
        $payOrder = $this->createMock(PayOrder::class);

        $event->setPayOrder($payOrder);
        $this->assertSame($payOrder, $event->getPayOrder());
    }

    public function testSetAndGetPayload(): void
    {
        $event = new JSAPIPayCallbackSuccessEvent();
        $payload = ['key' => 'value'];

        $event->setPayload($payload);
        $this->assertSame($payload, $event->getPayload());
    }
}
