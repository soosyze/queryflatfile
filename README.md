# Queryjson
QueryJson est un framework de base de données flat file basé sur le format JSON. 
L'objectif est de pouvoir manipuler des données contenues dans des fichiers JSON de la même façon dont ont manipule les données contenues en base de données.

# Sommaire
* Fonctions
* Initialisation
* Usage
  * CREATE TABLE
  * INSERT INTO
  * SELECT
    * SELECT ALTERNATIVE
  * WHERE
    * WHERE ALTERNATIVE
    * WHERE NOT EQUALS
    * WHERE LESS
    * WHERE LESS OR EQUALS
    * WHERE GREATER
    * WHERE GREATER OR EQUALS
    * WHERE BWETWEEN
    * WHERE IN
    * WHERE EMPTY
    * WHERE REGEX
    * WHERE AND
    * WHERE OR
  * LIMIT / OFFSET
  * ORDER
    * ASC
    * DESC
    * ASC (multiple)
    * DESC (multiple)
  * LEFT JOIN
  * UPDATE
  * DELETE
  * DROP TABLE
  * DROP DATABASE
 
# Fonctions
Les fonctionnalités fournies de base par l'objet Request pour construire la requête :
```php
public function select( array $columns );
public function from( $table );
public function leftJoin( $table, Where $w );
public function rightJoin( $table, Where $w );
public function where( Where $w );
//Renvoie un nouvelle objet Where
public function expr();
public function insertInto( $table, array $columns = null );
public function values( array $columns );
public function update( $table, array $columns = null );
public function delete();
public function limit( $limit, $offset = 0 );
public function orderBy( $columns, $order = 'asc' );
```
Les fonctions de base de l'objet Request pour exécuter la requête :
```php
// Revoie tous les résultats de la requête
public function fetchAll();
// Revoie le premier résultat de la requête
public function fetch();
// Exécute l'insertion, la modification et la suppression de données
public function execute();
```
Les fonctions de base de l'objet Where :
```php
// Valeur accepter de l'attribue $condition ('==', '!=', '<', '<=', '>', '>=' )
public function where( $column, $condition, $value = null );
public function bwetween( $column, $min, $max );
public function in( $column, array $values );
public function isEmpty( $column, $bool = true );
public function regex( $column, $pattern );
public function wAND();
public function wOR();
```
Les fonctions de base de l'objet Schema pour manipuler les tables :
```php
/**
 * Donne la configuration minimum au bon fonctionnement du Schema
 * @param String $driver le format de stockage des données (json)
 * @param String $host le repertoire de stockage des données
 * @param String $name le nom du fichier stockant le schema
 */
public function setConfig( $host, $name = 'schema', $driver = 'json' );
public function getSchema();
public function getSchemaTable( $table );
public function dropSchema();
public function createTable( $table, $callback = null );
public function truncateTable( $table );
public function dropTable( $table );
```
Les fonctions de base de l'objet TableBuilder pour manipuler les paramètres des tables :
```php
public function increments( $name );
public function char( $name );
public function text( $name );
public function string( $name, $length = 255 );
public function integer( $name );
public function float( $name );
public function boolean( $name );
public function dateTime( $name );
public function timestamps( $name );
```

# Initialisation
Pour commencer il faut créer un objet 'Schema' pour manipuler les tables et leurs paramètres.
Requête au format SQL :
```php
CREATE DATABASE schema
```
```php
use Queryjson;

$this->bdd = new Schema();
$this->bdd->setConfig('data', 'schema', 'json');

$this->request = new Request();
// Ajout du schéma de base pour pouvoir réaliser des requêtes dessus
$this->request->setSchema($this->bdd);
```

# Usage
## CREATE TABLE
Requête au format SQL :
```php
CREATE TABLE user
(
    id INT,
    name VARCHAR(100),
    firstname VARCHAR(100),
);
CREATE TABLE user_role
(
    id_user INT,
    id_role INT,
);
CREATE TABLE role
(
    id INT,
    labelle VARCHAR(100),
);
```
Requête avec QueryJson :
```php
$bdd->createTable('user', function(TableBuilder $table){
	return $table
	    ->increments('id')
		->string('name')
		->string('firstname');
    });
	$bdd->createTable('user_role', function(TableBuilder $table){
		return $table
		    ->integer('id_user')
			->integer('id_role');
	});
	$bdd->createTable('role', function(TableBuilder $table){
		return $table
		    ->increments('id')
	    	->string('labelle');
	});
```

