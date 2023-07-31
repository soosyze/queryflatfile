<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Enums;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
enum FieldType: string
{
    case Boolean = 'boolean';
    case Char = 'char';
    case DateTime = 'datetime';
    case Date = 'date';
    case Float = 'float';
    case Increment = 'increments';
    case Int = 'integer';
    case String = 'string';
    case Text = 'text';

    private const TEXT_TYPES = [self::Text, self::String, self::Char];

    private const DATE_TYPES = [self::Date, self::DateTime, self::Int];

    private const NUMBER_TYPES = [self::Float, self::Increment, self::Int];

    public function realType(): string
    {
        return match ($this) {
            self::Boolean => 'boolean',
            self::Char => 'string',
            self::DateTime => 'string',
            self::Date => 'string',
            self::Float => 'float',
            self::Int => 'integer',
            self::Increment => 'integer',
            self::String => 'string',
            self::Text => 'string',
        };
    }

    public function isModify(FieldType $newType): bool
    {
        return match ($this) {
            self::Boolean => in_array($newType, [self::Boolean, ...self::TEXT_TYPES]),
            self::Char => in_array($newType, self::TEXT_TYPES),
            self::DateTime => in_array($newType, self::DATE_TYPES),
            self::Date => in_array($newType, self::DATE_TYPES),
            self::Float => in_array($newType, self::NUMBER_TYPES),
            self::Int => in_array($newType, self::NUMBER_TYPES),
            self::Increment => in_array($newType, self::NUMBER_TYPES),
            self::String => in_array($newType, self::TEXT_TYPES),
            self::Text => in_array($newType, self::TEXT_TYPES),
        };
    }
}
