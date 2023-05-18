<?php

namespace Soosyze\Queryflatfile\Tests\architecture\Finalized;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Tests\architecture\BaseRule;

class DriverFinalized extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Driver');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Field\*'))
            ->should(new IsFinal())
            ->because('we want uniform naming');
    }
}
