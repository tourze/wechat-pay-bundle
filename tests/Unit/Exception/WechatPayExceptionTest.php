<?php

namespace WechatPayBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WechatPayBundle\Exception\WechatPayException;

class WechatPayExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new WechatPayException('Test message');
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}