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
use Queryflatfile\Exception\Query\TableNotFoundException;
use Queryflatfile\Exception\TableBuilder\ColumnsNotFoundException;
use Queryflatfile\Exception\TableBuilder\ColumnsValueException;
use Queryflatfile\Field\DropType;
use Queryflatfile\Field\IncrementType;
use Queryflatfile\Field\RenameType;

/**
 * Pattern fluent pour la gestion d'un schéma de données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @phpstan-type RowData array<string, null|scalar>
 * @phpstan-type TableData RowData[]
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
     * @var array<string, Table>
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
     * @param string $tableName Nom de la table.
     * @param int    $increment Nouvelle valeur incrémentale.
     *
     * @throws TableNotFoundException
     * @throws Exception
     *
     * @return bool Si le schéma d'incrémentaion est bien enregistré.
     */
    public function setIncrement(string $tableName, int $increment): bool
    {
        if (!$this->hasTable($tableName)) {
            throw new TableNotFoundException($tableName);
        }

        if (!$this->schema[ $tableName ]->hasIncrement()) {
            throw new Exception(
                sprintf('Table %s does not have an incremental value.', $tableName)
            );
        }

        $this->schema[ $tableName ]->setIncrement($increment);

        return $this->save($this->name, $this->toArray());
    }

    /**
     * Retourne la valeur incrémentale d'une table.
     *
     * @param string $tableName Nom de la table.
     *
     * @throws TableNotFoundException
     * @throws Exception
     *
     * @return int
     */
    public function getIncrement(string $tableName): int
    {
        if (!$this->hasTable($tableName)) {
            throw new TableNotFoundException($tableName);
        }

        if ($this->schema[ $tableName ]->getIncrement() === null) {
            throw new Exception(
                sprintf('Table %s does not have an incremental value.', $tableName)
            );
        }

        return $this->schema[ $tableName ]->getIncrement();
    }

    /**
     * Retourne le schema des tables.
     *
     * @return array<Table> Schéma de la base de données.
     */
    public function getSchema(): array
    {
        if ($this->schema) {
            return $this->schema;
        }
        $this->create($this->name);
        $schema = $this->read($this->name);

        /** @var string $tableName */
        foreach ($schema as $tableName => $table) {
            $this->schema[ $tableName ] = TableBuilder::createTableFromArray($tableName, $table);
        }

        return $this->schema;
    }

    /**
     * Cherche le schéma de la table passée en paramètre.
     *
     * @param string $tableName Nom de la table.
     *
     * @throws TableNotFoundException
     *
     * @return Table Schéma de la table.
     */
    public function getTableSchema(string $tableName): Table
    {
        if (!$this->hasTable($tableName)) {
            throw new TableNotFoundException($tableName);
        }

        return $this->getSchema()[ $tableName ];
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
        foreach (array_keys($schema) as $tableName) {
            $this->delete($tableName);
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
     * @param string        $tableName Nom de la table.
     * @param callable|null $callback  fonction(TableBuilder $tableName) pour créer les champs.
     *
     * @throws Exception
     *
     * @return $this
     */
    public function createTable(string $tableName, ?callable $callback = null): self
    {
        if ($this->hasTable($tableName)) {
            throw new Exception(sprintf('Table %s exist.', $tableName));
        }

        $this->schema[ $tableName ] = self::tableBuilder($tableName, $callback)->getTable();

        $this->save($this->name, $this->toArray());
        $this->create($tableName);

        return $this;
    }

    /**
     * Créer une référence dans le schéma et un fichier de données si ceux si n'existe pas.
     *
     * @param string        $tableName Nom de la table.
     * @param callable|null $callback  fonction(TableBuilder $table) pour créer les champs.
     *
     * @return $this
     */
    public function createTableIfNotExists(string $tableName, ?callable $callback = null): self
    {
        /* Créer la table si elle n'existe pas dans le schéma. */
        if (!$this->hasTable($tableName)) {
            $this->createTable($tableName, $callback);
        } elseif (!$this->driver->has($this->root . $this->path, $tableName)) {
            /* Si elle existe dans le schéma et que le fichier est absent alors on le créer. */
            $this->create($tableName);
        }

        return $this;
    }

    /**
     * Modifie les champs du schéma de données.
     *
     * @param string   $tableName Nom de la table.
     * @param callable $callback  fonction(TableAleter $tableAlter) pour manipuler les champs.
     *
     * @return $this
     */
    public function alterTable(string $tableName, callable $callback): self
    {
        $tableSchema  = $this->getTableSchema($tableName);
        $tableBuilder = self::tableAlterBuilder($tableName, $callback)->getTable();
        $tableData    = $this->read($tableName);

        foreach ($tableBuilder->getFields() as $field) {
            if ($field->getOpt() === Field::OPT_CREATE) {
                self::filterFieldAdd($tableSchema, $field);
                self::add($tableSchema, $field, $tableData);
            } elseif ($field->getOpt() === Field::OPT_RENAME) {
                self::filterFieldRename($tableSchema, $field);
                self::rename($tableSchema, $field, $tableData);
            } elseif ($field->getOpt() === Field::OPT_MODIFY) {
                self::filterFieldModify($tableSchema, $field);
                self::modify($tableSchema, $field, $tableData);
            } elseif ($field->getOpt() === Field::OPT_DROP) {
                self::filterFieldDrop($tableSchema, $field);
                self::drop($tableSchema, $field, $tableData);
            }
        }

        $this->schema[ $tableName ] = $tableSchema;
        $this->save($this->name, $this->toArray());
        $this->save($tableName, $tableData);

        return $this;
    }

    /**
     * Détermine une table existe.
     *
     * @param string $tableName Nom de la table.
     *
     * @return bool Si le schéma de référence et le fichier de données existent.
     */
    public function hasTable(string $tableName): bool
    {
        return isset($this->getSchema()[ $tableName ]) && $this->driver->has($this->root . $this->path, $tableName);
    }

    /**
     * Détermine si une colonne existe.
     *
     * @param string $tableName Nom de la table.
     * @param string $column    Nom de la colonne.
     *
     * @return bool Si le schéma de référence et le fichier de données existent.
     */
    public function hasColumn(string $tableName, string $column): bool
    {
        return isset($this->getSchema()[ $tableName ]) &&
            $this->getSchema()[ $tableName ]->hasField($column) &&
            $this->driver->has($this->root . $this->path, $tableName);
    }

    /**
     * Vide la table et initialise les champs incrémentaux.
     *
     * @param String $tableName Nom de la table.
     *
     * @throws TableNotFoundException
     *
     * @return bool
     */
    public function truncateTable(string $tableName): bool
    {
        if (!$this->hasTable($tableName)) {
            throw new TableNotFoundException($tableName);
        }

        $deleteSchema = true;
        if ($this->schema[ $tableName ]->hasIncrement()) {
            $this->schema[ $tableName ]->setIncrement(0);

            $deleteSchema = $this->save($this->name, $this->toArray());
        }
        $deleteData = $this->save($tableName, []);

        return $deleteSchema && $deleteData;
    }

    /**
     * Supprime du schéma la référence de la table et son fichier de données.
     *
     * @param string $tableName Nom de la table.
     *
     * @throws TableNotFoundException
     *
     * @return bool Si la suppression du schema et des données se son bien passé.
     */
    public function dropTable(string $tableName): bool
    {
        if (!$this->hasTable($tableName)) {
            throw new TableNotFoundException($tableName);
        }

        unset($this->schema[ $tableName ]);
        $deleteData   = $this->delete($tableName);
        $deleteSchema = $this->save($this->name, $this->toArray());

        return $deleteSchema && $deleteData;
    }

    /**
     * Supprime une table si elle existe.
     *
     * @param string $tableName Nom de la table.
     *
     * @return bool Si la table n'existe plus.
     */
    public function dropTableIfExists(string $tableName): bool
    {
        return $this->hasTable($tableName) && $this->dropTable($tableName);
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
     * @param Table     $table     Schéma de la table.
     * @param Field     $field     Nouveau champ.
     * @param TableData $tableData Les données de la table.
     *
     * @return void
     */
    protected static function add(
        Table &$table,
        Field $field,
        array &$tableData
    ): void {
        $table->addField($field);

        $increment = $field instanceof IncrementType
            ? 0
            : null;

        try {
            $valueDefault = $field->getValueDefault();
        } catch (ColumnsValueException | \InvalidArgumentException $e) {
            $valueDefault = '';
        }

        foreach ($tableData as &$data) {
            $data[ $field->getName() ] = $increment === null
                ? $valueDefault
                : ++$increment;
        }

        if ($increment !== null) {
            $table->setIncrement($increment);
        }
    }

    /**
     * Modifie un champ dans les paramètre de la table et ses données.
     *
     * @param Table $table     Schéma de la table.
     * @param Field $field     Champ modifié.
     * @param array $tableData Les données de la table.
     *
     * @return void
     */
    protected static function modify(
        Table &$table,
        Field $field,
        array &$tableData
    ): void {
        $table->addField($field);

        $increment = $field instanceof IncrementType
            ? 0
            : null;

        try {
            $valueDefault = $field->getValueDefault();
            foreach ($tableData as &$data) {
                $data[ $field->getName() ] = $valueDefault;
            }
        } catch (ColumnsValueException | \InvalidArgumentException $e) {
        }

        if ($increment !== null) {
            $table->setIncrement($increment);
        }
    }

    /**
     * Renomme un champ dans les paramètre de la table et ses données.
     *
     * @param Table      $table       Schéma de la table.
     * @param RenameType $fieldRename champ à renommer
     * @param array      $tableData   Les données de la table.
     *
     * @return void
     */
    protected static function rename(
        Table &$table,
        RenameType $fieldRename,
        array &$tableData
    ): void {
        $table->renameField($fieldRename->getName(), $fieldRename->getTo());

        foreach ($tableData as &$data) {
            $data[ $fieldRename->getTo() ] = $data[ $fieldRename->getName() ];
            unset($data[ $fieldRename->getName() ]);
        }
    }

    /**
     * Supprime un champ dans les paramètre de la table et ses données.
     *
     * @param Table    $table     Schéma de la table.
     * @param DropType $fieldDrop Champ à supprimer
     * @param array    $tableData Les données de la table.
     *
     * @return void
     */
    protected static function drop(
        Table &$table,
        DropType $fieldDrop,
        array &$tableData
    ): void {
        foreach (array_keys($tableData) as $key) {
            unset($tableData[ $key ][ $fieldDrop->getName() ]);
        }

        if ($table->getField($fieldDrop->getName()) instanceof IncrementType) {
            $table->setIncrement(null);
        }
        $table->unsetField($fieldDrop->getName());
    }

    /**
     * Passe en premier paramètre d'une fonction anonyme un objet TableBuilder et le retourne.
     *
     * @param string   $tableName Nom de la table.
     * @param callable $callback  Fonction anonyme.
     *
     * @return TableBuilder
     */
    protected static function tableAlterBuilder(string $tableName, callable $callback): TableBuilder
    {
        $builder = new TableAlter($tableName);
        call_user_func_array($callback, [ &$builder ]);

        return $builder;
    }

    /**
     * Passe en premier paramètre d'une fonction anonyme un objet TableBuilder et le retourne.
     *
     * @param string        $tableName Nom de la table.
     * @param callable|null $callback  Fonction anonyme.
     *
     * @return TableBuilder
     */
    protected static function tableBuilder(string $tableName, ?callable $callback = null): TableBuilder
    {
        $builder = new TableBuilder($tableName);
        if ($callback !== null) {
            call_user_func_array($callback, [ &$builder ]);
        }

        return $builder;
    }

    /**
     * Vérifie si les opérations d'ajout du champ sont conformes.
     *
     * @param Table $tableSchema Le schéma de la table.
     * @param Field $field       Nouveau champ.
     *
     * @throws Exception
     * @throws ColumnsNotFoundException
     */
    private static function filterFieldAdd(Table $tableSchema, Field $field): void
    {
        /* Si un champ est ajouté il ne doit pas exister dans le schéma. */
        if ($tableSchema->hasField($field->getName())) {
            throw new Exception(
                sprintf(
                    '%s field does not exists in %s table.',
                    $field->getName(),
                    $tableSchema->getName()
                )
            );
        }
        if ($tableSchema->hasIncrement() && $field instanceof IncrementType) {
            throw new ColumnsValueException(
                sprintf(
                    'The %s table can not have multiple incremental values.',
                    $tableSchema->getName()
                )
            );
        }
    }

    /**
     * Vérifie si les opérations de modification du champ sont conformes.
     *
     * @param Table $tableSchema Le schéma de la table.
     * @param Field $field       Champ à modifier.
     *
     * @throws ColumnsNotFoundException
     * @throws ColumnsValueException
     * @throws Exception
     */
    private static function filterFieldModify(Table $tableSchema, Field $field): void
    {
        if (!$tableSchema->hasField($field->getName())) {
            throw new Exception(
                sprintf(
                    '%s field does not exists in %s table.',
                    $field->getName(),
                    $tableSchema->getName()
                )
            );
        }
        if ($tableSchema->hasIncrement() && $field instanceof IncrementType) {
            throw new ColumnsValueException(
                sprintf(
                    'The %s table can not have multiple incremental values.',
                    $tableSchema->getName()
                )
            );
        }

        $fieldOld = $tableSchema->getField($field->getName());

        /* Si le type change, les données présents doivent être d'un type équivalent. */
        $modifyNumber = in_array($field::TYPE, [ 'integer', 'float', 'increments' ]) &&
            !in_array($fieldOld::TYPE, [ 'integer', 'float', 'increments' ]);
        $modifyString = in_array($field::TYPE, [ 'text', 'string', 'char' ]) &&
            !in_array($fieldOld::TYPE, [ 'text', 'string', 'char' ]);
        $modifyDate   = in_array($field::TYPE, [ 'date', 'datetime' ]) &&
            !in_array($fieldOld::TYPE, [ 'date', 'datetime', 'string', 'text' ]);

        if ($modifyString || $modifyNumber || $modifyDate) {
            throw new Exception(
                sprintf(
                    'The %s column type %s can not be changed with the %s type.',
                    $field->getName(),
                    $fieldOld::TYPE,
                    $field::TYPE
                )
            );
        }
    }

    /**
     * Vérifie si les opérations de renommage du champ sont conformes.
     *
     * @param Table      $table Le schéma de la table.
     * @param RenameType $field Champ à renommer.
     *
     * @throws ColumnsNotFoundException
     * @throws Exception
     */
    private static function filterFieldRename(Table $table, RenameType $field): void
    {
        if (!$table->hasField($field->getName())) {
            throw new ColumnsNotFoundException(
                sprintf('%s field does not exists in %s table.', $field->getName(), $table->getName())
            );
        }
        /* Si le champ à renommer existe dans le schema. */
        if ($table->hasField($field->getTo())) {
            throw new Exception(
                sprintf('%s field does exists in %s table.', $field->getName(), $table->getName())
            );
        }
    }

    /**
     * Vérifie si les opérations de suppression du champ sont conformes.
     *
     * @param Table    $table Le schéma de la table.
     * @param DropType $field Champ à supprimer
     *
     * @throws ColumnsNotFoundException
     *
     * @return void
     */
    private static function filterFieldDrop(Table $table, DropType $field): void
    {
        if (!$table->hasField($field->getName())) {
            throw new ColumnsNotFoundException(
                sprintf('%s field does not exists in %s table.', $field->getName(), $table->getName())
            );
        }
    }

    private function toArray(): array
    {
        $tables = [];
        foreach ($this->schema as $tableName => $table) {
            $tables[ $tableName ] = $table->toArray();
        }

        return $tables;
    }
}
