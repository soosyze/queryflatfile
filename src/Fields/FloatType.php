<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Fields;

use Soosyze\Queryflatfile\Concerns\Field\ThrowInvalidType;
use Soosyze\Queryflatfile\Enums\FieldType;
use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class FloatType extends Field
{
    use ThrowInvalidType;

    /**
     * {@inheritdoc}
     */
    public function getType(): FieldType
    {
        return FieldType::Float;
    }

    /**
     * {@inheritdoc}
     */
    public function tryOrGetValue(mixed $value): float
    {
        if (!\is_float($value)) {
            $this->throwInvalidType($value);
        }

        return $value;
    }
}