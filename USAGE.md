# Usage

- [Usage](/USAGE.md#usage)
  - [Initialisation](/USAGE.md#initialisation)
  - [Create table](/USAGE.md#create-table)
  - [Alter table](/USAGE.md#alter-table)
  - [Inserts](/USAGE.md#inserts)
  - [Select](/USAGE.md#select)
  - [Where](/USAGE.md#where)
  - [Where group](/USAGE.md#where-group)
  - [Order, Limit & Offset](/USAGE.md#order-limit-offset)
  - [Joins](/USAGE.md#joins)
  - [Unions](/USAGE.md#unions)
  - [Updates](/USAGE.md#updates)
  - [Deletes](/USAGE.md#deletes)
  - [Drop table](/USAGE.md#drop-table)
  - [Drop database](/USAGE.md#drop-database)
- [Exception](/USAGE.md#exception)
- [Driver](/USAGE.md#driver)

## Initialisation

Pour commencer il faut créer un objet `Soosyze\Queryflatfile\Schema` pour manipuler les tables et leurs paramètres.

Requête au format SQL :

```sql
CREATE DATABASE schema
```

Requête en PHP avec QueryFlatFile :

```php
use Soosyze\Queryflatfile\Schema;
use Soosyze\Queryflatfile\Request;

// En ne renseignant pas le dernier paramètre les données seront stockées au format JSON par défaut
$sch = new Schema('data', 'schema');

// Ajout du schéma de base pour pouvoir réaliser des requêtes dessus
$req = new Request($sch);
```

## Create table

Maintenant que le schéma de votre base de données est initialisé vous devez créer les schémas de vos tables.

Requête au format SQL :

```sql
CREATE TABLE `user` (
    `id`        INT NOT NULL AUTO_INCREMENT,
    `name`      VARCHAR(100) NOT NULL,
    `firstname` VARCHAR(100)
);
CREATE TABLE `user_role` (
    `id_user`   INT NOT NULL,
    `id_role`   INT NOT NULL
);
CREATE TABLE `role` (
    `id`        INT NOT NULL AUTO_INCREMENT,
    `labelle`   VARCHAR(100) NOT NULL
);
```

Requête en PHP avec QueryFlatFile :

```php
$sch->createTable('user', function(Soosyze\Queryflatfile\TableBuilder $table) {
    $table->increments('id');
    $table->string('name', 100);
    $table->string('firstname', 100)->nullable();
});
$sch->createTable('user_role', function(Soosyze\Queryflatfile\TableBuilder $table) {
    $table->integer('id_user');
    $table->integer('id_role');
});
$sch->createTable('role', function(Soosyze\Queryflatfile\TableBuilder $table) {
    $table->increments('id');
    $table->string('labelle', 100);
});
```

### Types de données

| Type                              | Description                                                           |
| --------------------------------- | --------------------------------------------------------------------- |
| `$table->boolean('valid');`       | Colonne de type boolean.                                              |
| `$table->char('firstname', 80);`  | Colonne de type caractère avec une option pour la longueur.           |
| `$table->date('created');`        | Colonne de type date.                                                 |
| `$table->dateTime('created');`    | Colonne de type dateTime.                                             |
| `$table->float('cost');`          | Colonne de type nombre flottant.                                      |
| `$table->increments('id');`       | Colonne de type incrémentale.                                         |
| `$table->integer('weight');`      | Colonne de type nombre entier.                                        |
| `$table->string('libelle', 255);` | Colonne de type chaine de caractère avec une option pour la longueur. |
| `$table->text('description');`    | Colonne de type texte.                                                |

### Options des données

| Option                                | Description                                                                                                                                          |
| ------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| `->nullable();`                       | Autorise que le champ possède la valeur null.                                                                                                        |
| `->unsigned();`                       | Autorise que le champ (uniquement pour le type integer) soit non signé.                                                                              |
| `->valueDefault('N/A');`              | Donne une valeur par défaut au champ (attention au type de données).                                                                                 |
| `->valueDefault('current_date');`     | Pour un champ de type date le mot clé 'current_date' donne par défaut la date courante lors de l'insertion au format Y-m-d.                          |
| `->valueDefault('current_datetime');` | Pour un champ de type dateTime le mot clé 'current_datetime' donne par défaut la date et l'heure courante lors de l'insertion au format Y-m-d H:i:s. |
| `->comment( $comment );`              | Ajoute un commentaire à votre champ.                                                                                                                 |

## Alter table

Vous pouvez renommer, ajouter, modifier ou supprimer les colonnes des tables après leur création.

### Renommer une colonne

Requête au format SQL :

```sql
ALTER TABLE `user`
MODIFY `name` `other`
```

Requête en PHP avec QueryFlatFile :

```php
$sch->alterTable('user', function (TableAlter $table) {
    $table->renameColumn('name', 'other');
});
```

### Ajouter une colonne

Requête au format SQL :

```sql
ALTER TABLE `user`
ADD `country` VARCHAR(100)
```

Requête en PHP avec QueryFlatFile :

```php
$sch->alterTable('user', function (TableAlter $table) {
    $table->string('country', 100);
});
```

### Modifier une colonne

Requête au format SQL :

```sql
ALTER TABLE `user`
MODIFY `country` VARCHAR(255)
```

Requête en PHP avec QueryFlatFile :

```php
$sch->alterTable('user', function (TableAlter $table) {
    $table->string('country', 255)->modify();
});
```

### Supprimer une colonne

Requête au format SQL :

```sql
ALTER TABLE `user`
DROP `country`
```

Requête en PHP avec QueryFlatFile :

```php
$sch->alterTable('user', function (TableAlter $table) {
    $table->dropColumn('country');
});
```

## Inserts

Requête au format SQL :

```sql
INSERT INTO `user` (`id`, `name`, `firstname`) VALUES
    (0, 'NOEL', 'Mathieu'),
    (1, 'DUPOND', 'Jean'),
    (2, 'MARTIN', 'Manon'),
    (3, 'PETIT', 'Marie')
    (4, 'DUPOND', 'Pierre'),
    (5, 'MEYER', 'Eva'),
    (6, 'ROBERT', null);
INSERT INTO `user` (`id`, `labelle`) VALUES
    (0, 'Admin'),
    (1, 'Author'),
    (2, 'User');
INSERT INTO `user` (`id_user`, `id_role`) VALUES
    (0, 0),
    (1, 0),
    (2, 1),
    (3, 1)
    (1, 2),
    (2, 2),
    (3, 2);
```

Requête en PHP avec QueryFlatFile :

```php
$req->insertInto('user', [ 'id', 'name', 'firstname' ])
    ->values([ 0, 'NOEL', 'Mathieu' ])
    ->values([ 1, 'DUPOND', 'Jean' ])
    ->values([ 2, 'MARTIN', 'Manon' ])
    ->values([ 3, 'PETIT', 'Marie' ])
    ->values([ 4, 'DUPOND', 'Pierre' ])
    ->values([ 5, 'MEYER', 'Eva' ])
    ->values([ 6, 'ROBERT', null ])
    ->execute();
$req->insertInto('role', [ 'id', 'labelle' ])
    ->values([ 0, 'Admin' ])
    ->values([ 1, 'Author' ])
    ->values([ 2, 'User' ])
    ->execute();
$req->insertInto('user_role', [ 'id_user', 'id_role' ])
    ->values([ 0, 0 ])
    ->values([ 1, 0 ])
    ->values([ 2, 1 ])
    ->values([ 3, 1 ])
    ->values([ 4, 2 ])
    ->values([ 5, 2 ])
    ->values([ 6, 2 ])
    ->execute();
```

Table user :

| id  | name   | firstname |
| --- | ------ | --------- |
| 0   | NOEL   | Mathieu   |
| 1   | DUPOND | Jean      |
| 2   | MARTIN | Manon     |
| 3   | PETIT  | Marie     |
| 4   | DUPOND | Pierre    |
| 5   | MEYER  | Eva       |
| 6   | ROBERT | ''        |

Table role :

| id  | labelle |
| --- | ------- |
| 0   | Admin   |
| 1   | Author  |
| 2   | User    |

Table (pivot) user_role :

| id_user | id_role |
| ------- | ------- |
| 0       | 0       |
| 1       | 0       |
| 2       | 1       |
| 3       | 1       |
| 4       | 2       |
| 5       | 2       |
| 6       | 2       |

## Select

Requête au format SQL :

```sql
SELECT `firstname` FROM `user` LIMIT 1;
```

Requête en PHP avec QueryFlatFile :

```php
$req->select('firstname')->from('user')->fetch();
```

Résultat(s) de la requête :

| firstname |
| --------- |
| Mathieu   |

### Select all

Requête au format SQL :

```sql
SELECT * FROM `user` LIMIT 1;
```

Requête en PHP avec QueryFlatFile :

```php
// Une absence de sélection revient par défaut à retourner toutes les données de la ligne
$req->select()->from('user')->fetch();
$req->from('user')->fetch();
```

Résultat(s) de la requête :

| id  | name | firstname |
| --- | ---- | --------- |
| 0   | NOEL | Mathieu   |

### Lists

La fonction `lists( string $name, string $key = null );` contrairement à la fonction `fetch();` ou `fetchAll();` renvoie les résultats en listes en fonction du champ spécifié en premier paramètre.
Le second paramètre sert à spécifier les clés à associer aux valeurs.

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')->lists('firstname');
```

Résultat(s) de la requête :

| output  |
| ------- |
| Mathieu |
| Jean    |
| Manon   |
| Marie   |
| Pierre  |
| Eva     |
| ''      |

## Where

La fonction where standard peut-être utilisé avec les opérateurs suivants :

- `==` égal
- `===` ou `=` strictement égal (par valeur et type de données),
- `<>` ou `!=` différent de,
- `!==` strictement différent de (par valeur et type de données),
- `<` inférieur à,
- `<=` inférieur ou strictement égal à,
- `>` supérieur à,
- `>=` supérieur ou strictement égal à,
- `like` correspond au modèle (condition sensible à la case),
- `ilike` correspond au modèle (condition non sensible à la case),
- `not like` ne correspond pas au modèle (condition sensible à la case),
- `not ilike` ne correspond pas au modèle (condition non sensible à la case).

Les opérateurs de la fonction where sont insensibles à la case (`like` peut s'écrire `LIKE`, `Like`, `LiKe`, `LIke`...)

### Where equals

Requête au format SQL :

```sql
SELECT `name` FROM `user` WHERE `firstname` = 'Jean' LIMIT 1;
```

Requête en PHP avec QueryFlatFile :

```php
$req->select('name')
    ->from('user')
    ->where('firstname', '=', 'Jean')
    ->fetch();
```

Résultat(s) de la requête :

| name   |
| ------ |
| DUPOND |

### Where not equals

Requête au format SQL :

```sql
SELECT `firstname` FROM `user` WHERE `firstname` <> 'Jean';
```

Requête en PHP avec QueryFlatFile :

```php
$req->select('firstname')
    ->from('user')
    ->where('firstname', '<>', 'Jean')
    ->fetchAll();
```

Résultat(s) de la requête :

| firstname |
| --------- |
| Mathieu   |
| Manon     |
| Mathieu   |
| Marie     |
| Pierre    |
| Eva       |
| ''        |

### Where less

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `id` < 1 LIMIT 1;
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->where('id', '<', 1)
    ->fetch();
```

Résultat(s) de la requête :

| id  | name | firstname |
| --- | ---- | --------- |
| 0   | NOEL | Mathieu   |

### Where less or equals

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `id` <= 1;
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->where('id', '<=', 1)
    ->fetchAll();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 0   | NOEL   | Mathieu   |
| 1   | DUPOND | Jean      |

### Where greater

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `id` > 5 LIMIT 1;
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->where('id', '>', 5)
    ->fetch();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 6   | ROBERT | ''        |

### Where greater or equals

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `id` >= 5;
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->where('id', '>=', 5)
    ->fetchAll();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 5   | MEYER  | Eva       |
| 6   | ROBERT | ''        |

### Where and

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `name` = 'DUPOND' AND `firstname` = 'Pierre' LIMIT 1;
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->where('name', '=', 'DUPOND')
    ->where('firstname', '=', 'Pierre')
    ->fetch();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 4   | DUPOND | Pierre    |

### Where or

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `name` = 'DUPOND' OR `firstname` = 'Pierre';
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->where('name', '=', 'DUPOND')
    ->orWhere('firstname', '=', 'Pierre')
    ->fetchAll();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 0   | NOEL   | Mathieu   |
| 1   | DUPOND | Jean      |
| 4   | DUPOND | Pierre    |

### Where between / orBetween / notBetween / orNotBetween

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `id` BETWEEN 0 AND 2 LIMIT 1;
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->between('id', 0, 2)
    ->fetch();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 1   | DUPOND | Jean      |

### Where in / orIn / notIn / orNotIn

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `id` IN (0, 1);
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->in('id', [ 0, 1 ])
    ->fetchAll();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 0   | NOEL   | Mathieu   |
| 1   | DUPOND | Jean      |

### Where isNull / orIsNull / isNotNull / orIsNotNull

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `firstname` IS NULL LIMIT 1;
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->isNull('firstname')
    ->fetch();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 6   | ROBERT | ''        |

### Where regex / orRegex / notRegex / orNotRegex

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
        ->regex('name', '/^D.*/')
        ->fetchAll();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 1   | DUPOND | Jean      |
| 4   | DUPOND | Pierre    |

## Where group

Dans le cas où vous devez grouper vos conditions vous devez créer une fonction anonyme dans le premier paramètre de la fonction where.

### Where group AND

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `id` >= 2 AND (`name` = 'DUPOND' OR `firstname` = 'Eva');
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->where('id', '>=', 2)
    ->whereGroup(function(Where $query): void {
        $query->where('name', '=', 'DUPOND')
              ->orWhere('firstname', '=', 'Eva');
    })
    ->fetchAll();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 4   | DUPOND | Pierre    |
| 5   | MEYER  | Eva       |

### Where group OR

Requête au format SQL :

```sql
SELECT * FROM `user` WHERE `name` = 'DUPOND' OR ( `firstname` = 'Eva' OR `firstname` = 'Mathieu');
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->where('name', '=', 'DUPOND')
    ->orWhereGroup(function(Where $query): void {
        $query->where('firstname', '=', 'Eva')
              ->orWhere('firstname', '=', 'Mathieu');
    })
    ->fetchAll();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 0   | NOEL   | Mathieu   |
| 1   | DUPOND | Jean      |
| 4   | DUPOND | Pierre    |
| 5   | MEYER  | Eva       |

## Order, Limit, Offset

### OrderBy ASC

Requête au format SQL :

```sql
SELECT `firstname` FROM `user` ORDER BY `firstname` ASC;
```

Requête en PHP avec QueryFlatFile :

```php
$req->select('firstname')
    ->from('user')
    ->orderBy('firstname')
    ->fetchAll();
```

Résultat(s) de la requête :

| firstname |
| --------- |
| ''        |
| Eva       |
| Jean      |
| Manon     |
| Marie     |
| Mathieu   |
| Pierre    |

### OrderBy DESC

Requête au format SQL :

```sql
SELECT `firstname` FROM `user` ORDER BY `firstname` DESC;
```

Requête en PHP avec QueryFlatFile :

```php
$req->select('firstname')
    ->from('user')
    ->orderBy('firstname', SORT_DESC)
    ->fetchAll();
```

Résultat(s) de la requête :

| firstname |
| --------- |
| Pierre    |
| Mathieu   |
| Marie     |
| Manon     |
| Jean      |
| Eva       |
| ''        |

### OrderBy ASC (multiple)

Requête au format SQL :

```sql
SELECT `name`, `firstname` FROM `user` ORDER BY `name` DESC, `firstname` ASC;
```

Requête en PHP avec QueryFlatFile :

```php
$req->select('name', 'firstname')
    ->from('user')
    ->orderBy('name', SORT_DESC)
    ->orderBy('firstname')
    ->fetchAll();
```

Résultat(s) de la requête :

| name   | firstname |
| ------ | --------- |
| ROBERT | ''        |
| PETIT  | Marie     |
| NOEL   | Mathieu   |
| MEYER  | Eva       |
| MARTIN | Manon     |
| DUPOND | Jean      |
| DUPOND | Pierre    |

### OrderBy DESC (multiple)

Requête au format SQL :

```sql
SELECT `name` FROM `user` ORDER BY `name` DESC, `firstname` DESC;
```

Requête en PHP avec QueryFlatFile :

```php
$req->select('name', 'firstname')
    ->from('user')
    ->orderBy('name', SORT_DESC)
    ->orderBy('firstname', SORT_DESC)
    ->fetchAll();
```

Résultat(s) de la requête :

| name   | firstname |
| ------ | --------- |
| ROBERT | ''        |
| PETIT  | Marie     |
| NOEL   | Mathieu   |
| MEYER  | Eva       |
| MARTIN | Manon     |
| DUPOND | Pierre    |
| DUPOND | Jean      |

### Limit & Offset

La méthode `fetch()` retourne le premier résultat de la requête en forçant `$limite` à 1.
Vous pouvez également le définir avec à la méthode `limit( int $limit, int $offset = 0 )`.
À noter que si `$offset` est égale à 0 alors les données ne seront pas décalées.

Requête au format SQL :

```sql
SELECT * FROM `user` LIMIT 1 OFFSET 2;
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->limit(1, 2)
    ->fetchAll();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 2   | MARTIN | Manon     |

## Joins

```php
/**
 *
 * @param string          $tableName Nom de la table à joindre
 * @param string|\Closure $column    Nom de la colonne d'une des tables précédentes
 *                                   ou une closure pour affiner les conditions.
 * @param string          $operator  Opérateur logique.
 * @param string          $value     Colonne de la table jointe (au format nom_table.colonne).
 */
public function leftJoin( string $tableName, $column, string $operator = '', string $value = '' );
public function rightJoin( string $tableName, $column, string $operator = '', string $value = '' );
```

### Left join

Requête au format SQL :

```sql
SELECT `name`
FROM `user`
    LEFT JOIN `user_role` ON `id` = `user_role.id_user`
    LEFT JOIN `role` ON `id_role` = `role.id`
WHERE `labelle` = 'Admin';
```

Requête en PHP avec QueryFlatFile :

```php
$req->select('id', 'name', 'firstname')
    ->from('user')
    ->leftJoin('user_role', 'id', '=', 'user_role.id_user')
    ->leftJoin('role', 'id_role', '=', 'role.id')
    ->where('labelle', '=', 'Admin')
    ->fetchAll();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 0   | NOEL   | Mathieu   |
| 1   | DUPOND | Jean      |

### Right join

Requête au format SQL :

```sql
SELECT `name`
FROM `user`
    RIGHT JOIN `user_role` ON `id` = `user_role.id_user`
    RIGHT JOIN `role` ON `id_role` = `role.id`
WHERE `labelle` = 'Admin';
```

Requête en PHP avec QueryFlatFile :

```php
$req->select('id', 'name', 'firstname')
    ->from('user')
    ->rightJoin('user_role', 'id', '=', 'user_role.id_user')
    ->rightJoin('role', 'id_role', '=', 'role.id')
    ->where('labelle', '=', 'Admin')
    ->fetchAll();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 0   | NOEL   | Mathieu   |
| 1   | DUPOND | Jean      |

## Join clause multiple

Vous pouvez également introduire une fonction anonyme pour afiner les conditions de la jointure.

```php
$req->select('id', 'name', 'firstname')
    ->from('user')
    ->leftJoin('user_role', function(Where $query): void {
        $query->where('id', '=', 'user_role.id_user');
        // Ajout de condition dans la fonction
    })
    ->leftJoin('role', 'id_role', '=', 'role.id')
    ->where('labelle', '=', 'Admin')
    ->fetchAll();
```

## Unions

### Union

Requête au format SQL :

```sql
SELECT `name` FROM `user` WHERE `id` BETWEEN 1 AND 5
UNION
SELECT `name` FROM `user`;
```

Requête en PHP avec QueryFlatFile :

```php
$union = $req->select('name')
    ->from('user')
    ->between('id', 1, 5);
/* Une union est une opération entre 2 ensembles (soit 2 requêtes) */
$req2->select('name')
    ->from('user')
    ->union($union)
    ->fetchAll();
```

Résultat(s) de la requête :

| name   |
| ------ |
| NOEL   |
| DUPOND |
| MARTIN |
| PETIT  |
| MEYER  |
| ROBERT |

### Union All

Requête au format SQL :

```sql
SELECT `name` FROM `user` WHERE `id` BETWEEN 1 AND 5
UNION ALL
SELECT `name` FROM `user`;
```

Requête en PHP avec QueryFlatFile :

```php
$union = $req->select('name')
    ->from('user')
    ->between('id', 1, 5);
$req2->select('name')
    ->from('user')
    ->unionAll($union)
    ->fetchAll();
```

Résultat(s) de la requête :

| name   |
| ------ |
| NOEL   |
| DUPOND |
| MARTIN |
| PETIT  |
| DUPOND |
| MEYER  |
| ROBERT |
| DUPOND |
| MARTIN |
| PETIT  |
| DUPOND |
| MEYER  |

## Updates

Requête au format SQL :

```sql
UPDATE `user` SET `name` = 'PETIT' WHERE `id` = 0;
```

Requête avec Queryflatfile :

```php
$req->update('user', [ 'name'=>'PETIT' ])
    ->where('id', 0)
    ->execute();
```

Résultat(s) de la requête :

| id  | name  | firstname |
| --- | ----- | --------- |
| 0   | PETIT | Mathieu   |

## Deletes

Requête au format SQL :

```sql
DELETE FROM `user` WHERE `id` BETWEEN 0 AND 5;
```

Requête en PHP avec QueryFlatFile :

```php
$req->from('user')
    ->delete()
    ->between('id', 0, 5)
    ->execute();
```

Résultat(s) de la requête :

| id  | name   | firstname |
| --- | ------ | --------- |
| 0   | NOEL   | Mathieu   |
| 5   | MEYER  | Eva       |
| 6   | ROBERT | ''        |

## Drop table

Requête au format SQL :

```sql
DROP TABLE `user`;
DROP TABLE `user_role`;
DROP TABLE `role`;
```

Requête en PHP avec QueryFlatFile :

```php
$sch->dropTable('user');
$sch->dropTable('user_role');
$sch->dropTable('role');
```

## Drop database

Requête au format SQL :

```sql
DROP DATABASE `schema`;
```

Requête en PHP avec QueryFlatFile :

```php
$sch->dropSchema();
```

# Exception

Les exceptions sont ordonnées de façon à pouvoir capturer précisément les erreurs.

```
Exception
|-- QueryException                  // Exception relative à l'utilisation des requêtes et du schéma
|   |-- BadFunctionException        // levée lorsqu'une méthode attendue est absente
|   |-- ColumnsNotFoundException    // levée lorsque le champ ou les champs ne trouve pas de correspondance
|   |-- ColumnsValueException       // levée lors d'un mauvais type valeur pour le champ sélectionné
|   |-- OperatorNotFound            // levée lorsqu'un opérateur WHERE n'existe pas
|   +-- TableNotFoundException      // levée lorsqu'une table absente du schema
|
|-- TableBuilderException           // Exception relative à la construction d'une table
|   |-- ColumnsNotFoundException    // levée lorsque aucun champ est sélectionné
|   +-- ColumnsValueException       // levée lors d'un mauvais type valeur pour le champ sélectionné
|
+-- DriverException                 // Exception relative à l'utilisation du Driver
    |-- ExtensionNotLoadedException // levée lorsque l'extension du driver est non chargée
    |-- FileNotFoundException       // levée lorsque le fichier de stockage est absent
    |-- FileNotReadableException    // levée lorsque le fichier de stockage est non lisible
    +-- FileNotWritableException    // levée lorsque le fichier de stockage est non éditable
```

# Exemple

```php
try{
    $req->select(...$fields)->from('user')->fetch();
}
catch(\Soosyze\Queryflatfile\Exception\Query\ColumnsNotFoundException $e) {
    // Exception levée si une des valeurs contenues dans $fields ne correspond à aucun champ
}
catch(\Soosyze\Queryflatfile\Exception\Query\TableNotFoundException $e) {
    // Exception levée si $table user n'existe pas
}
catch(\Soosyze\Queryflatfile\Exception\Query\QueryException $e) {
     // Exception levée dans les 2 cas grâce à l'héritage
}
```

# Driver

Le driver permet l'abstraction de la manipulation des données.
Pour simplifier, votre schéma qui manipule vos tables ne se préoccupe pas du format des données.
Il appelle successivement les méthodes prédéfini et le driver s'occupe de renvoyer des données.

Ainsi en créant une class qui implémentant l'interface `Soosyze\Queryflatfile\DriverInterface`
vous pouvez personnaliser le type de fichier dans lequel vos données seront stockées.

Une fois votre propre driver créé, utiliser le à l'instanciation de votre base de données.

```php
$sch = new Soosyze\Queryflatfile\Schema();
$sch->setConfig('data', 'schema', new DriverFormatX());
```

Il existe une trois autres implementations :

- `Txt` enregistrent le données dans un fichier texte,
- `MsgPack` enregistrent le données en binaire grâce à l'extension `MsgPack`,
- `Igbinary` enregistrent le données en binaire grâce à l'extension `Igbinary`.
