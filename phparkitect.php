<?php

declare(strict_types=1);

use Arkitect\CLI\Config;
use Soosyze\Queryflatfile\Tests\Architecture\Extending\DriverExceptionExtending;
use Soosyze\Queryflatfile\Tests\Architecture\Extending\DriverExtending;
use Soosyze\Queryflatfile\Tests\Architecture\Extending\FieldExtending;
use Soosyze\Queryflatfile\Tests\Architecture\Extending\QueryExceptionExtending;
use Soosyze\Queryflatfile\Tests\Architecture\Extending\TableBuilderExceptionExtending;
use Soosyze\Queryflatfile\Tests\Architecture\Finalized\DriverFinalized;
use Soosyze\Queryflatfile\Tests\Architecture\Naming\DriverNaming;
use Soosyze\Queryflatfile\Tests\Architecture\Naming\ExceptionNaming;
use Soosyze\Queryflatfile\Tests\Architecture\Naming\FieldNaming;

return static function (Config $config): void {
    $rules = [
        'finalized' => [
            DriverFinalized::class,
        ],
        'naming' =>[
            DriverNaming::class,
            ExceptionNaming::class,
            FieldNaming::class,
        ],
        'extending' =>[
            DriverExceptionExtending::class,
            DriverExtending::class,
            FieldExtending::class,
            QueryExceptionExtending::class,
            TableBuilderExceptionExtending::class,
        ]
    ];

    $rules = new RecursiveIteratorIterator(new RecursiveArrayIterator($rules));

    foreach ($rules as $rule) {
        $rule::setPath(__DIR__);
        $config->add($rule::classSet(), $rule::rule());
    }
};
