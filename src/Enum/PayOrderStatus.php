<?php

namespace WechatPayBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum PayOrderStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case INIT = 'init';
    case SUCCESS = 'success';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::INIT => '未回调',
            self::SUCCESS => '回调成功',
            self::FAILED => '回调失败',
        };
    }
}
