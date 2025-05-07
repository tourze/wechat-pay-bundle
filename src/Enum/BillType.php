<?php

namespace WechatPayBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum BillType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case ALL = 'ALL';
    case SUCCESS = 'SUCCESS';
    case REFUND = 'REFUND';
    case RECHARGE_REFUND = 'RECHARGE_REFUND';
    case ALL_SPECIAL = 'ALL_SPECIAL';
    case SUC_SPECIAL = 'SUC_SPECIAL';
    case REF_SPECIAL = 'REF_SPECIAL';

    public function getLabel(): string
    {
        return match ($this) {
            self::ALL => '当日所有订单信息（不含充值退款订单）',
            self::SUCCESS => '当日成功支付的订单（不含充值退款订单）',
            self::REFUND => '当日退款订单（不含充值退款订单）',
            self::RECHARGE_REFUND => '当日充值退款订单',
            self::ALL_SPECIAL => '个性化账单当日所有订单信息',
            self::SUC_SPECIAL => '个性化账单当日成功支付的订单',
            self::REF_SPECIAL => '个性化账单当日退款订单',
        };
    }
}
