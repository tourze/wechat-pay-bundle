<?php

namespace WechatPayBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Event\NativePayCallbackSuccessEvent;

/**
 * @internal
 */
#[CoversClass(NativePayCallbackSuccessEvent::class)]
final class NativePayCallbackSuccessEventTest extends AbstractEventTestCase
{
    public function testEvent(): void
    {
        $event = new NativePayCallbackSuccessEvent();
        $this->assertInstanceOf(NativePayCallbackSuccessEvent::class, $event);
    }

    public function testSetAndGetPayOrder(): void
    {
        $event = new NativePayCallbackSuccessEvent();
        $payOrder = $this->createMock(PayOrder::class);

        $event->setPayOrder($payOrder);
        $this->assertSame($payOrder, $event->getPayOrder());
    }

    public function testSetAndGetDecryptData(): void
    {
        $event = new NativePayCallbackSuccessEvent();
        $decryptData = ['key' => 'value'];

        $event->setDecryptData($decryptData);
        $this->assertSame($decryptData, $event->getDecryptData());
    }
}
