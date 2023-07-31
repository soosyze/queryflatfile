<?php

namespace Soosyze\Queryflatfile\Enums;

enum JoinType
{
    case Left;
    case Right;

    public function toString(): string
    {
        return $this === self::Left ? 'LEFT' : 'RIGHT';
    }
}
