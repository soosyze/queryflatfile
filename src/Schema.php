<?php

declare(strict_types=1);

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
        ?string $host = null,
        string $name = 'schema',
        DriverInterface $driver = null
    ) {
        if ($host !== null) {
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
        string $host,
        string $name = 'schema',
        DriverInterface $driver = null
    ): self {
        $this->driver = $driver ?? new Driver\Json();
        $this->path   = $host;
        $this->name   = $name;

        return $this;
    }

    /**
     * Retourne le chemin relatif au répertoire de stockage.
     *
     * @return string
     */
    public function getPath(): string
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
    public function setPathRoot(string $root = ''): self
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Modifie la valeur incrémentale d'une table.
     *
     * @param string $table     Nom de la table.
     * @param int    $increment Tableau associatif des valeurs incrémentales.
     *
     * @throws TableNotFoundException
     * @throws Exception
     *
     * @return bool Si le schéma d'incrémentaion est bien enregistré.
     */
    public function setIncrement(string $table, int $increment): bool
    {
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException($table);
        }

        if (!isset($this->schema[ $table ][ 'increments' ])) {
            throw new Exception(
                sprintf('Table %s does not have an incremental value.', $table)
            );
        }

        $this->schema[ $table ][ 'increments' ] = $increment;

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
    public function getIncrement(string $table): int
    {
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException($table);
        }

        if ($this->schema[ $table ][ 'increments' ] === null) {
            throw new Exception(
                sprintf('Table %s does not have an incremental value.', $table)
            );
        }

        return $this->schema[ $table ][ 'increments' ];
    }

    /**
     * Génère le schéma s'il n'existe pas en fonction du fichier de configuration.
     *
     * @return array Schéma de la base de données.
     */
    public function getSchema(): array
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
    public function getSchemaTable(string $table): array
    {
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException($table);
        }

        return $this->getSchema()[ $table ];
    }

    /**
     * Supprime le schéma courant des données.
     *
     * @return $this
     */
    public function dropSchema(): self
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
    public function createTable(string $table, ?callable $callback = null): self
    {
        if ($this->hasTable($table)) {
            throw new Exception(sprintf('Table %s exist.', $table));
        }

        $builder = self::tableBuilder($callback);

        $this->schema[ $table ] = $builder->getTableSchema();

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
    public function createTableIfNotExists(string $table, ?callable $callback = null): self
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
     * @return $this
     */
    public function alterTable(string $table, callable $callback): self
    {
        $schema       = $this->getSchemaTable($table);
        $fields       = $schema[ 'fields' ];
        $tableBuilder = self::tableAlterBuilder($callback)->build();
        $dataTable    = $this->read($table);

        foreach ($tableBuilder as $name => $params) {
            if (!isset($params[ 'opt' ])) {
                self::filterFieldAdd($table, $fields, $name, $params[ 'type' ]);
                self::add($schema, $dataTable, $name, $params);
            } elseif ($params[ 'opt' ] === TableAlter::OPT_RENAME) {
                self::filterFieldRename($table, $fields, $name, $params[ 'to' ]);
                self::rename($schema, $dataTable, $name, $params[ 'to' ]);
            } elseif ($params[ 'opt' ] === TableAlter::OPT_MODIFY) {
                self::filterFieldModify($table, $fields, $name, $params[ 'type' ]);
                self::modify($schema, $dataTable, $name, $params);
            } elseif ($params[ 'opt' ] === TableAlter::OPT_DROP) {
                self::filterFieldDrop($table, $fields, $name);
                self::drop($schema, $dataTable, $name);
            }
        }

        $this->schema[ $table ] = $schema;
        $this->save($this->name, $this->schema);
        $this->save($table, $dataTable);

        return $this;
    }

    /**
     * Retourne la valeur par defaut du champ passé en paramêtre.
     *
     * @param string $name   Nom du champ.
     * @param array  $params Différente configurations.
     *
     * @throws ColumnsValueException
     *
     * @return mixed Valeur par defaut.
     */
    public static function getValueDefault(string $name, array &$params)
    {
        if (isset($params[ 'default' ])) {
            if ($params[ 'type' ] === TableBuilder::TYPE_DATE && $params[ 'default' ] === TableBuilder::CURRENT_DATE_DEFAULT) {
                return date('Y-m-d', time());
            }
            if ($params[ 'type' ] === TableBuilder::TYPE_DATETIME && $params[ 'default' ] === TableBuilder::CURRENT_DATETIME_DEFAULT) {
                return date('Y-m-d H:i:s', time());
            }

            /* Si les variables magiques ne sont pas utilisé alors la vrais valeur par defaut est retourné. */
            return $params[ 'default' ];
        }
        if (isset($params[ 'nullable' ])) {
            return null;
        }

        throw new ColumnsValueException(
            sprintf('%s not nullable or not default.', $name)
        );
    }

    /**
     * Détermine une table existe.
     *
     * @param string $table Nom de la table.
     *
     * @return bool Si le schéma de référence et le fichier de données existent.
     */
    public function hasTable(string $table): bool
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
    public function hasColumn(string $table, string $column): bool
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
    public function truncateTable(string $table): bool
    {
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException($table);
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
    public function dropTable(string $table): bool
    {
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException($table);
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
    public function dropTableIfExists(string $table): bool
    {
        return $this->hasTable($table) && $this->dropTable($table);
    }

    /**
     * Utilisation du driver pour connaître l'extension de fichier utilisé.
     *
     * @return string Extension de fichier sans le '.'.
     */
    public function getExtension(): string
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
    public function read(string $file): array
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
    public function save(string $file, array $data): bool
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
    protected function create(string $file, array $data = []): bool
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
    protected function delete(string $file): bool
    {
        return $this->driver->delete($this->root . $this->path, $file);
    }

    /**
     * Ajoute un champ dans les paramètre de la table et ses données.
     *
     * @param array  $schema    Schéma de la table.
     * @param array  $dataTable Les données de la table.
     * @param string $name      Nom du champ.
     * @param array  $params    Nouveaux paramètres.
     *
     * @return void
     */
    protected static function add(
        array &$schema,
        array &$dataTable,
        string $name,
        array $params
    ): void {
        $schema[ 'fields' ][ $name ] = $params;

        $increment = $params[ 'type' ] === TableBuilder::TYPE_INCREMENT
            ? 0
            : null;

        try {
            $valueDefault = self::getValueDefault($name, $params);
        } catch (ColumnsValueException $e) {
            $valueDefault = '';
        }

        foreach ($dataTable as &$data) {
            $data[ $name ] = $increment === null
                ? $valueDefault
                : ++$increment;
        }

        if ($params[ 'type' ] === TableBuilder::TYPE_INCREMENT) {
            $schema[ 'increments' ] = $increment;
        }
    }

    /**
     * Modifie un champ dans les paramètre de la table et ses données.
     *
     * @param array  $schema    Schéma de la table.
     * @param array  $dataTable Les données de la table.
     * @param string $name      Nom du champ.
     * @param array  $params    Nouveaux paramètres.
     *
     * @return void
     */
    protected static function modify(
        array &$schema,
        array &$dataTable,
        string $name,
        array $params
    ): void {
        unset($params[ 'opt' ]);
        $schema[ 'fields' ][ $name ] = $params;

        $increment = $params[ 'type' ] === TableBuilder::TYPE_INCREMENT
            ? 0
            : null;

        try {
            $valueDefault = self::getValueDefault($name, $params);
            foreach ($dataTable as &$data) {
                $data[ $name ] = $valueDefault;
            }
        } catch (ColumnsValueException $e) {
        }

        if ($params[ 'type' ] === TableBuilder::TYPE_INCREMENT) {
            $schema[ 'increments' ] = $increment;
        }
    }

    /**
     * Renomme un champ dans les paramètre de la table et ses données.
     *
     * @param array  $schema    Les champs de la table.
     * @param array  $dataTable Les données de la table.
     * @param string $name      Nom du champ.
     * @param string $to        Nouveau nom du champ.
     *
     * @return void
     */
    protected static function rename(
        array &$schema,
        array &$dataTable,
        string $name,
        string $to
    ): void {
        $schema[ 'fields' ][ $to ] = $schema[ 'fields' ][ $name ];
        unset($schema[ 'fields' ][ $name ]);
        foreach ($dataTable as &$data) {
            $data[ $to ] = $data[ $name ];
            unset($data[ $name ]);
        }
    }

    /**
     * Supprime un champ dans les paramètre de la table et ses données.
     *
     * @param array  $schema    Les champs de la table.
     * @param array  $dataTable Les données de la table.
     * @param string $name      Nom du champ.
     *
     * @return void
     */
    protected static function drop(
        array &$schema,
        array &$dataTable,
        string $name
    ): void {
        foreach (array_keys($dataTable) as $key) {
            unset($dataTable[ $key ][ $name ]);
        }

        if ($schema[ 'fields' ][ $name ][ 'type' ] === TableBuilder::TYPE_INCREMENT) {
            $schema[ 'increments' ] = null;
        }
        unset($schema[ 'fields' ][ $name ]);
    }

    /**
     * Retour true si l'un des champs est de type incrementale.
     *
     * @param array $fields
     *
     * @return bool
     */
    protected static function isFieldIncrement(array $fields): bool
    {
        foreach ($fields as $field) {
            if ($field[ 'type' ] === TableBuilder::TYPE_INCREMENT) {
                return true;
            }
        }

        return false;
    }

    /**
     * Passe en premier paramètre d'une fonction anonyme un objet TableBuilder et le retourne.
     *
     * @param callable $callback Fonction anonyme.
     *
     * @return TableBuilder
     */
    protected static function tableAlterBuilder(callable $callback): TableBuilder
    {
        $builder = new TableAlter();
        call_user_func_array($callback, [ &$builder ]);

        return $builder;
    }

    /**
     * Passe en premier paramètre d'une fonction anonyme un objet TableBuilder et le retourne.
     *
     * @param callable|null $callback Fonction anonyme.
     *
     * @return TableBuilder
     */
    protected static function tableBuilder(?callable $callback = null): TableBuilder
    {
        $builder = new TableBuilder();
        if ($callback !== null) {
            call_user_func_array($callback, [ &$builder ]);
        }

        return $builder;
    }

    /**
     * Vérifie si les opérations du champ sont conformes.
     *
     * @param string $table  Nom de la table.
     * @param array  $fields Paramètres du champ en base.
     * @param string $name   Nom du champ.
     * @param string $type   Type de donnée.
     *
     * @throws Exception
     * @throws ColumnsNotFoundException
     */
    private static function filterFieldAdd(
        string $table,
        array $fields,
        string $name,
        string $type
    ): void {
        /* Si un champ est ajouté il ne doit pas exister dans le schéma. */
        if (isset($fields[ $name ])) {
            throw new Exception(
                sprintf('%s field does not exists in %s table.', $name, $table)
            );
        }
        if ($type === TableBuilder::TYPE_INCREMENT && self::isFieldIncrement($fields)) {
            throw new ColumnsValueException(
                sprintf(
                    'The %s table can not have multiple incremental values.',
                    $table
                )
            );
        }
    }

    /**
     * @param string $table  Nom de la table.
     * @param array  $fields Paramètres du champ en base.
     * @param string $name   Nom du champ.
     * @param string $type   Type de donnée.
     *
     * @throws ColumnsNotFoundException
     * @throws ColumnsValueException
     * @throws Exception
     */
    private static function filterFieldModify(
        string $table,
        array $fields,
        string $name,
        string $type
    ): void {
        if (!isset($fields[ $name ])) {
            throw new ColumnsNotFoundException(
                sprintf(
                   "%s field does not exists in %s table.", $name, $table
                )
            );
        }
        if ($type === TableBuilder::TYPE_INCREMENT && self::isFieldIncrement($fields)) {
            throw new ColumnsValueException(
                sprintf(
                    'The %s table can not have multiple incremental values.',
                    $table
                )
            );
        }

        /* Si le type change, les données présents doivent être d'un type équivalent. */
        $modifyNumber = in_array($type, [ 'integer', 'float', 'increments' ]) &&
            !in_array($fields[ $name ][ 'type' ], [ 'integer', 'float', 'increments' ]);
        $modifyString = in_array($type, [ 'text', 'string', 'char' ]) &&
            !in_array($fields[ $name ][ 'type' ], [ 'text', 'string', 'char' ]);
        $modifyDate   = in_array($type, [ 'date', 'datetime' ]) &&
            !in_array($fields[ $name ][ 'type' ], [ 'date', 'datetime', 'string', 'text' ]);

        if ($modifyString || $modifyNumber || $modifyDate) {
            throw new Exception(
                sprintf(
                    'The %s column type %s can not be changed with the %s type.',
                    $name,
                    $fields[ $name ][ 'type' ],
                    $type
                )
            );
        }
    }

    /**
     * @param string $table  Nom de la table.
     * @param array  $fields Paramètres du champ en base.
     * @param string $name   Nom du champ.
     * @param string $to     Nouveau nom du champ.
     *
     * @throws ColumnsNotFoundException
     * @throws Exception
     */
    private static function filterFieldRename(
        string $table,
        array $fields,
        string $name,
        string $to
    ): void {
        if (!isset($fields[ $name ])) {
            throw new ColumnsNotFoundException(
                sprintf('%s field does not exists in %s table.', $name, $table)
            );
        }
        /* Si le champ à renommer existe dans le schema. */
        if (isset($fields[ $to ])) {
            throw new Exception(
                sprintf('%s field does exists in %s table.', $name, $table)
            );
        }
    }

    /**
     * @param string $table  Nom de la table.
     * @param array  $fields Paramètres du champ en base.
     * @param string $name   Nom du champ.
     *
     * @throws ColumnsNotFoundException
     *
     * @return void
     */
    private static function filterFieldDrop(
        string $table,
        array $fields,
        string $name
    ): void {
        if (!isset($fields[ $name ])) {
            throw new ColumnsNotFoundException(
                sprintf('%s field does not exists in %s table.', $name, $table)
            );
        }
    }
}
