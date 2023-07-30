<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Concern\Field;

/**
 * @property string $name
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
trait ThrowInvalidType
{
    /**
     * @throws \InvalidArgumentException
     */
    public function throwInvalidType(mixed $value): never
    {
        throw new \InvalidArgumentException(
            sprintf(
                'The value of the %s field must be of type %s: %s given.',
                $this->name,
                $this->getType()->realType(),
                gettype($value)
            )
        );
    }
}
