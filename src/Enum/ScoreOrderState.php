<?php

namespace WechatPayBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ScoreOrderState: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case CREATED = 'CREATED';
    case DOING = 'DOING';
    case DONE = 'DONE';
    case REVOKED = 'REVOKED';
    case EXPIRED = 'EXPIRED';

    public function getLabel(): string
    {
        return match ($this) {
            self::CREATED => '已创建',
            self::DOING => '进行中',
            self::DONE => '已完成',
            self::REVOKED => '取消服务',
            self::EXPIRED => '已失效',
        };
    }
}
