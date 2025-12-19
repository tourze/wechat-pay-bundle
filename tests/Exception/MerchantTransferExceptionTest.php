<?php

declare(strict_types=1);

namespace WechatPayBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use WechatPayBundle\Exception\MerchantTransferException;
use WechatPayBundle\Exception\WechatPayException;

/**
 * @internal
 */
#[CoversClass(MerchantTransferException::class)]
final class MerchantTransferExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return MerchantTransferException::class;
    }

    public function testExtendsWechatPayException(): void
    {
        $exception = new MerchantTransferException('test message');

        $this->assertInstanceOf(WechatPayException::class, $exception);
    }
}
