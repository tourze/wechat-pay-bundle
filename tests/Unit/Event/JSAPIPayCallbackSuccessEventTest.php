<?php

namespace WechatPayBundle\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Event\JSAPIPayCallbackSuccessEvent;

class JSAPIPayCallbackSuccessEventTest extends TestCase
{
    public function testEvent(): void
    {
        $event = new JSAPIPayCallbackSuccessEvent();
        $this->assertInstanceOf(JSAPIPayCallbackSuccessEvent::class, $event);
    }
}