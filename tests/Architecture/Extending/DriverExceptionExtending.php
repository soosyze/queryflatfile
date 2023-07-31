<?php

namespace Soosyze\Queryflatfile\Tests\Architecture\Extending;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Exceptions\Driver\DriverException;
use Soosyze\Queryflatfile\Tests\Architecture\BaseRule;

class DriverExceptionExtending extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Exceptions/Driver');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->except(DriverException::class)
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Exceptions\Diver\*'))
            ->should(new Extend(DriverException::class))
            ->because('we want uniform naming');
    }
}
