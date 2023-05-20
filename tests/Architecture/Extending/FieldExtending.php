<?php

namespace Soosyze\Queryflatfile\Tests\Architecture\Extending;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Soosyze\Queryflatfile\Field;
use Soosyze\Queryflatfile\Tests\Architecture\BaseRule;

class FieldExtending extends BaseRule
{
    public static function classSet(): ClassSet
    {
        return ClassSet::fromDir(self::$path . '/src/Field');
    }

    public static function rule(): ArchRule
    {
        return Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Soosyze\Queryflatfile\Field\*'))
            ->should(new Extend(Field::class))
            ->because('we want uniform naming');
    }
}
