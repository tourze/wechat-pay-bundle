<?php

namespace WechatPayBundle\Request;

class AppOrderParams
{
    /**
     * @var string 商户ID
     */
    private string $mchId = '';

    private string $openId = '';

    /**
     * @var string 应用id
     */
    private string $appId;

    /**
     * @var string 订单ID
     */
    private string $contractId;

    /**
     * @var string 币种
     */
    private string $currency = 'CNY';

    /**
     * @var int 支付价格 单位分
     */
    private int $money;

    /**
     * @var string 描述
     */
    private string $description = '新订单';

    /**
     * @var string 附加信息
     */
    private string $attach = '';

    public function getMchId(): string
    {
        return $this->mchId;
    }

    public function setMchId(string $mchId): void
    {
        $this->mchId = $mchId;
    }

    public function getContractId(): string
    {
        return $this->contractId;
    }

    public function setContractId(string $contractId): void
    {
        $this->contractId = $contractId;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getMoney(): int
    {
        return $this->money;
    }

    public function setMoney(int $money): void
    {
        $this->money = $money;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getAttach(): string
    {
        return $this->attach;
    }

    public function setAttach(string $attach): void
    {
        $this->attach = $attach;
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function getOpenId(): string
    {
        return $this->openId;
    }

    public function setOpenId(string $openId): void
    {
        $this->openId = $openId;
    }
}
