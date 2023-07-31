<?php

namespace Soosyze\Queryflatfile\Tests\Architecture\Extending;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Exceptions\Query\QueryException;
use Soosyze\Queryflatfile\Tests\Architecture\BaseRule;

class QueryExceptionExtending extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Exceptions/Query');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->except(QueryException::class)
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Exceptions\Query\*'))
            ->should(new Extend(QueryException::class))
            ->because('we want uniform naming');
    }
}
