# Queryflatfile

[![Build Status](https://travis-ci.org/soosyze/queryflatfile.svg?branch=master)](https://travis-ci.org/soosyze/queryflatfile "Travis")
[![Coverage Status](https://coveralls.io/repos/github/soosyze/queryflatfile/badge.svg?branch=master)](https://coveralls.io/github/soosyze/queryflatfile?branch=master "Coveralls")
[![GitHub](https://img.shields.io/github/license/mashape/apistatus.svg)](https://github.com/soosyze/queryflatfile/blob/master/LICENSE "LICENSE")
[![Packagist](https://img.shields.io/packagist/v/soosyze/queryflatfile.svg)](https://packagist.org/packages/soosyze/queryflatfile "Packagist")
[![PHP from Packagist](https://img.shields.io/packagist/php-v/soosyze/queryflatfile.svg)](/README.md#version-php "PHP version 5.4 minimum")
[![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/soosyze/queryflatfile.svg)](https://github.com/soosyze/queryflatfile/archive/master.zip "Download")

* :gb: [README in English](README.md)
* :fr: [README en Français](README_fr.md)

# About

Queryflatfile is a flat file database library written in PHP.
Stores your data by default in `JSON` format, also supports `txt`, `msgPack` and `igbinary` formats.
Manipulate your data with a QueryBuilder similar to SQL syntax.

# Sommaire
* [Requirements](/README.md#requirements)
* [Installation](/README.md#installation)
* [Simple example](/README.md#simple-exemple)
* [Methods](/README.md#methods)
* [Usage](https://github.com/soosyze/queryflatfile/blob/master/USAGE.md)

# Requirements

## PHP version

Support more than [85% of current PHP versions](https://w3techs.com/technologies/details/pl-php)

| Version PHP                 | QueryFlatFile 1.4.x |
|-----------------------------|---------------------|
| <= 5.4                      | ✗ Unsupported       |
| 5.5 / 5.6                   | ✓ Supported         |
| 7.0 / 7.1 / 7.2 / 7.3 / 7.4 | ✓ Supported         |

## Extensions

- `txt` for recording data with PHP serialize,
- `json` for recording data in JSON format,
- `msgpack` for recording data in binary.
- `igbinary` for recording data in binary.

## Memory required

The minimum amount of memory required depends on the amount of data you are going to process and the type of operations.
To gain performance use the PHP 7.x versions and the `MsgPack` or `Igbinary` driver.

## Permission of files and directory

Permission to write and read files in the directory that will store your data.

# Installation

## Composer

To install **QueryFlatFile** via Composer you must have the installer or the binary file [Composer](https://getcomposer.org/download/)

Go to your project directory, open a command prompt and run the following command:
```sh
composer require soosyze/queryflatfile --no-dev
```

Or, if you use the binary file,
```sh
php composer.phar require soosyze/queryflatfile --no-dev
```

# Simple example
```php
require __DIR__ . '/vendor/autoload.php';

use Queryflatfile\Schema;
use Queryflatfile\Request;
use Queryflatfile\TableBuilder;
use Queryflatfile\Driver\Json;

$bdd = new Schema('data', 'schema', new Json());
$req = new Request($bdd);

$bdd->createTableIfNotExists('user', function(TableBuilder $table){
    $table->increments('id')
          ->string('name')
          ->string('firstname')->nullable();
});

$req->insertInto('user', [ 'name', 'firstname' ])
    ->values([ 'NOEL', 'Mathieu' ])
    ->values([ 'DUPOND', 'Jean' ])
    ->values([ 'MARTIN', null ])
    ->execute();

$data = $req->select('id', 'name')
    ->from('user')
    ->where('firstname', '=', 'Jean')
    ->fetch();

print_r($data);

$bdd->dropTableIfExists('user');
```

The above example will output:
```
Array
(
    [id] => 2
    [name] => DUPOND
)
```

# Methods

**Schema**
- `dropSchema()`,
- `getSchema()`,
- `getSchemaTable( $table )`,
- `hasColumn( $table, $columns )`,
- `hasTable( $table )`,
- `setConfig( $host, $name = 'schema', DriverInterface $driver = null )`.

**Handling tables**
- `alterTable( $table, callable $callback )`,
- `createTable( $table, callable $callback = null )`,
- `createTableIfNotExists( $table, callable $callback = null )` :
  - `boolean( $name )`,
  - `char( $name, $length = 1)`,
  - `date( $name )`,
  - `dateTime( $name )`,
  - `float( $name )`,
  - `increments( $name )`,
  - `integer( $name )`,
  - `string( $name, $length = 255)`,
  - `text( $name )`.
- `dropTable( $table )`,
- `dropTableIfExists( $table )`,
- `truncateTable( $table )`.

**Selection request**
- `select( mixed $var [, mixed $... ] )`,
- `from( $table )`,
- `leftJoin( $table, callable|string $column, $condition = null, $value = null )`,
- `rightJoin( $table, callable|string $column, $condition = null, $value = null )`,
- `union( Request $union )`,
- `unionAll( Request $union )`,
- `orderBy( $columns, $order = SORT_DESC|SORT_ASC )`,
- `limit( $limit, $offset = 0 )`.

**Request for execution**
- `insertInto( $table, array $columns )`,
- `values( array $columns )`,
- `update( $table, array $columns )`,
- `delete()`.

**Result(s) of the query**
- `execute()` Performs the insertion, modification and deletion of data,
- `fetch()` Returns the first result of the query,
- `fetchAll()` Returns all the results of the query,
- `lists( $name, $key = null )` Returns a list of the column passed in parameter.

**Where**
- `where( callable|string $column, $condition = null, $value = null )`,
- `orWhere( callable|string $column, $condition = null, $value = null )`,
- `notWhere( callable|string $column, $condition = null, $value = null )`,
- `orNotWhere( callable|string $column, $condition = null, $value = null )`.

Supported conditions (===, ==, !=, <>, <, <=, >, >=, like, ilike, not like, not ilike)

**Where between**
- `between( $column, $min, $max )`,
- `orBetween( $column, $min, $max )`,
- `notBetween( $column, $min, $max )`,
- `orNotBetween( $column, $min, $max )`.

**Where in**
- `in( $column, array $values )`,
- `orIn( $column, array $values )`,
- `notIn( $column, array $values )`,
- `orNotIn( $column, array $values )`.

**Where isNull**
- `isNull( $column )`,
- `orIsNull( $column )`,
- `isNotNull( $column )`,
- `orIsNotNull( $column )`.

**Where regex**
- `regex( $column, $pattern)`,
- `orRegex( $column, $pattern )`,
- `notRegex( $column, $pattern )`,
- `orNotRegex( $column, $pattern )`.

# Usage

For examples of uses, refer to the [user documentation](https://github.com/soosyze/queryflatfile/blob/master/USAGE.md).