<?php

declare(strict_types=1);

namespace WechatPayBundle\Service\Transfer;

interface MerchantTransferServiceInterface
{
    public function transfer(MerchantTransferCommand $command): MerchantTransferResult;
}
