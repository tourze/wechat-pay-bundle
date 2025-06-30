<?php

namespace WechatPayBundle\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Event\NativePayCallbackSuccessEvent;

class NativePayCallbackSuccessEventTest extends TestCase
{
    public function testEvent(): void
    {
        $event = new NativePayCallbackSuccessEvent();
        $this->assertInstanceOf(NativePayCallbackSuccessEvent::class, $event);
    }
}