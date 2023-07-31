<?php

namespace Soosyze\Queryflatfile\Tests\Architecture\Extending;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Exceptions\TableBuilder\TableBuilderException;
use Soosyze\Queryflatfile\Tests\Architecture\BaseRule;

class TableBuilderExceptionExtending extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Exceptions/TableBuilder');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->except(TableBuilderException::class)
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Exceptions\TableBuilder\*'))
            ->should(new Extend(TableBuilderException::class))
            ->because('we want uniform naming');
    }
}
