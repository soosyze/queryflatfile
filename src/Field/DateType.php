<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class DateType extends DateTimeType
{
    public const CURRENT_DEFAULT = 'current_date';

    public const TYPE = 'date';

    protected const FORMAT = 'Y-m-d';
}
