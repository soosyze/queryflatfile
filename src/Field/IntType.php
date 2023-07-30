<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

use Soosyze\Queryflatfile\Concern\Field\ThrowInvalidType;
use Soosyze\Queryflatfile\Enum\FieldType;
use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class IntType extends Field
{
    use ThrowInvalidType;

    private bool $isUnsigned = false;

    /**
     * {@inheritdoc}
     */
    public function getType(): FieldType
    {
        return FieldType::Int;
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
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        if ($this->isUnsigned) {
            $data[ 'unsigned' ] = $this->isUnsigned;
        }

        return $data;
    }

    /**
     * Enregistre le champ (uniquement de type integer) comme étant non signié.
     *
     * @return $this
     */
    public function unsigned(): self
    {
        $this->isUnsigned = true;

        return $this;
    }
}
