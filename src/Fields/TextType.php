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
final class TextType extends Field
{
    use ThrowInvalidType;

    /**
     * {@inheritdoc}
     */
    public function getType(): FieldType
    {
        return FieldType::Text;
    }

    /**
     * {@inheritdoc}
     */
    public function tryOrGetValue(mixed $value): string
    {
        if (!\is_string($value)) {
            $this->throwInvalidType($value);
        }

        return $value;
    }
}
