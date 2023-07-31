# Queryflatfile

[![Build Status](https://github.com/soosyze/queryflatfile/workflows/Tests/badge.svg?branch=master)](https://github.com/soosyze/queryflatfile/actions?query=branch:master "Tests")
[![Coverage Status](https://coveralls.io/repos/github/soosyze/queryflatfile/badge.svg?branch=master)](https://coveralls.io/github/soosyze/queryflatfile?branch=master "Coveralls")
[![GitHub](https://img.shields.io/github/license/mashape/apistatus.svg)](https://github.com/soosyze/queryflatfile/blob/master/LICENSE "LICENSE")
[![Packagist](https://img.shields.io/packagist/v/soosyze/queryflatfile.svg)](https://packagist.org/packages/soosyze/queryflatfile "Packagist")
[![PHP from Packagist](https://img.shields.io/packagist/php-v/soosyze/queryflatfile.svg)](/README.md#version-php "PHP version 8.1 minimum")
[![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/soosyze/queryflatfile.svg)](https://github.com/soosyze/queryflatfile/archive/master.zip "Download")

- :gb: [README in English](README.md)
- :fr: [README en Français](README_fr.md)

## À propos

Queryflatfile est une bibliothèque de base de données flat file écrit en PHP.
Stock vos données par défaut au format `JSON`, supporte aussi les formats `txt`, [msgPack](https://pecl.php.net/package/msgpack) et [igbinary](https://pecl.php.net/package/igbinary).
Manipulez vos données avec un QueryBuilder similaire à la syntaxe SQL.

## Sommaire

- [Exigences d'installation](/README_fr.md#exigences-dinstallation)
- [Installation](/README_fr.md#installation)
- [Exemple simple](/README_fr.md#exemple-simple)
- [Méthodes](/README_fr.md#méthodes)
- [Utilisation](/README_fr.md#utilisation)
- [License](/README_fr.md#licence)

## Exigences d'installation

### Version PHP

| Version PHP | QueryFlatFile 4.0.x |
| ----------- | ------------------- |
| <= 8.0      | ✗ Non supporté      |
|  8.1 / 8.2  | ✓ Supporté          |

### Extensions PHP

- `txt` pour l'enregistrement des données sérialiser,
- `json` pour l'enregistrement des données au format JSON,
- [msgPack](https://pecl.php.net/package/msgpack) pour l'enregistrement des données en binaire,
- [igbinary](https://pecl.php.net/package/igbinary) pour l'enregistrement des données en binaire.

### Mémoire requise

La quantité de mémoire minimum nécessaire dépend du volume de données que vous traiterez et du type d'opérations.
Pour gagner en performance utiliser le drivers `MsgPack` ou `Igbinary`.

### Permission des fichiers et répértoire

La permission d'écrire et lire les fichiers dans le répertoire qui stockera vos données.

## Installation

### Composer

Pour installer **Queryflatfile** via Composer il est faut avoir l’installateur ou le fichier binaire [Composer](https://getcomposer.org/download/)

Rendez-vous dans le répertoire de votre projet, ouvrez une invite de commandes et lancer la commande suivante :

```sh
composer require soosyze/queryflatfile
```

Ou, si vous utilisez le fichier binaire :

```sh
php composer.phar require soosyze/queryflatfile
```

## Exemple simple

```php
require __DIR__ . '/vendor/autoload.php';

use Soosyze\Queryflatfile\Schema;
use Soosyze\Queryflatfile\Request;
use Soosyze\Queryflatfile\TableBuilder;
use Soosyze\Queryflatfile\Drivers\Json;

$sch = new Schema('data', 'schema', new Json());
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

L'exemple ci-dessus va afficher :

```
Array
(
    [id] => 2
    [name] => DUPOND
)
```

## Méthodes

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

## Utilisation

Pour avoir des exemples d'utilisations référez-vous à la [documentation d'utilisation](/USAGE.md).

## Licence

Ce projet est sous [licence MIT](/LICENSE).
