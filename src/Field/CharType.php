<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

use Soosyze\Queryflatfile\Concerns\Field\TryOrGetString;
use Soosyze\Queryflatfile\Enum\FieldType;
use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class CharType extends Field
{
    use TryOrGetString;

    protected int $length = 1;

    /**
     * {@inheritdoc}
     */
    public function getType(): FieldType
    {
        return FieldType::Char;
    }
}
