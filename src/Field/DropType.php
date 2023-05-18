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
class DropType extends Field
{
    protected string $opt = self::OPT_DROP;

    public function filterValue(null|bool|string|int|float $value): null|bool|string|int|float
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $data          = parent::toArray();
        $data[ 'opt' ] = $this->opt;

        return $data;
    }
}
