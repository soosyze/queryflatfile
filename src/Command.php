<?php

declare(strict_types=1);
/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */
namespace Soosyze\Queryflatfile;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class Command
{
    public function __construct(readonly public string $name)
    {
    }
}
