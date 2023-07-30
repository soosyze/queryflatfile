<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

use Soosyze\Queryflatfile\Concern\Field\TryOrGetDate;
use Soosyze\Queryflatfile\Enum\CurentDefaultType;
use Soosyze\Queryflatfile\Enum\FieldType;
use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class DateTimeType extends Field
{
    use TryOrGetDate;

    public function __construct(readonly public string $name)
    {
        $this->currentDefault = CurentDefaultType::DateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): FieldType
    {
        return FieldType::DateTime;
    }
}
