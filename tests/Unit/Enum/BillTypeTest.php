<?php

namespace WechatPayBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Enum\BillType;

class BillTypeTest extends TestCase
{
    public function testEnum(): void
    {
        $cases = BillType::cases();
        $this->assertCount(7, $cases);
        $this->assertContains(BillType::ALL, $cases);
        $this->assertContains(BillType::SUCCESS, $cases);
        $this->assertContains(BillType::REFUND, $cases);
    }
}