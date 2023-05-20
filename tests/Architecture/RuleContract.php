<?php

namespace Soosyze\Queryflatfile\Tests\Architecture;

use Arkitect\ClassSet;
use Arkitect\Rules\DSL\ArchRule;

interface RuleContract
{
    public static function classSet(): ClassSet;

    public static function rule(): ArchRule;
}
