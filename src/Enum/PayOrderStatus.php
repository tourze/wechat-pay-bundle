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
    case PAYING = 'paying';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CLOSED = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::INIT => '未回调',
            self::PAYING => '支付中',
            self::SUCCESS => '回调成功',
            self::FAILED => '回调失败',
            self::CLOSED => '已关闭',
        };
    }
}