## INSERT INTO
Requête au format SQL :
```sql
INSERT INTO `user` (`id`, `name`, `firstname`) VALUES
    (0, 'NOEL', 'Mathieu'),
    (1, 'DUPOND', 'Jean'),
    (2, 'MARTIN', 'Manon'),
    (3, 'PETIT', 'Marie')
    (1, 'DUPOND', 'Pierre'),
    (2, 'MEYER', 'Eva'),
    (3, 'ROBERT', '');
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
Requête avec QueryJson :
```php
$request->insertInto('user', [ 'id', 'name', 'firstname' ])
	->values([ 0, 'NOEL', 'Mathieu' ])
	->values([ 1, 'DUPOND', 'Jean' ])
	->values([ 2, 'MARTIN', 'Manon' ])
	->values([ 3, 'PETIT', 'Marie' ])
	->values([ 4, 'DUPOND', 'Pierre' ])
	->values([ 5, 'MEYER', 'Eva' ])
	->values([ 6, 'ROBERT', '' ])
	->execute();

$request->insertInto('role', [ 'id', 'labelle' ])
	->values([ 0, 'Admin' ])
    ->values([ 1, 'Author' ])
	->values([ 2, 'User' ])
	->execute();

$request->insertInto('user_role', [ 'id_user', 'id_role' ])
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
| id | name | firstname |
| ------ | ------ | ------ |
| 0 | NOEL | Mathieu |
| 1 | DUPOND | Jean |
| 2 | MARTIN | Manon |
| 3 | PETIT | Marie |
| 4 | DUPOND | Pierre |
| 5 | MEYER | Eva |
| 6 | ROBERT | '' |
Table role :
| id | labell |
| ------ | ------ |
| 0 | Admin |
| 1 | Author |
| 2 | User |
Table (pivot) user_role :
| id_user | id_role |
| ------ | ------ |
| 0 | 0 |
| 1 | 0 |
| 2 | 1 |
| 3 | 1 |
| 4 | 2 |
| 5 | 2 |
| 6 | 2 |

## SELECT / FROM
Requête au format SQL :
```sql
SELECT `firstname` 
FROM `user`;
```
Requête avec QueryJson :
```php
$request->select([ 'firstname' ])->from('user')->fetch();
```
Résultat(s) de la requête :
| firstname |
| ------ |
| Mathieu |

### SELECT ALTERNATIVE
Requête au format SQL :
```sql
SELECT * 
FROM `user`;
```
Requête avec QueryJson :
```php
// Toutes les colonnes sont selection avec une absence de selection
$request->select([])->from('user')->fetch();
$request->select()->from('user')->fetch();
$request->from('user')->fetch();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 0 | NOEL | Mathieu |

## WHERE
### WHERE EQUALS
Requête au format SQL :
```sql
SELECT `name` 
FROM `user` 
WHERE `firstname` = 'Jean';
```
Requête avec QueryJson :
```php
$request->select([ 'name' ])
	->from('user')
	->where($request->expr()->where('firstname', '==', 'Jean'))
	->fetch();
```
Résultat(s) de la requête :
| name |
| ------ |
| DUPOND |

### WHERE EQUALS ALTERNATIVE
Requête au format SQL :
```sql
SELECT `name` 
FROM `user` 
WHERE `firstname` = 'Jean';
```
Requête avec QueryJson :
```php
$request->select([ 'name' ])
	->from('user')
	->where($request->expr()->where('firstname', 'Jean'))
	->fetch();
