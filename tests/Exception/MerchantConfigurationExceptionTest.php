<?php

namespace WechatPayBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use WechatPayBundle\Exception\MerchantConfigurationException;
use WechatPayBundle\Exception\WechatPayException;

/**
 * @internal
 */
#[CoversClass(MerchantConfigurationException::class)]
final class MerchantConfigurationExceptionTest extends AbstractExceptionTestCase
{
    public function testExtendsWechatPayException(): void
    {
        $exception = new MerchantConfigurationException('Test message');
        $this->assertInstanceOf(WechatPayException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionMessage(): void
    {
        $message = 'Merchant configuration not found';
        $exception = new MerchantConfigurationException($message);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $code = 2001;
        $exception = new MerchantConfigurationException('Test', $code);
        $this->assertSame($code, $exception->getCode());
    }
}
