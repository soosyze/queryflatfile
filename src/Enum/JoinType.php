<?php

namespace Soosyze\Queryflatfile\Enum;

enum JoinType
{
    case Left;
    case Right;

    public function toString(): string
    {
        return $this === self::Left ? 'LEFT' : 'RIGHT';
    }
}
