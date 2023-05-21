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
class TextType extends Field
{
    public const TYPE = 'text';

    /**
     * {@inheritdoc}
     */
    public function tryOrGetValue(null|bool|string|int|float $value): string
    {
        if (!\is_string($value)) {
            throw new \InvalidArgumentException(
                sprintf(self::INVALID_ARGUMENT_MESSAGE, $this->name, 'string', gettype($value))
            );
        }

        return $value;
    }
}
