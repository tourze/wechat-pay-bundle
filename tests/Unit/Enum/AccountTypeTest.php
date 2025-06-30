<?php

namespace WechatPayBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Enum\AccountType;

class AccountTypeTest extends TestCase
{
    public function testEnum(): void
    {
        $cases = AccountType::cases();
        $this->assertCount(3, $cases);
        $this->assertContains(AccountType::BASIC, $cases);
        $this->assertContains(AccountType::OPERATION, $cases);
        $this->assertContains(AccountType::FEES, $cases);
    }
}