<?php

declare(strict_types=1);

/**
 * @package Queryflatfile

 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

use Soosyze\Queryflatfile\Commands\DropCommand;
use Soosyze\Queryflatfile\Commands\RenameCommand;
use Soosyze\Queryflatfile\DriverInterface;
use Soosyze\Queryflatfile\Drivers\Json;
use Soosyze\Queryflatfile\Enums\TableExecutionType;
use Soosyze\Queryflatfile\Exceptions\Exception;
use Soosyze\Queryflatfile\Exceptions\Query\TableNotFoundException;
use Soosyze\Queryflatfile\Exceptions\TableBuilder\ColumnsNotFoundException;
use Soosyze\Queryflatfile\Exceptions\TableBuilder\ColumnsValueException;
use Soosyze\Queryflatfile\Fields\DropType;
use Soosyze\Queryflatfile\Fields\IncrementType;
use Soosyze\Queryflatfile\Fields\RenameType;

/**
 * Pattern fluent pour la gestion d'un schéma de données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @phpstan-type RowData array<string, null|scalar>
 * @phpstan-type RowValues array<null|scalar>
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
     */
    protected string $path;

    /**
     * La racine pour le répertoire de stockage.
     */
    protected string $root = '';

    /**
     * Nom du schéma.
     */
    protected string $name;

    /**
     * Schéma des tables.
     *
     * @var array<string, Table>
     */
    protected array $schema = [];

    /**
     * Construis l'objet avec une configuration.
     *
     * @param string|null          $host   Répertoire de stockage des données.
     * @param string               $name   Nom du fichier contenant le schéma de base.
     * @param DriverInterface|null $driver Interface de manipulation de données.
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
        $this->driver = $driver ?? new Drivers\Json();
        $this->path   = $host;
        $this->name   = $name;

        return $this;
    }

    /**
     * Retourne le chemin relatif au répertoire de stockage.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Ajoute la racine du répertoire de stockage.
     *
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
        return $this->hasTable($tableName)
            ? $this->getSchema()[ $tableName ]
            : throw new TableNotFoundException($tableName);
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
        $this->schema[ $tableName ] = $this->hasTable($tableName)
            ? throw new Exception(sprintf('Table %s exist.', $tableName))
            : self::tableBuilder($tableName, $callback)->getTable();

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
            if ($field->getExecutionType() === TableExecutionType::Create) {
                $this->tryFieldAdd($tableSchema, $field);
                self::add($tableSchema, $field, $tableData);
            } elseif ($field->getExecutionType() === TableExecutionType::Modify) {
                $this->tryFieldModify($tableSchema, $field);
                self::modify($tableSchema, $field, $tableData);
            }
        }
        foreach($tableBuilder->getCommands() as $command) {
            if ($command instanceof RenameCommand) {
                $this->tryFieldRename($tableSchema, $command);
                self::rename($tableSchema, $command, $tableData);
            } elseif ($command instanceof DropCommand) {
                $this->tryFieldDrop($tableSchema, $command);
                self::drop($tableSchema, $command, $tableData);
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
     * @param string $tableName  Nom de la table.
     * @param string $columnName Nom de la colonne.
     *
     * @return bool Si le schéma de référence et le fichier de données existent.
     */
    public function hasColumn(string $tableName, string $columnName): bool
    {
        return isset($this->getSchema()[ $tableName ]) &&
            $this->getSchema()[ $tableName ]->hasField($columnName) &&
            $this->driver->has($this->root . $this->path, $tableName);
    }

    /**
     * Vide la table et initialise les champs incrémentaux.
     *
     * @param String $tableName Nom de la table.
     *
     * @throws TableNotFoundException
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
     *
     * @return array le contenu du fichier
     */
    public function read(string $file): array
    {
        return $this->driver->read($this->root . $this->path, $file);
    }

    /**
     * Utilisation du driver pour enregistrer des données dans un fichier.
     */
    public function save(string $file, array $data): bool
    {
        return $this->driver->save($this->root . $this->path, $file, $data);
    }

    /**
     * Utilisation du driver pour créer un fichier.
     */
    protected function create(string $file, array $data = []): bool
    {
        return $this->driver->create($this->root . $this->path, $file, $data);
    }

    /**
     * Utilisation du driver pour supprimer un fichier.
     */
    protected function delete(string $file): bool
    {
        return $this->driver->delete($this->root . $this->path, $file);
    }

    /**
     * Ajoute un champ dans les paramètre de la table et ses données.
     *
     * @param Table $table     Schéma de la table.
     * @param Field $field     Nouveau champ.
     * @param array $tableData Les données de la table.
     *
     * @phpstan-param TableData $tableData
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
        } catch (ColumnsValueException | \InvalidArgumentException) {
            $valueDefault = '';
        }

        foreach ($tableData as &$data) {
            $data[ $field->name ] = $increment === null
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
     */
    protected static function modify(
        Table &$table,
        Field $field,
        array &$tableData
    ): void {
        $oldField = $table->getField($field->name);

        $table->addField($field);

        /**
         * Si la modification ne concerne pas le type, la mise à jour des données ne se fait pas.
         * Exemple: rendre un champ nullable ne doit écraser les données présentent en table.
         */
        if ($oldField->getType() === $field->getType()) {
            return;
        }

        $increment = $field instanceof IncrementType
            ? 0
            : null;

        try {
            $valueDefault = $field->getValueDefault();
            foreach ($tableData as &$data) {
                $data[ $field->name ] = $valueDefault;
            }
        } catch (ColumnsValueException | \InvalidArgumentException) {
        }

        if ($increment !== null) {
            $table->setIncrement($increment);
        }
    }

    /**
     * Renomme un champ dans les paramètre de la table et ses données.
     *
     * @param Table         $table     Schéma de la table.
     * @param RenameCommand $command   champ à renommer
     * @param array         $tableData Les données de la table.
     */
    protected static function rename(
        Table &$table,
        RenameCommand $command,
        array &$tableData
    ): void {
        $table->renameField($command->name, $command->to);

        foreach ($tableData as &$data) {
            $data[ $command->to ] = $data[ $command->name ];
            unset($data[ $command->name ]);
        }
    }

    /**
     * Supprime un champ dans les paramètre de la table et ses données.
     *
     * @param Table       $table     Schéma de la table.
     * @param DropCommand $command   Champ à supprimer
     * @param array       $tableData Les données de la table.
     */
    protected static function drop(
        Table &$table,
        DropCommand $command,
        array &$tableData
    ): void {
        foreach (array_keys($tableData) as $key) {
            unset($tableData[ $key ][ $command->name ]);
        }

        if ($table->getField($command->name) instanceof IncrementType) {
            $table->setIncrement(null);
        }
        $table->unsetField($command->name);
    }

    /**
     * Passe en premier paramètre d'une fonction anonyme un objet TableBuilder et le retourne.
     *
     * @param string   $tableName Nom de la table.
     * @param callable $callback  Fonction anonyme.
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
    private function tryFieldAdd(Table $tableSchema, Field $field): void
    {
        /* Si un champ est ajouté il ne doit pas exister dans le schéma. */
        if ($tableSchema->hasField($field->name)) {
            throw new Exception(
                sprintf(
                    '%s field does not exists in %s table.',
                    $field->name,
                    $tableSchema->name
                )
            );
        }
        if ($tableSchema->hasIncrement() && $field instanceof IncrementType) {
            throw new ColumnsValueException(
                sprintf(
                    'The %s table can not have multiple incremental values.',
                    $tableSchema->name
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
    private function tryFieldModify(Table $tableSchema, Field $field): void
    {
        if (!$tableSchema->hasField($field->name)) {
            throw new Exception(
                sprintf(
                    '%s field does not exists in %s table.',
                    $field->name,
                    $tableSchema->name
                )
            );
        }
        if ($tableSchema->hasIncrement() && $field instanceof IncrementType) {
            throw new ColumnsValueException(
                sprintf(
                    'The %s table can not have multiple incremental values.',
                    $tableSchema->name
                )
            );
        }

        $fieldOld = $tableSchema->getField($field->name);
        $typeFieldOld = $fieldOld->getType();
        $typeField = $field->getType();

        /* Si le type change, les données présents doivent être d'un type équivalent. */
        if (!$typeFieldOld->isModify($typeField)) {
            throw new Exception(
                sprintf(
                    'The %s column type %s can not be changed with the %s type.',
                    $field->name,
                    $typeFieldOld->value,
                    $typeField->value
                )
            );
        }
    }

    /**
     * Vérifie si les opérations de renommage du champ sont conformes.
     *
     * @param Table         $table   Le schéma de la table.
     * @param RenameCommand $command Champ à renommer.
     *
     * @throws ColumnsNotFoundException
     * @throws Exception
     */
    private function tryFieldRename(Table $table, RenameCommand $command): void
    {
        if (!$table->hasField($command->name)) {
            throw new ColumnsNotFoundException(
                sprintf('%s field does not exists in %s table.', $command->name, $table->name)
            );
        }
        /* Si le champ à renommer existe dans le schema. */
        if ($table->hasField($command->to)) {
            throw new Exception(
                sprintf('%s field does exists in %s table.', $command->name, $table->name)
            );
        }
    }

    /**
     * Vérifie si les opérations de suppression du champ sont conformes.
     *
     * @param Table       $table   Le schéma de la table.
     * @param DropCommand $command Champ à supprimer
     *
     * @throws ColumnsNotFoundException
     */
    private function tryFieldDrop(Table $table, DropCommand $command): void
    {
        if (!$table->hasField($command->name)) {
            throw new ColumnsNotFoundException(
                sprintf('%s field does not exists in %s table.', $command->name, $table->name)
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
