<?php

namespace WechatPayBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Entity\FundFlowBill;

class FundFlowBillTest extends TestCase
{
    public function testEntity(): void
    {
        $entity = new FundFlowBill();
        $this->assertInstanceOf(FundFlowBill::class, $entity);
    }

    public function testGettersAndSetters(): void
    {
        $entity = new FundFlowBill();
        
        $entity->setHashType('MD5');
        $this->assertEquals('MD5', $entity->getHashType());
        
        $entity->setDownloadUrl('https://example.com/bill.csv');
        $this->assertEquals('https://example.com/bill.csv', $entity->getDownloadUrl());
    }
}