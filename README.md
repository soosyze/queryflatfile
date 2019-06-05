# Queryflatfile

[![Build Status](https://travis-ci.org/soosyze/queryflatfile.svg?branch=master)](https://travis-ci.org/soosyze/queryflatfile "Travis")
[![Coverage Status](https://coveralls.io/repos/github/soosyze/queryflatfile/badge.svg?branch=master)](https://coveralls.io/github/soosyze/queryflatfile?branch=master "Coveralls")
[![GitHub](https://img.shields.io/github/license/mashape/apistatus.svg)](https://github.com/soosyze/queryflatfile/blob/master/LICENSE "LICENSE")
[![Packagist](https://img.shields.io/packagist/v/soosyze/queryflatfile.svg)](https://packagist.org/packages/soosyze/queryflatfile "Packagist")
[![PHP from Packagist](https://img.shields.io/packagist/php-v/soosyze/queryflatfile.svg)](/README.md#version-php "PHP version 5.4 minimum")
[![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/soosyze/queryflatfile.svg)](https://github.com/soosyze/queryflatfile/archive/master.zip "Download")

Queryflatfile est un framework de base de données NoSQL écrit en PHP qui stocke les données par défaut au format JSON (flat file). L'objectif est de pouvoir manipuler des données contenues dans des fichiers de la même façon dont ont manipule les données avec le langage SQL.

# Sommaire
* [Requirements](/README.md#requirements)
* [Installation](/README.md#installation)
* [Fonctions](/README.md#fonctions)
* [Usage](/USAGE.md#usage)
  * [Initialisation](/USAGE.md#initialisation)
  * [Create table](/USAGE.md#create-table)
  * [Alter table](/USAGE.md#alter-table)
  * [Inserts](/USAGE.md#inserts)
  * [Select](/USAGE.md#select)
  * [Where](/USAGE.md#where)
  * [Where group](/USAGE.md#where-group)
  * [Order, Limit & Offset](/USAGE.md#order-limit-offset)
  * [Joins](/USAGE.md#joins)
  * [Unions](/USAGE.md#unions)
  * [Updates](/USAGE.md#updates)
  * [Deletes](/USAGE.md#deletes)
  * [Drop table](/USAGE.md#drop-table)
  * [Drop database](/USAGE.md#drop-database)
* [Exception](/USAGE.md#exception)
* [Driver](/USAGE.md#driver)

# Requirements

## Version PHP

| Version PHP                | QueryFlatFile 1.x    |
|----------------------------|----------------------|
| <= 5.3                     | ✗ Non supporté       |
| 5.4 / 5.5 / 5.6            | ✓ Supporté           |
| 7.0 / 7.1 / 7.2 / 7.3.0RC3 | ✓ Supporté           |

## Extensions

`json` pour l'enregistrement des données (si vous utilisez le driver Json).

## Mémoire requise

La quantité de mémoire minimum nécessaire dépend du volume de données que vous traiterez. 
En choisissant les versions PHP 7.x vous aurez un gain de performance sur la mémoire et le temps d'exécution de 30% à 45% selon les opérations (select/insert/update/delete).

## Permission des fichiers et répértoire

La permission d'écrire et lire les fichiers dans le répertoire choisi pour stocker vos données.

# Installation

## Composer

Vous pouvez utiliser [Composer](https://getcomposer.org/) pour l'installation avec la commande suivante :
```sh
composer require soosyze/queryflatfile
```

Ou, si vous utilisez le PHAR (assurez-vous que l'exécutable php.exe est dans votre PATH):
```sh
php composer.phar require soosyze/queryflatfile
```
# Fonctions

Les fonctions du Schéma pour manipuler les tables
```php
# Queryflatfile\Schema

$bdd->setConfig( $host, $name = 'schema', Queryflatfile\DriverInterface $driver = null );
$bdd->getSchema();
$bdd->getSchemaTable( $table );
$bdd->dropSchema();
$bdd->hasTable( $table );
$bdd->hasColumn( $table, $columns );
/* Manipulation des tables */
$bdd->createTable( $table, callable $callback = null );
$bdd->createTableIfNotExists( $table, callable $callback = null );
$bdd->alterTable( $table, callable $callback );
$bdd->truncateTable( $table );
$bdd->dropTable( $table );
$bdd->dropTableIfExists( $table );
```

Les fonctionnalitées del'objet Request pour construire la requête
```php
# Queryflatfile\Request

// La fonction select( mixed $var [, mixed $... ] ) accepte une suite de valeur et/ou de tableau
$request->insertInto( $table, array $columns = null );
$request->select( mixed $var [, mixed $... ] );
$request->from( $table );
$request->limit( $limit, $offset = 0 );
$request->orderBy( $columns, $order = 'asc' );
$request->leftJoin( $table, callable|$column, $condition = null, $value = null );
$request->rightJoin( $table, callable|$column, $condition = null, $value = null );
$request->union( Queryflatfile\Request $union );
$request->unionAll( Queryflatfile\Request $union );
$request->update( $table, array $columns = null );
$request->values( array $columns );
$request->delete();
```

Les fonctionnalitées de Request pour conditionner les données
```php
# Queryflatfile\Where

// condition (===, ==, !=, <>, <, <=, >, >=, like, ilike, not like, not ilike)
$request->where( callable|$column, $condition = null, $value = null, $bool = 'and', $not = false );
$request->orWhere( callable|$column, $condition = null, $value = null );
// NOT WHERE
$request->notWhere( callable|$column, $condition = null, $value = null );
$request->orNotWhere( callable|$column, $condition = null, $value = null );
// WHERE BETWEEN
$request->between( $column, $min, $max, $bool = 'and', $not = false );
$request->orBetween( $column, $min, $max );
$request->notBetween( $column, $min, $max );
$request->orNotBetween( $column, $min, $max );
// WHERE IN
$request->in( $column, callable|array $values, $bool = 'and', $not = false );
$request->orIn( $column, callable|array $values );
$request->notIn( $column, callable|array $values );
$request->orNotIn( $column, callable|array $values );
// WHERE NULL
$request->isNull( $column, $condition = '===', $bool = 'and', $not = false );
$request->orIsNull( $column );
$request->isNotNull( $column );
$request->orIsNotNull( $column );
// WHERE REGEX
$request->regex( $column, $pattern, $bool = 'and', $not = false );
$request->orRegex( $column, $pattern );
$request->notRegex( $column, $pattern );
$request->orNotRegex( $column, $pattern );
```

Les fonctions de Request pour executer la requête :
```php
# Queryflatfile\Request

// Renvoie tous les résultats de la requête
$request->fetchAll();
// Renvoie le premier résultat de la requête
$request->fetch();
// Renvoie une liste de la colonne passée en paramètre
$request->lists( $name = null );
// Exécute l'insertion, la modification et la suppression de données
$request->execute();
```

# Usage

Pour avoir des exemples d'utilisations référez-vous à la [documentation d'utilisation](https://github.com/soosyze/queryflatfile/blob/master/USAGE.md).