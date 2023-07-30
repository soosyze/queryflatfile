<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Enum;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
enum CurentDefaultType: string
{
    case Date = 'current_date';
    case DateTime = 'current_datetime';

    public function format(): string
    {
        return match ($this) {
            self::Date => 'Y-m-d',
            self::DateTime => 'Y-m-d H:i:s',
        };
    }
}
