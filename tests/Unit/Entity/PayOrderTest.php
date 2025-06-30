<?php

namespace WechatPayBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;

class PayOrderTest extends TestCase
{
    public function testEntity(): void
    {
        $entity = new PayOrder();
        $this->assertInstanceOf(PayOrder::class, $entity);
    }

    public function testGettersAndSetters(): void
    {
        $entity = new PayOrder();
        
        $entity->setTradeNo('order123');
        $this->assertEquals('order123', $entity->getTradeNo());
        
        $entity->setStatus(PayOrderStatus::INIT);
        $this->assertEquals(PayOrderStatus::INIT, $entity->getStatus());
        
        $entity->setTotalFee(100);
        $this->assertEquals(100, $entity->getTotalFee());
    }
}