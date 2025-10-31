<?php

namespace WechatPayBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Event\AppPayCallbackSuccessEvent;

/**
 * @internal
 */
#[CoversClass(AppPayCallbackSuccessEvent::class)]
final class AppPayCallbackSuccessEventTest extends AbstractEventTestCase
{
    public function testEvent(): void
    {
        $event = new AppPayCallbackSuccessEvent();
        $this->assertInstanceOf(AppPayCallbackSuccessEvent::class, $event);
    }

    public function testSetAndGetPayOrder(): void
    {
        $event = new AppPayCallbackSuccessEvent();
        $payOrder = $this->createMock(PayOrder::class);

        $event->setPayOrder($payOrder);
        $this->assertSame($payOrder, $event->getPayOrder());
    }

    public function testSetAndGetDecryptData(): void
    {
        $event = new AppPayCallbackSuccessEvent();
        $decryptData = ['key' => 'value'];

        $event->setDecryptData($decryptData);
        $this->assertSame($decryptData, $event->getDecryptData());
    }
}
