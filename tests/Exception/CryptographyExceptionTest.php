<?php

namespace WechatPayBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use WechatPayBundle\Exception\CryptographyException;
use WechatPayBundle\Exception\WechatPayException;

/**
 * @internal
 */
#[CoversClass(CryptographyException::class)]
final class CryptographyExceptionTest extends AbstractExceptionTestCase
{
    public function testExtendsWechatPayException(): void
    {
        $exception = new CryptographyException('Test message');
        $this->assertInstanceOf(WechatPayException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionMessage(): void
    {
        $message = 'Encryption method not callable';
        $exception = new CryptographyException($message);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $code = 1001;
        $exception = new CryptographyException('Test', $code);
        $this->assertSame($code, $exception->getCode());
    }
}
