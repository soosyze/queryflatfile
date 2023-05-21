<?php

namespace Soosyze\Queryflatfile\Enum;

enum UnionType
{
    case Simple;
    case All;

    public function toString(): string
    {
        return $this ===  self::Simple ? 'UNION' : 'UNION ALL';
    }
}
