# Queryflatfile

[![Build Status](https://travis-ci.org/soosyze/queryflatfile.svg?branch=master)](https://travis-ci.org/soosyze/queryflatfile "Travis")
[![Coverage Status](https://coveralls.io/repos/github/soosyze/queryflatfile/badge.svg?branch=master)](https://coveralls.io/github/soosyze/queryflatfile?branch=master "Coveralls")
[![GitHub](https://img.shields.io/github/license/mashape/apistatus.svg)](https://github.com/soosyze/queryflatfile/blob/master/LICENSE "LICENSE")
[![Packagist](https://img.shields.io/packagist/v/soosyze/queryflatfile.svg)](https://packagist.org/packages/soosyze/queryflatfile "Packagist")
[![PHP from Packagist](https://img.shields.io/packagist/php-v/soosyze/queryflatfile.svg)](/README.md#version-php "PHP version 5.4 minimum")
[![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/soosyze/queryflatfile.svg)](https://github.com/soosyze/queryflatfile/archive/master.zip "Download")

* :gb: [README in English](README.md)
* :fr: [README en Français](README_fr.md)

# À propos

Queryflatfile est une bibliothèque de base de données flat file écrit en PHP.
Stock vos données par défaut au format `JSON`, supporte aussi les formats `txt`, `msgPack` et `igbinary`.
Manipulez vos données avec un QueryBuilder similaire à la syntaxe SQL.

# Sommaire
* [Exigences d'installation](/README.md#exigences-dinstallation)
* [Installation](/README.md#installation)
* [Exemple simple](/README.md#exemple-simple)
* [Méthodes](/README.md#méthodes)
* [Utilisation](https://github.com/soosyze/queryflatfile/blob/master/USAGE.md)

# Exigences d'installation

## Version PHP

Support plus de [85% des versions PHP actuelles](https://w3techs.com/technologies/details/pl-php)

| Version PHP                 | QueryFlatFile 1.4.x |
|-----------------------------|---------------------|
| <= 5.4                      | ✗ Non supporté      |
| 5.5 / 5.6                   | ✓ Supporté          |
| 7.0 / 7.1 / 7.2 / 7.3 / 7.4 | ✓ Supporté          |

## Extensions PHP

- `txt` pour l'enregistrement des données sérialiser,
- `json` pour l'enregistrement des données au format JSON,
- `msgpack` pour l'enregistrement des données en binaire,
- `igbinary` pour l'enregistrement des données en binaire.

## Mémoire requise

 La quantité de mémoire minimum nécessaire dépend du volume de données que vous traiterez et du type d'opérations.
Pour gagner en performance utiliser les versions PHP 7.x et le driver `MsgPack` ou `Igbinary`.

## Permission des fichiers et répértoire

La permission d'écrire et lire les fichiers dans le répertoire qui stockera vos données.

# Installation

## Composer

Pour installer **QueryFlatFile** via Composer il est faut avoir l’installateur ou le fichier binaire [Composer](https://getcomposer.org/download/)

Rendez-vous dans le répertoire de votre projet, ouvrez une invite de commandes et lancer la commande suivante :
```sh
composer require soosyze/queryflatfile
```

Ou, si vous utilisez le fichier binaire :
```sh
php composer.phar require soosyze/queryflatfile
```

# Exemple simple
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

L'exemple ci-dessus va afficher :
```
Array
(
    [id] => 2
    [name] => DUPOND
)
```


# Méthodes

**Schéma**
- `dropSchema()`,
- `getSchema()`,
- `getSchemaTable( $table )`,
- `hasColumn( $table, $columns )`,
- `hasTable( $table )`,
- `setConfig( $host, $name = 'schema', DriverInterface $driver = null )`.

**Manipulation des tables**
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

**Requête de sélection**
- `select( mixed $var [, mixed $... ] )`,
- `from( $table )`,
- `leftJoin( $table, callable|string $column, $condition = null, $value = null )`,
- `rightJoin( $table, callable|string $column, $condition = null, $value = null )`,
- `union( Request $union )`,
- `unionAll( Request $union )`,
- `orderBy( $columns, $order = SORT_DESC|SORT_ASC )`,
- `limit( $limit, $offset = 0 )`.

**Requête d'exécution**
- `insertInto( $table, array $columns )`,
- `values( array $columns )`,
- `update( $table, array $columns )`,
- `delete()`.

**Résultat(s) de la requête**
- `execute()` Exécute l'insertion, la modification et la suppression de données,
- `fetch()` Renvoie le premier résultat de la requête,
- `fetchAll()` Renvoie tous les résultats de la requête,
- `lists( $name, $key = null )` Renvoie une liste de la colonne passée en paramètre.

**Where**
- `where( callable|string $column, $condition = null, $value = null )`,
- `orWhere( callable|string $column, $condition = null, $value = null )`,
- `notWhere( callable|string $column, $condition = null, $value = null )`,
- `orNotWhere( callable|string $column, $condition = null, $value = null )`.

Conditions supportées (===, ==, !=, <>, <, <=, >, >=, like, ilike, not like, not ilike)

**Where between**
- `between( $column, $min, $max )`,
- `orBetween( $column, $min, $max )`,
- `notBetween( $column, $min, $max )`,
- `orNotBetween( $column, $min, $max )`.

**Where in**
- `in( $column, callable|array $values )`,
- `orIn( $column, callable|array $values )`,
- `notIn( $column, callable|array $values )`,
- `orNotIn( $column, callable|array $values )`.

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

# Utilisation

Pour avoir des exemples d'utilisations référez-vous à la [documentation d'utilisation](https://github.com/soosyze/queryflatfile/blob/master/USAGE.md).