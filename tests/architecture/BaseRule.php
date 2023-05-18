<?php

namespace Soosyze\Queryflatfile\Tests\architecture;

abstract class BaseRule implements RuleContract
{
    protected static string $path;

    public static function setPath(string $path): void
    {
        self::$path = $path;
    }
}
