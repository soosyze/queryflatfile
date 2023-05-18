<?php

namespace Soosyze\Queryflatfile\Tests\architecture\Extending;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Exception\Query\QueryException;
use Soosyze\Queryflatfile\Tests\architecture\BaseRule;

class QueryExceptionExtending extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Exception/Query');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->except(QueryException::class)
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Exception\Query\*'))
            ->should(new Extend(QueryException::class))
            ->because('we want uniform naming');
    }
}
