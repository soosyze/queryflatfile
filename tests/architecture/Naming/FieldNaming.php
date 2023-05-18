<?php

namespace Soosyze\Queryflatfile\Tests\architecture\Naming;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Tests\architecture\BaseRule;

class FieldNaming extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Field');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Field\*'))
            ->should(new HaveNameMatching('*Type'))
            ->because('we want uniform naming');
    }
}
