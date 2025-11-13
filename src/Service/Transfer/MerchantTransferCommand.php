<?php

declare(strict_types=1);

namespace WechatPayBundle\Service\Transfer;

final class MerchantTransferCommand
{
    public function __construct(
        private readonly string $appId,
        private readonly string $outBatchNo,
        private readonly string $batchName,
        private readonly string $batchRemark,
        private readonly string $outDetailNo,
        private readonly int $amount,
        private readonly string $transferRemark,
        private readonly string $openid,
        private readonly ?string $userName = null,
        private readonly ?string $merchantId = null,
    ) {
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function getOutBatchNo(): string
    {
        return $this->outBatchNo;
    }

    public function getBatchName(): string
    {
        return $this->batchName;
    }

    public function getBatchRemark(): string
    {
        return $this->batchRemark;
    }

    public function getOutDetailNo(): string
    {
        return $this->outDetailNo;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getTransferRemark(): string
    {
        return $this->transferRemark;
    }

    public function getOpenid(): string
    {
        return $this->openid;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getMerchantId(): ?string
    {
        return $this->merchantId;
    }
}
