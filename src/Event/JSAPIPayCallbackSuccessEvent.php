<?php

namespace WechatPayBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WechatPayBundle\Entity\PayOrder;

/**
 * 统一下单回调事件
 */
class JSAPIPayCallbackSuccessEvent extends Event
{
    protected array $payload = [];

    private PayOrder $payOrder;

    public function getPayOrder(): PayOrder
    {
        return $this->payOrder;
    }

    public function setPayOrder(PayOrder $payOrder): void
    {
        $this->payOrder = $payOrder;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }
}
