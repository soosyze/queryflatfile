<?php

namespace Soosyze\Queryflatfile\Tests\Architecture\Naming;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Exceptions\Driver\DriverException;
use Soosyze\Queryflatfile\Tests\Architecture\BaseRule;

class ExceptionNaming extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Exceptions');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Exceptions\*'))
            ->should(new HaveNameMatching('*Exception'))
            ->because('we want uniform naming');
    }
}
