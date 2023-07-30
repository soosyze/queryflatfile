<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Concern\Field;

/**
 * @property string $name
 * @property int    $length
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
trait TryOrGetString
{
    use ThrowInvalidType;

    /**
     * {@inheritdoc}
     */
    public function tryOrGetValue(mixed $value): string
    {
        if (!\is_string($value)) {
            $this->throwInvalidType($value);
        }

        /** @var string $value */
        if (strlen($value) > $this->length) {
            throw new \LengthException(
                sprintf(
                    'The value of the %s field must be less than or equal to %s characters: %s given',
                    $this->name,
                    $this->length,
                    strlen($value)
                )
            );
        }

        return $value;
    }
}
