<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

use Soosyze\Queryflatfile\Exception\TableBuilder\ColumnsValueException;
use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class IncrementType extends Field
{
    public const TYPE = 'increments';

    /**
     * {@inheritdoc}
     *
     * return int
     */
    public function filterValue($value)
    {
        if (!\is_int($value)) {
            throw new \InvalidArgumentException(
                sprintf(self::INVALID_ARGUMENT_MESSAGE, $this->name, 'integer', gettype($value))
            );
        }

        return (int) $value;
    }

    /**
     * @throws ColumnsValueException
     */
    public function getValueDefault()
    {
        throw new ColumnsValueException('An incremental type column can not have a default value.');
    }

    /**
     * @throws ColumnsValueException
     */
    public function valueDefault($value)
    {
        throw new ColumnsValueException('An incremental type column can not have a default value.');
    }
}
