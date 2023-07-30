<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

use Soosyze\Queryflatfile\Concerns\Field\ThrowInvalidType;
use Soosyze\Queryflatfile\Enum\FieldType;
use Soosyze\Queryflatfile\Exception\TableBuilder\ColumnsValueException;
use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class IncrementType extends Field
{
    use ThrowInvalidType;

    /**
     * {@inheritdoc}
     */
    public function getType(): FieldType
    {
        return FieldType::Increment;
    }

    /**
     * {@inheritdoc}
     */
    public function tryOrGetValue(mixed $value): int
    {
        if (!\is_int($value)) {
            $this->throwInvalidType($value);
        }

        return $value;
    }

    /**
     * @throws ColumnsValueException
     */
    public function getValueDefault(): never
    {
        throw new ColumnsValueException('An incremental type column can not have a default value.');
    }

    /**
     * @throws ColumnsValueException
     */
    public function valueDefault(mixed $value): never
    {
        throw new ColumnsValueException('An incremental type column can not have a default value.');
    }
}
