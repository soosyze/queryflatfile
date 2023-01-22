<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

trait ValueToString
{
    /**
     * @param array|null|scalar|Where $value
     */
    protected static function getValueToString($value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return sprintf('\'%s\'', addslashes($value));
        }
        if ($value instanceof Where) {
            return (string) $value;
        }
        if (is_array($value)) {
            return implode(
                ', ',
                array_map(
                    function ($item): string {
                        return self::getValueToString($item);
                    },
                    $value
                )
            );
        }

        return 'null';
    }
}
