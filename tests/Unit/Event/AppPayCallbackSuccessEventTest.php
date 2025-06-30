<?php

namespace WechatPayBundle\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Event\AppPayCallbackSuccessEvent;

class AppPayCallbackSuccessEventTest extends TestCase
{
    public function testEvent(): void
    {
        $event = new AppPayCallbackSuccessEvent();
        $this->assertInstanceOf(AppPayCallbackSuccessEvent::class, $event);
    }
}