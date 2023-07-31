<?php

namespace Soosyze\Queryflatfile\Tests\Architecture\Naming;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Driver;
use Soosyze\Queryflatfile\Tests\Architecture\BaseRule;

class DriverNaming extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Drivers');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Drivers\*'))
            ->should(new Extend(Driver::class))
            ->because('we want protect our domain');
    }
}
