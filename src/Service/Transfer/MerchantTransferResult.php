<?php

declare(strict_types=1);

namespace WechatPayBundle\Service\Transfer;

final class MerchantTransferResult
{
    public function __construct(
        private readonly string $batchId,
        private readonly string $batchStatus,
        private readonly ?string $createTime,
        private readonly string $outBatchNo,
        private readonly ?string $detailId,
        private readonly ?string $detailStatus,
        private readonly string $outDetailNo,
    ) {
    }

    public function getBatchId(): string
    {
        return $this->batchId;
    }

    public function getBatchStatus(): string
    {
        return $this->batchStatus;
    }

    public function getCreateTime(): ?string
    {
        return $this->createTime;
    }

    public function getOutBatchNo(): string
    {
        return $this->outBatchNo;
    }

    public function getDetailId(): ?string
    {
        return $this->detailId;
    }

    public function getDetailStatus(): ?string
    {
        return $this->detailStatus;
    }

    public function getOutDetailNo(): string
    {
        return $this->outDetailNo;
    }

    public function isDetailSuccessful(): bool
    {
        if (null === $this->detailStatus) {
            return false;
        }

        return \in_array($this->detailStatus, ['SUCCESS', 'FINISHED'], true);
    }
}