```
Résultat(s) de la requête :
| name |
| ------ |
| DUPOND |

### WHERE NOT EQUALS
Requête au format SQL :
```sql
SELECT `firstname`
FROM `user` 
WHERE `firstname` != 'Jean';
```
Requête avec QueryJson :
```php
$request->select([ 'firstname' ])
	->from('user')
	->where($request->expr()->where('firstname', '!=', 'Jean'))
	->fetchAll();
```
Résultat(s) de la requête :
| firstname |
| ------ |
| Mathieu |
| Manon |
| Mathieu |
| Marie |
| Pierre |
| Eva |
| '' |

### WHERE LESS
Requête au format SQL :
```sql
SELECT * 
FROM `user` 
WHERE `id` <= 1;
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->where($request->expr()->where('id', '<', 1))
	->fetch();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 0 | NOEL | Mathieu |

### WHERE LESS OR EQUALS
Requête au format SQL :
```sql
SELECT * 
FROM `user`
WHERE `id` <= 1;
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->where($request->expr()->where('id', '<=', 1))
	->fetchAll();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 0 | NOEL | Mathieu |
| 1 | DUPOND | Jean |

### WHERE GREATER
Requête au format SQL :
```sql
SELECT * 
FROM `user` 
WHERE `id` > 5;
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->where($request->expr()->where('id', '>', 5))
	->fetch();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 6 | ROBERT | '' |

### WHERE GREATER OR EQUALS
Requête au format SQL :
```sql
SELECT * 
FROM `user` 
WHERE `id` >= 5;
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->where($request->expr()->where('id', '>=', 5))
	->fetchAll();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 5 | MEYER | Eva |
| 6 | ROBERT | '' |

### WHERE BWETWEEN
Requête au format SQL :
```sql
SELECT * 
FROM `user` 
WHERE `id` BWETWEEN 0 AND 2;
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->where($request->expr()->bwetween('id', 0, 2))
	->fetch();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 1 | DUPOND | Jean |

### WHERE IN
Requête au format SQL :
```sql
SELECT * 
FROM `user` 
WHERE `id` IN (0, 1);
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->where($request->expr()->in('id', [ 0, 1 ]))
	->fetchAll();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 0 | NOEL | Mathieu |
| 1 | DUPOND | Jean |

### WHERE EMPTY
Requête au format SQL :
```sql
SELECT * 
FROM `user` 
WHERE `firstname` IS NULL;
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->where($request->expr()->isEmpty('firstname'))
	->fetch();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 6 | ROBERT | '' |

### WHERE REGEX
Requête au format SQL :
```sql
SELECT * 
FROM `user` 
WHERE `firstname` LIKE '%D';
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->where($request->expr()->regex('name', '/^D/'))
	->fetchAll();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 1 | DUPOND | Jean |
| 4 | DUPOND | Pierre |

### WHERE AND
Requête au format SQL :
```sql
SELECT *
FROM `user`
WHERE `name` = 'DUPOND' AND `firstname` = 'Pierre';
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->where($request->expr()
		->where('name', 'DUPOND')
		->wAND()
		->where('firstname', 'Pierre'))
	->fetch();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 4 | DUPOND | Pierre |

### WHERE OR
Requête au format SQL :
```sql
SELECT *
FROM `user`
WHERE `name` = 'DUPOND' OR `firstname` = 'Pierre';
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->where($request->expr()
		->where('name', 'DUPOND')
		->wOR()
		->where('firstname', 'Pierre'))
	->fetchAll();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 0 | NOEL | Mathieu |
| 1 | DUPOND | Jean |
| 4 | DUPOND | Pierre |

## LIMIT / OFFSET
Requête au format SQL :
```sql
SELECT *
FROM `user`
LIMIT 1 OFFSET 2;
```
Requête avec QueryJson :
```php
$request->select([])
	->from('user')
	->limit(1, 2)
	->fetchAll();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 2 | MARTIN | Manon |

## ORDER
### ASC
Requête au format SQL :
```sql
SELECT * 
FROM `user`
OERDER BY `firstname` ASC;
```
Requête avec QueryJson :
```php
$request->select([ 'firstname' ])
	->from('user')
	->orderBy('firstname')
	->fetchAll();
```
Résultat(s) de la requête :
| firstname |
| ------ |
| '' |
| Eva |
| Jean |
| Manon |
| Marie |
| Mathieu |
| Pierre |

