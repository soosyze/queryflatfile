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
class RenameType extends Field
{
    protected string $opt = self::OPT_RENAME;

    public function __construct(string $name, protected string $to)
    {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    public function tryOrGetValue(null|bool|string|int|float $value): null|bool|string|int|float
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $data          = parent::toArray();
        $data[ 'opt' ] = $this->opt;
        $data[ 'to' ]  = $this->to;

        return $data;
    }
}
