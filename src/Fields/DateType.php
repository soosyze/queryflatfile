<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Fields;

use Soosyze\Queryflatfile\Concerns\Field\TryOrGetDate;
use Soosyze\Queryflatfile\Enums\CurentDefaultType;
use Soosyze\Queryflatfile\Enums\FieldType;
use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class DateType extends Field
{
    use TryOrGetDate;

    public function __construct(readonly public string $name)
    {
        $this->currentDefault = CurentDefaultType::Date;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): FieldType
    {
        return FieldType::Date;
    }
}
