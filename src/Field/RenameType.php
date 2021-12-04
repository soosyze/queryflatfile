<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile\Field;

use Queryflatfile\Field;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class RenameType extends Field
{
    protected $opt = self::OPT_RENAME;

    /**
     * @var string
     */
    protected $to;

    public function __construct(string $name, string $to)
    {
        parent::__construct($name);
        $this->to = $to;
    }

    /**
     * {@inheritdoc}
     */
    public function filterValue($value)
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
