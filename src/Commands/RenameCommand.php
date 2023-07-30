<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Commands;

use Soosyze\Queryflatfile\Command;
use Soosyze\Queryflatfile\Enum\TableExecutionType;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class RenameCommand extends Command
{
    public function __construct(public readonly string $name, public readonly string $to)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionType(): TableExecutionType
    {
        return TableExecutionType::Rename;
    }
}
