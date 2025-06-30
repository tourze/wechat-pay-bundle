<?php

namespace WechatPayBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Exception\PaymentParameterException;
use WechatPayBundle\Exception\WechatPayException;

class PaymentParameterExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new PaymentParameterException('Test message');
        $this->assertInstanceOf(WechatPayException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}