<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

use Soosyze\Queryflatfile\Exception\TableBuilder\ColumnsValueException;
use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class DateTimeType extends Field
{
    public const CURRENT_DEFAULT = 'current_datetime';

    public const TYPE = 'datetime';

    protected const FORMAT = 'Y-m-d H:i:s';

    /**
     * {@inheritdoc}
     */
    public function tryOrGetValue(null|bool|string|int|float $value): string
    {
        if (!\is_string($value)) {
            throw new \InvalidArgumentException(
                sprintf(self::INVALID_ARGUMENT_MESSAGE, $this->name, 'string', gettype($value))
            );
        }
        if (strtolower($value) === static::CURRENT_DEFAULT) {
            return static::CURRENT_DEFAULT;
        }
        if (($timestamp = strtotime($value))) {
            return date(static::FORMAT, $timestamp);
        }

        throw new ColumnsValueException(
            sprintf('The value of the %s field must be a valid date: %s given', $this->name, $value)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getValueDefault(): null|bool|string|int|float
    {
        if ($this->valueDefault !== null) {
            if ($this->valueDefault === static::CURRENT_DEFAULT) {
                return date(static::FORMAT, time());
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
