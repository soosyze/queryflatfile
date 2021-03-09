<?php

/**
 * Queryflatfile
 *
 * @package Queryflatfile

 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\DriverInterface;
use Queryflatfile\Exception\Exception;
use Queryflatfile\Exception\Query\ColumnsValueException;
use Queryflatfile\Exception\Query\TableNotFoundException;
use Queryflatfile\Exception\TableBuilder\ColumnsNotFoundException;
use Queryflatfile\TableBuilder;

/**
 * Pattern fluent pour la gestion d'un schéma de données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class Schema
{
    /**
     * Format de la base de données.
     *
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Répertoire de stockage.
     *
     * @var string
     */
    protected $path;

    /**
     * La racine pour le répertoire de stockage.
     *
     * @var string
     */
    protected $root = '';

    /**
     * Nom du schéma.
     *
     * @var string
     */
    protected $name;

    /**
     * Chemin, nom et extension du schéma.
     *
     * @var string
     */
    protected $file;

    /**
     * Schéma des tables.
     *
     * @var array
     */
    protected $schema = [];

    /**
     * Construis l'objet avec une configuration.
     *
     * @param string          $host   Répertoire de stockage des données.
     * @param string          $name   Nom du fichier contenant le schéma de base.
     * @param DriverInterface $driver Interface de manipulation de données.
     */
    public function __construct(
    $host = null,
        $name = 'schema',
        DriverInterface $driver = null
    ) {
        if (!\is_null($host)) {
            $this->setConfig($host, $name, $driver);
        }
    }

    /**
     * Enregistre la configuration.
     *
     * @param string               $host   Répertoire de stockage des données.
     * @param string               $name   Nom du fichier contenant le schéma de base.
     * @param DriverInterface|null $driver Interface de manipulation de données.
     *
     * @return $this
     */
    public function setConfig(
        $host,
        $name = 'schema',
        DriverInterface $driver = null
    ) {
        $this->driver = $driver === null
            ? new Driver\Json()
            : $driver;
        $this->path   = $host;
        $this->name   = $name;

        return $this;
    }

    /**
     * Retourne le chemin relatif au répertoire de stockage.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Ajoute la racine du répertoire de stockage.
     *
     * @param string $root
     *
     * @return $this
     */
    public function setPathRoot($root = '')
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Modifie la valeur incrémentale d'une table.
     *
     * @param string $table      Nom de la table.
     * @param int    $increments Tableau associatif des valeurs incrémentales.
     *
     * @throws TableNotFoundException
     * @throws Exception
     *
     * @return bool Si le schéma d'incrémentaion est bien enregistré.
     */
    public function setIncrements($table, $increments)
    {
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException("Table $table is not exist.");
        }

        if (!isset($this->schema[ $table ][ 'increments' ])) {
            throw new Exception("Table $table does not have an incremental value.");
        }

        $this->schema[ $table ][ 'increments' ] = $increments;

        return $this->save($this->name, $this->schema);
    }

    /**
     * Retourne la valeur incrémentale d'une table.
     *
     * @param string $table Nom de la table.
     *
     * @throws TableNotFoundException
     * @throws Exception
     *
     * @return int
     */
    public function getIncrement($table)
    {
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException("Table $table is not exist.");
        }

        if ($this->schema[ $table ][ 'increments' ] === null) {
            throw new Exception("Table $table does not have an incremental value.");
        }

        return $this->schema[ $table ][ 'increments' ];
    }

    /**
     * Génère le schéma s'il n'existe pas en fonction du fichier de configuration.
     *
     * @return array Schéma de la base de données.
     */
    public function getSchema()
    {
        if ($this->schema) {
            return $this->schema;
        }
        $this->create($this->name);
        $this->schema = $this->read($this->name);

        return $this->schema;
    }

    /**
     * Cherche le schéma de la table passée en paramètre.
     *
     * @param string $table Nom de la table.
     *
     * @throws TableNotFoundException
     *
     * @return array Schéma de la table.
     */
    public function getSchemaTable($table)
    {
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException("The $table table is missing in the schema.");
        }

        return $this->getSchema()[ $table ];
    }

    /**
     * Supprime le schéma courant des données.
     *
     * @return $this
     */
    public function dropSchema()
    {
        $schema = $this->getSchema();

        /* Supprime les fichiers des tables. */
        foreach (array_keys($schema) as $table) {
            $this->delete($table);
        }

        /* Supprime le fichier de schéma. */
        $this->delete($this->name);

        /*
         * Dans le cas ou le répertoire utilisé contient d'autre fichier
         * (Si le répertoire contient que les 2 élements '.' et '..')
         * alors nous le supprimons.
         */
        $dir = scandir($this->root . $this->path);
        if ($dir !== false && count($dir) == 2) {
            rmdir($this->root . $this->path);
        }

        return $this;
    }

    /**
     * Créer une référence dans le schéma et le fichier de la table.
     *
     * @param string        $table    Nom de la table.
     * @param callable|null $callback fonction(TableBuilder $table) pour créer les champs.
     *
     * @throws Exception
     *
     * @return $this
     */
    public function createTable($table, callable $callback = null)
    {
        if ($this->hasTable($table)) {
            throw new Exception("Table $table exist.");
        }

        $this->schema[ $table ] = [ 'fields' => null, 'increments' => null ];
        if ($callback !== null) {
            $builder                = self::tableBuilder($callback);
            $this->schema[ $table ] = [
                'fields'     => $builder->build(),
                'increments' => $builder->getIncrement()
            ];
        }
        $this->save($this->name, $this->schema);
        $this->create($table);

        return $this;
    }

    /**
     * Créer une référence dans le schéma et un fichier de données si ceux si n'existe pas.
     *
     * @param string        $table    Nom de la table.
     * @param callable|null $callback fonction(TableBuilder $table) pour créer les champs.
     *
     * @return $this
     */
    public function createTableIfNotExists($table, callable $callback = null)
    {
        /* Créer la table si elle n'existe pas dans le schéma. */
        if (!$this->hasTable($table)) {
            $this->createTable($table, $callback);
        } elseif (!$this->driver->has($this->root . $this->path, $table)) {
            /* Si elle existe dans le schéma et que le fichier est absent alors on le créer. */
            $this->create($table);
        }

        return $this;
    }

    /**
     * Modifie les champs du schéma de données.
     *
     * @param string   $table    Nom de la table.
     * @param callable $callback fonction(TableBuilder $table) pour créer les champs.
     *
     * @throws TableNotFoundException
     * @throws Exception
     *
     * @return $this
     */
    public function alterTable($table, callable $callback)
    {
        if (!$this->hasTable($table)) {
            throw new Exception("Table $table table is missing in the schema.");
        }

        $tableBuilder = self::tableBuilder($callback)->buildFull();
        $dataTable    = $this->read($table);
        $fields       = $this->schema[ $table ][ 'fields' ];

        foreach ($tableBuilder as $name => $param) {
            self::filterField($table, $param, $fields, $name);
            if (!isset($param[ 'opt' ])) {
                self::add($fields, $dataTable, $name, $param);
            } elseif ($param[ 'opt' ] === 'rename') {
                self::rename($fields, $dataTable, $name, $param[ 'to' ]);
            } elseif ($param[ 'opt' ] === 'modify') {
                self::modify($fields, $dataTable, $name, $param);
            } elseif ($param[ 'opt' ] === 'drop') {
                self::drop($fields, $dataTable, $name);
            }
        }
        $this->schema[ $table ][ 'fields' ] = $fields;
        $this->save($this->name, $this->schema);
        $this->save($table, $dataTable);

        return $this;
    }

    /**
     * Retourne la valeur par defaut du champ passé en paramêtre.
     *
     * @param string $field Nom du champ.
     * @param array  $arg   Différente configurations.
     *
     * @throws ColumnsValueException
     *
     * @return mixed Valeur par defaut.
     */
    public static function getValueDefault($field, $arg)
    {
        if (isset($arg[ 'default' ])) {
            if ($arg[ 'type' ] === 'date' && $arg[ 'default' ] === 'current_date') {
                return date('Y-m-d', time());
            }
            if ($arg[ 'type' ] === 'datetime' && $arg[ 'default' ] === 'current_datetime') {
                return date('Y-m-d H:i:s', time());
            }

            /* Si les variables magiques ne sont pas utilisé alors la vrais valeur par defaut est retourné. */
            return $arg[ 'default' ];
        }
        if (isset($arg[ 'nullable' ])) {
            return null;
        }

        throw new ColumnsValueException("$field not nullable or not default.");
    }

    /**
     * Détermine une table existe.
     *
     * @param string $table Nom de la table.
     *
     * @return bool Si le schéma de référence et le fichier de données existent.
     */
    public function hasTable($table)
    {
        return isset($this->getSchema()[ $table ]) && $this->driver->has($this->root . $this->path, $table);
    }

    /**
     * Détermine si une colonne existe.
     *
     * @param string $table  Nom de la table.
     * @param string $column Nom de la colonne.
     *
     * @return bool Si le schéma de référence et le fichier de données existent.
     */
    public function hasColumn($table, $column)
    {
        return isset($this->getSchema()[ $table ][ 'fields' ][ $column ]) && $this->driver->has($this->root . $this->path, $table);
    }

    /**
     * Vide la table et initialise les champs incrémentaux.
     *
     * @param String $table Nom de la table.
     *
     * @throws TableNotFoundException
     *
     * @return bool
     */
    public function truncateTable($table)
    {
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException("Table $table is not exist.");
        }

        $deleteSchema = true;
        if ($this->schema[ $table ][ 'increments' ] !== null) {
            $this->schema[ $table ][ 'increments' ] = 0;

            $deleteSchema = $this->save($this->name, $this->schema);
        }
        $deleteData = $this->save($table, []);

        return $deleteSchema && $deleteData;
    }

    /**
     * Supprime du schéma la référence de la table et son fichier de données.
     *
     * @param string $table Nom de la table.
     *
     * @throws TableNotFoundException
     *
     * @return bool Si la suppression du schema et des données se son bien passé.
     */
    public function dropTable($table)
    {
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException("Table $table is not exist.");
        }

        unset($this->schema[ $table ]);
        $deleteData   = $this->delete($table);
        $deleteSchema = $this->save($this->name, $this->schema);

        return $deleteSchema && $deleteData;
    }

    /**
     * Supprime une table si elle existe.
     *
     * @param string $table Nom de la table.
     *
     * @return bool Si la table n'existe plus.
     */
    public function dropTableIfExists($table)
    {
        return $this->hasTable($table) && $this->dropTable($table);
    }

    /**
     * Utilisation du driver pour connaître l'extension de fichier utilisé.
     *
     * @return string Extension de fichier sans le '.'.
     */
    public function getExtension()
    {
        return $this->driver->getExtension();
    }

    /**
     * Utilisation du driver pour lire un fichier.
     *
     * @param string $file
     *
     * @return array le contenu du fichier
     */
    public function read($file)
    {
        return $this->driver->read($this->root . $this->path, $file);
    }

    /**
     * Utilisation du driver pour enregistrer des données dans un fichier.
     *
     * @param string $file
     * @param array  $data
     *
     * @return bool
     */
    public function save($file, array $data)
    {
        return $this->driver->save($this->root . $this->path, $file, $data);
    }

    /**
     * Utilisation du driver pour créer un fichier.
     *
     * @param string $file
     * @param array  $data
     *
     * @return bool
     */
    protected function create($file, array $data = [])
    {
        return $this->driver->create($this->root . $this->path, $file, $data);
    }

    /**
     * Utilisation du driver pour supprimer un fichier.
     *
     * @param string $file
     *
     * @return bool
     */
    protected function delete($file)
    {
        return $this->driver->delete($this->root . $this->path, $file);
    }

    /**
     * Ajoute un champ dans les paramètre de la table et ses données.
     *
     * @param array  $fields    Les champs de la table.
     * @param array  $dataTable Les données de la table.
     * @param string $name      Nom du champ.
     * @param array  $value     Nouveaux paramètres.
     *
     * @return void
     */
    protected static function add(array &$fields, array &$dataTable, $name, array $value)
    {
        $fields[ $name ] = $value;
        foreach ($dataTable as &$data) {
            try {
                $data[ $name ] = self::getValueDefault($name, $value);
            } catch (\Exception $e) {
                $data[ $name ] = '';
            }
        }
    }

    /**
     * Modifie un champ dans les paramètre de la table et ses données.
     *
     * @param array  $fields    Les champs de la table.
     * @param array  $dataTable Les données de la table.
     * @param string $name      Nom du champ.
     * @param array  $value     Nouveaux paramètres.
     *
     * @return void
     */
    protected static function modify(array &$fields, array &$dataTable, $name, array $value)
    {
        unset($value[ 'opt' ]);
        $fields[ $name ] = $value;
        foreach ($dataTable as &$data) {
            try {
                $data[ $name ] = self::getValueDefault($name, $value);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    /**
     * Renomme un champ dans les paramètre de la table et ses données.
     *
     * @param array  $fields    Les champs de la table.
     * @param array  $dataTable Les données de la table.
     * @param string $name      Nom du champ.
     * @param string $to        Nouveau nom du champ.
     *
     * @return void
     */
    protected static function rename(array &$fields, array &$dataTable, $name, $to)
    {
        $fields[ $to ] = $fields[ $name ];
        unset($fields[ $name ]);
        foreach ($dataTable as &$data) {
            $data[ $to ] = $data[ $name ];
            unset($data[ $name ]);
        }
    }

    /**
     * Supprime un champ dans les paramètre de la table et ses données.
     *
     * @param array  $fields    Les champs de la table.
     * @param array  $dataTable Les données de la table.
     * @param string $name      Nom du champ.
     *
     * @return void
     */
    protected static function drop(array &$fields, array &$dataTable, $name)
    {
        unset($fields[ $name ]);
        foreach (array_keys($dataTable) as $key) {
            unset($dataTable[ $key ][ $name ]);
        }
    }

    /**
     * Vérifie si les opérations du champ sont conformes.
     *
     * @param string $table  Nom de la table.
     * @param array  $value  Paramètres du champ.
     * @param array  $fields Paramètres du champ en base.
     * @param string $name   Nom du champ.
     *
     * @throws Exception
     * @throws ColumnsNotFoundException
     * @return void
     */
    protected static function filterField($table, array $value, array $fields, $name)
    {
        /* Si un champ est ajouté. */
        if (!isset($value[ 'opt' ])) {
            if ($value[ 'type' ] === 'increments') {
                throw new ColumnsValueException("The $table table can not have multiple incremental values.");
            }
            /* Si un champ est ajouté il ne doit pas exister dans le schéma. */
            if (isset($fields[ $name ])) {
                throw new Exception("$name field does not exists in $table table.");
            }
        } else {
            /* Si un champ est modifie il doit exister dans le schéma. */
            if ($value[ 'opt' ] === 'modify' && !isset($fields[ $name ])) {
                throw new ColumnsNotFoundException("$name field does not exists in $table table.");
            }
            /* Si il s'agit d'une opération sur un champ il doit exister dans le schéma. */
            if (!isset($fields[ $name ])) {
                throw new ColumnsNotFoundException("$name field does not exists in $table table.");
            }
        }
    }

    /**
     * Passe en premier paramètre d'une fonction anonyme un objet TableBuilder et le retourne.
     *
     * @param callable $callback Fonction anonyme.
     *
     * @return TableBuilder
     */
    protected static function tableBuilder(callable $callback)
    {
        $builder = new TableBuilder();
        call_user_func_array($callback, [ &$builder ]);

        return $builder;
    }
}
