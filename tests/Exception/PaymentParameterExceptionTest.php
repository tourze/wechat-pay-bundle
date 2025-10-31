<?php

namespace WechatPayBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use WechatPayBundle\Exception\PaymentParameterException;
use WechatPayBundle\Exception\WechatPayException;

/**
 * @internal
 */
#[CoversClass(PaymentParameterException::class)]
final class PaymentParameterExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return PaymentParameterException::class;
    }

    protected function getParentExceptionClass(): string
    {
        return WechatPayException::class;
    }
}
