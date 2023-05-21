<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Field;

use Soosyze\Queryflatfile\Enum\TableExecutionType;
use Soosyze\Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class DropType extends Field
{
    protected TableExecutionType $opt = TableExecutionType::Drop;

    public function tryOrGetValue(null|bool|string|int|float $value): null|bool|string|int|float
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
