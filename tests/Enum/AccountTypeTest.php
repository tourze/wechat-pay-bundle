<?php

namespace WechatPayBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use WechatPayBundle\Enum\AccountType;

/**
 * @internal
 */
#[CoversClass(AccountType::class)]
final class AccountTypeTest extends AbstractEnumTestCase
{
    public function testToArray(): void
    {
        $array = AccountType::BASIC->toArray();

        $this->assertCount(2, $array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('BASIC', $array['value']);
        $this->assertEquals('基本账户', $array['label']);
    }
}
