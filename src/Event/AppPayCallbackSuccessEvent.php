<?php

namespace WechatPayBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WechatPayBundle\Entity\PayOrder;

/**
 * 微信app成功回调事件
 */
class AppPayCallbackSuccessEvent extends Event
{
    private PayOrder $payOrder;

    /** @var array<string, mixed> */
    private array $decryptData = [];

    public function getPayOrder(): PayOrder
    {
        return $this->payOrder;
    }

    public function setPayOrder(PayOrder $payOrder): void
    {
        $this->payOrder = $payOrder;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDecryptData(): array
    {
        return $this->decryptData;
    }

    /**
     * @param array<string, mixed> $decryptData
     */
    public function setDecryptData(array $decryptData): void
    {
        $this->decryptData = $decryptData;
    }
}
