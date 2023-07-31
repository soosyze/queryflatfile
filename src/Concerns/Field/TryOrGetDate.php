<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Concerns\Field;

use Soosyze\Queryflatfile\Enums\CurentDefaultType;
use Soosyze\Queryflatfile\Exception\TableBuilder\ColumnsValueException;

/**
 * @property string $name
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
trait TryOrGetDate
{
    use ThrowInvalidType;

    private CurentDefaultType $currentDefault;

    public function tryOrGetValue(mixed $value): string
    {
        if (!\is_string($value)) {
            $this->throwInvalidType($value);
        }

        /** @var string $value */
        if (strtolower($value) === $this->currentDefault->value) {
            return $this->currentDefault->value;
        }
        if (($timestamp = strtotime($value))) {
            return date($this->currentDefault->format(), $timestamp);
        }

        throw new ColumnsValueException(
            sprintf('The value of the %s field must be a valid date: %s given', $this->name, $value)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getValueDefault(): bool|float|int|string|null
    {
        if ($this->valueDefault !== null) {
            if ($this->valueDefault === $this->currentDefault->value) {
                return date($this->currentDefault->format(), time());
            }

            /* Si les variables magiques ne sont pas utilisé alors la vrais valeur par defaut est retourné. */
            return $this->valueDefault;
        }
        if ($this->isNullable) {
            return null;
        }

        throw new ColumnsValueException(
            sprintf('%s not nullable or not default.', $this->name)
        );
    }
}