### DESC
Requête au format SQL :
```sql
SELECT `firstname`
FROM `user`
ORDER BY `firstname` DESC;
```
Requête avec QueryJson :
```php
$request->select([ 'firstname' ])
	->from('user')
    ->orderBy('firstname', 'desc')
	->fetchAll();
```
Résultat(s) de la requête :
| firstname |
| ------ |
| Pierre |
| Mathieu |
| Marie  |
| Manon |
| Jean |
| Eva |
| '' |

### ASC (multiple)
Requête au format SQL :
```sql
SELECT `name`, `firstname`
FROM `user`
ORDER BY `name` DESC, ORDER BY `firstname` ASC;
```
Requête avec QueryJson :
```php
$request->select([ 'name', 'firstname' ])
	->from('user')
	->orderBy('name', 'desc')
	->orderBy('firstname')
	->fetchAll();
```
Résultat(s) de la requête :
| name | firstname |
| ------ | ------ |
| ROBERT | '' |
| PETIT | Marie |
| NOEL | Mathieu |
| MEYER | Eva |
| MARTIN | Manon |
| DUPOND | Jean |
| DUPOND | Pierre |

### DESC (multiple)
Requête au format SQL :
```sql
SELECT `name`
FROM `user`
WHERE `firstname` = 'Jean';
```
Requête avec QueryJson :
```php
$request->select([ 'name', 'firstname' ])
	->from('user')
	->orderBy('name', 'desc')
	->orderBy('firstname', 'desc')
	->fetchAll();
```
Résultat(s) de la requête :
| name | firstname |
| ------ | ------ |
| ROBERT | '' |
| PETIT | Marie |
| NOEL | Mathieu |
| MEYER | Eva |
| MARTIN | Manon |
| DUPOND | Pierre |
| DUPOND | Jean |

## LEFT JOIN
Requête au format SQL :
```sql
SELECT `name`
FROM `user`
    LEFT JOIN `user_role` ON `id` = `user_role.id_user`
    LEFT JOIN `role` ON `id_role` = `role.id`
WHERE `labelle` = 'Admin';
```
Requête avec QueryJson :
```php
$request->select([ 'id', 'name', 'firstname' ])
	->from('user')
	->leftJoin('user_role', $request->expr()->where('id', '==', 'user_role.id_user'))
	->leftJoin('role', $request->expr()->where('id_role', '==', 'role.id'))
	->where($request->expr()->where('labelle', 'Admin'))
	->fetchAll();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 0 | NOEL | Mathieu |
| 1 | DUPOND | Jean |

## UPDATE
Requête au format SQL :
```sql
UPDATE `user` SET `name` = 'PETIT'
WHERE `id` = 0;
```
Requête avec QueryJson :
```php
$request->update('user', [ 'name'=>'PETIT' ])
	->where($request->expr()->where('id', '==', 0))
	->execute();
```
| id | name | firstname |
| ------ | ------ | ------ |
| 0 | PETIT | Mathieu |

## DELETE
Requête au format SQL :
```sql
DELETE FROM `user`
WHERE `id` BWETEEN 0 AND 5;
```
Requête avec QueryJson :
```php
$request->from('user')
	->delete()
	->where($request->expr()->bwetween('id', 0, 5))
	->execute();
```
Résultat(s) de la requête :
| id | name | firstname |
| ------ | ------ | ------ |
| 0 | NOEL | Mathieu |
| 5 | MEYER | Eva |
| 6 | ROBERT | '' |

## DROP TABLE
Requête au format SQL :
```sql
DROP TABLE `user`;
DROP TABLE `user_role`;
DROP TABLE `role`;
```
Requête avec QueryJson :
```php
$bdd->dropTable('user');
$bdd->dropTable('user_role');
$bdd->dropTable('role');
```

## DROP DATABASE
Requête au format SQL :
```sql
DROP DATABASE '`schema`;
```
Requête avec QueryJson :
```php
$bdd->dropSchema();
```
