<?php

namespace Soosyze\Queryflatfile\Tests\architecture\Naming;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Driver;
use Soosyze\Queryflatfile\Tests\architecture\BaseRule;

class DriverNaming extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Driver');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Driver\*'))
            ->should(new Extend(Driver::class))
            ->because('we want protect our domain');
    }
}
