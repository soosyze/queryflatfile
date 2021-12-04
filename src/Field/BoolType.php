<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile\Field;

use Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class BoolType extends Field
{
    public const TYPE = 'boolean';

    /**
     * {@inheritdoc}
     *
     * return bool
     */
    public function filterValue($value)
    {
        if (!\is_bool($value)) {
            throw new \InvalidArgumentException(
                sprintf(self::INVALID_ARGUMENT_MESSAGE, $this->name, self::TYPE, gettype($value))
            );
        }

        return $value;
    }
}
