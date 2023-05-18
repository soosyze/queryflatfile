<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class IntType extends Field
{
    public const TYPE = 'integer';

    private bool $isUnsigned = false;

    /**
     * {@inheritdoc}
     */
    public function filterValue(null|bool|string|int|float $value): int
    {
        if (!\is_int($value)) {
            throw new \InvalidArgumentException(
                sprintf(self::INVALID_ARGUMENT_MESSAGE, $this->name, self::TYPE, gettype($value))
            );
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
