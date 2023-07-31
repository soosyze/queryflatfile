<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Commands;

use Soosyze\Queryflatfile\Command;
use Soosyze\Queryflatfile\Enums\TableExecutionType;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class DropCommand extends Command
{
    public function __construct(readonly public string $name)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionType(): TableExecutionType
    {
        return TableExecutionType::Drop;
    }
}
