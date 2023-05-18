<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class StringType extends TextType
{
    public const TYPE = 'string';

    protected int $length;

    public function __construct(string $name, int $length)
    {
        if ($length < 0) {
            throw new \InvalidArgumentException('The length passed in parameter is not of numeric type.');
        }
        parent::__construct($name);
        $this->length = $length;
    }

    /**
     * {@inheritdoc}
     */
    public function filterValue(null|bool|string|int|float $value): string
    {
        $str = parent::filterValue($value);

        if (strlen($str) > $this->length) {
            throw new \LengthException(
                sprintf(
                    'The value of the %s field must be less than or equal to %s characters: %s given',
                    $this->name,
                    $this->length,
                    strlen($str)
                )
            );
        }

        return $str;
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
