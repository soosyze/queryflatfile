<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

use Soosyze\Queryflatfile\Concerns\Field\ThrowInvalidType;
use Soosyze\Queryflatfile\Enums\FieldType;
use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class BoolType extends Field
{
    use ThrowInvalidType;

    /**
     * {@inheritdoc}
     */
    public function getType(): FieldType
    {
        return FieldType::Boolean;
    }

    /**
     * {@inheritdoc}
     */
    public function tryOrGetValue(mixed $value): bool
    {
        if (!\is_bool($value)) {
            $this->throwInvalidType($value);
        }

        return $value;
    }
}
