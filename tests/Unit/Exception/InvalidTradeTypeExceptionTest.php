<?php

namespace WechatPayBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Exception\InvalidTradeTypeException;
use WechatPayBundle\Exception\WechatPayException;

class InvalidTradeTypeExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new InvalidTradeTypeException('Test message');
        $this->assertInstanceOf(WechatPayException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}