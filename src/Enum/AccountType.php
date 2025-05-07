<?php

namespace WechatPayBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum AccountType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case BASIC = 'BASIC';
    case OPERATION = 'OPERATION';
    case FEES = 'FEES';

    public function getLabel(): string
    {
        return match ($this) {
            self::BASIC => '基本账户',
            self::OPERATION => '运营账户',
            self::FEES => '手续费账户',
        };
    }
}
