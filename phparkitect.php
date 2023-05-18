<?php

declare(strict_types=1);

use Arkitect\CLI\Config;
use Soosyze\Queryflatfile\Tests\architecture\Extending\DriverExceptionExtending;
use Soosyze\Queryflatfile\Tests\architecture\Extending\DriverExtending;
use Soosyze\Queryflatfile\Tests\architecture\Extending\FieldExtending;
use Soosyze\Queryflatfile\Tests\architecture\Extending\QueryExceptionExtending;
use Soosyze\Queryflatfile\Tests\architecture\Extending\TableBuilderExceptionExtending;
use Soosyze\Queryflatfile\Tests\architecture\Finalized\DriverFinalized;
use Soosyze\Queryflatfile\Tests\architecture\Naming\DriverNaming;
use Soosyze\Queryflatfile\Tests\architecture\Naming\ExceptionNaming;
use Soosyze\Queryflatfile\Tests\architecture\Naming\FieldNaming;

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
