<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class FloatType extends Field
{
    public const TYPE = 'float';

    /**
     * {@inheritdoc}
     */
    public function tryOrGetValue(null|bool|string|int|float $value): float
    {
        if (!\is_float($value)) {
            throw new \InvalidArgumentException(
                sprintf(self::INVALID_ARGUMENT_MESSAGE, $this->name, self::TYPE, gettype($value))
            );
        }

        return $value;
    }
}
