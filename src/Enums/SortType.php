<?php

namespace Soosyze\Queryflatfile\Enums;

enum SortType: int
{
    case Asc = 1;
    case Desc = -1;

    public static function tryFromPhpSort(mixed $sort): self
    {
        return match ($sort) {
            SORT_ASC => SortType::Asc,
            SORT_DESC => SortType::Desc,
            default => throw new \InvalidArgumentException(
                'The value must be that of the constants SORT_ASC|SORT_DESC'
            ),
        };
    }

    public function toString(): string
    {
        return $this === SortType::Asc ? 'ASC' : 'DESC';
    }
}
