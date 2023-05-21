<?php

namespace Soosyze\Queryflatfile\Tests\Architecture\Enum;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\IsEnum;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Tests\Architecture\BaseRule;

class Enuming extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Enum');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Enum\*'))
            ->should(new IsEnum())
            ->because('we want to be sure that all classes are enum');
    }
}
