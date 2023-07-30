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
final class StringType extends Field
{
    use TryOrGetString;

    public function __construct(readonly public string $name, protected int $length)
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException('The length passed in parameter is not of numeric type.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): FieldType
    {
        return FieldType::String;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $data             = parent::toArray();
        $data[ 'length' ] = $this->length;

        return $data;
    }
}
