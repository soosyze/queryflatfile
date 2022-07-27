# Queryflatfile

[![Build Status](https://github.com/soosyze/queryflatfile/workflows/Tests/badge.svg?branch=master)](https://github.com/soosyze/queryflatfile/actions?query=branch:master "Tests")
[![Coverage Status](https://coveralls.io/repos/github/soosyze/queryflatfile/badge.svg?branch=master)](https://coveralls.io/github/soosyze/queryflatfile?branch=master "Coveralls")
[![GitHub](https://img.shields.io/github/license/mashape/apistatus.svg)](https://github.com/soosyze/queryflatfile/blob/master/LICENSE "LICENSE")
[![Packagist](https://img.shields.io/packagist/v/soosyze/queryflatfile.svg)](https://packagist.org/packages/soosyze/queryflatfile "Packagist")
[![PHP from Packagist](https://img.shields.io/packagist/php-v/soosyze/queryflatfile.svg)](/README.md#version-php "PHP version 7.2 minimum")
[![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/soosyze/queryflatfile.svg)](https://github.com/soosyze/queryflatfile/archive/master.zip "Download")

- :gb: [README in English](README.md)
- :fr: [README en Français](README_fr.md)

## About

Queryflatfile is a flat file database library written in PHP.
Stores your data by default in `JSON` format, also supports `txt`, [msgPack](https://pecl.php.net/package/msgpack) and [igbinary](https://pecl.php.net/package/igbinary) formats.
Manipulate your data with a QueryBuilder similar to SQL syntax.

## Summary

- [Requirements](/README.md#requirements)
- [Installation](/README.md#installation)
- [Simple example](/README.md#simple-exemple)
- [Methods](/README.md#methods)
- [Usage](/README.md#usage)
- [License](/README.md#license)

## Requirements

### PHP version

| Version PHP     | QueryFlatFile 3.x |
| --------------- | ----------------- |
| <= 7.1          | ✗ Unsupported     |
| 7.2 / 7.3 / 7.4 | ✓ Supported       |
| 8.0 / 8.1       | ✓ Supported       |

### Extensions

- `txt` for recording data with PHP serialize,
- `json` for recording data in JSON format,
- [msgPack](https://pecl.php.net/package/msgpack) for recording data in binary.
- [igbinary](https://pecl.php.net/package/igbinary) for recording data in binary.

### Memory required

The minimum amount of memory required depends on the amount of data you are going to process and the type of operations.

### Permission of files and directory

Permission to write and read files in the directory that will store your data.

## Installation

### Composer

To install **Queryflatfile** via Composer you must have the installer or the binary file [Composer](https://getcomposer.org/download/)

Go to your project directory, open a command prompt and run the following command:

```sh
composer require soosyze/queryflatfile --no-dev
```

Or, if you use the binary file,

```sh
php composer.phar require soosyze/queryflatfile --no-dev
```

## Simple example

```php
require __DIR__ . '/vendor/autoload.php';

use Soosyze\Queryflatfile\Schema;
use Soosyze\Queryflatfile\Request;
use Soosyze\Queryflatfile\TableBuilder;
use Soosyze\Queryflatfile\Driver\Json;

$sch = new Schema(__DIR__ . 'data', 'schema', new Json());
$req = new Request($sch);

$sch->createTableIfNotExists('user', function(TableBuilder $table): void {
    $table->increments('id')
    $table->string('name')
    $table->string('firstname')->nullable();
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

$sch->dropTableIfExists('user');
```

The above example will output:

```
Array
(
    [id] => 2
    [name] => DUPOND
)
```

## Methods

**Schema**

- `dropSchema()`,
- `getIncrement( string $tableName )`,
- `getSchema()`,
- `getTableSchema( string $tableName )`,
- `hasColumn( string $tableName, $columnName )`,
- `hasTable( string $tableName )`,
- `setConfig( string $host, string $name = 'schema', DriverInterface $driver = null )`.

**Handling tables**

- `alterTable( string $tableName, callable $callback )`,
- `createTable( string $tableName, callable $callback = null )`,
- `createTableIfNotExists( string $tableName, callable $callback = null )` :
  - `boolean( string $name )`,
  - `char( string $name, $length = 1)`,
  - `date( string $name )`,
  - `dateTime( string $name )`,
  - `float( string $name )`,
  - `increments( string $name )`,
  - `integer( string $name )`,
  - `string( string $name, $length = 255)`,
  - `text( string $name )`.
- `dropTable( string $tableName )`,
- `dropTableIfExists( string $tableName )`,
- `truncateTable( string $tableName )`.

**Selection request**

- `select( string ...$columnNames )`,
- `from( string $tableName )`,
- `leftJoin( string $tableName, \Closure|string $column, string $condition = '', string $value = '' )`,
- `rightJoin( string $tableName, \Closure|string $column, string $condition = '', string $value = '' )`,
- `union( RequestInterface $union )`,
- `unionAll( RequestInterface $union )`,
- `orderBy( string $columnName, int $order = SORT_DESC|SORT_ASC )`,
- `limit( int $limit, int $offset = 0 )`.

**Request for execution**

- `insertInto( string $tableName, array $columnNames )`,
- `values( array $rowValues )`,
- `update( string $tableName, array $row )`,
- `delete()`,
- `execute()` Performs the insertion, modification and deletion of data.

**Result(s) of the query**

- `fetch(): array` Returns the first result of the query,
- `fetchAll(): array` Returns all the results of the query,
- `lists( string $columnName, string $key = null ): array` Returns a list of the column passed in parameter.

**Where**

- `where( string $columnName, string $condition, null|scalar $value )`,
- `orWhere( string $columnName, string $condition, null|scalar $value )`,
- `notWhere( string $columnName, string $condition, null|scalar $value )`,
- `orNotWhere( string $columnName, string $condition, null|scalar $value )`.

Supported conditions (===, ==, !=, <>, <, <=, >, >=, like, ilike, not like, not ilike)

**Where**

- `whereGroup( \Closure $columnName )`,
- `orWhereGroup( \Closure $columnName )`,
- `notWhereGroup( \Closure $columnName )`,
- `orNotWhereGroup( \Closure $columnName )`.

**Where between**

- `between( string $columnName, $min, $max )`,
- `orBetween( string $columnName, $min, $max )`,
- `notBetween( string $columnName, $min, $max )`,
- `orNotBetween( string $columnName, $min, $max )`.

**Where in**

- `in( string $columnName, array $values )`,
- `orIn( string $columnName, array $values )`,
- `notIn( string $columnName, array $values )`,
- `orNotIn( string $columnName, array $values )`.

**Where isNull**

- `isNull( string $columnName )`,
- `orIsNull( string $columnName )`,
- `isNotNull( string $columnName )`,
- `orIsNotNull( string $columnName )`.

**Where regex**

- `regex( string $columnName, string $pattern )`,
- `orRegex( string $columnName, string $pattern )`,
- `notRegex( string $columnName, string $pattern )`,
- `orNotRegex( string $columnName, string $pattern )`.

## Usage

For examples of uses, refer to the [user documentation](/USAGE.md).

## License

This project is licensed under the [MIT license](/LICENSE).
