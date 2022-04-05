<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\Exception\Query\BadFunctionException;
use Queryflatfile\Exception\Query\ColumnsNotFoundException;
use Queryflatfile\Exception\Query\OperatorNotFound;
use Queryflatfile\Exception\Query\QueryException;
use Queryflatfile\Exception\Query\TableNotFoundException;
use Queryflatfile\Field\IncrementType;

/**
 * Réalise des requêtes à partir d'un schéma de données passé en paramètre.
 * Les requêtes se construisent avec le pattern fluent.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @phpstan-import-type RowData from Schema
 * @phpstan-import-type TableData from Schema
 */
class Request extends RequestHandler
{
    use ValueToString;

    /**
     * Tous les champs utilisés.
     *
     * @var Field[]
     */
    private $allFieldsSchema;

    /**
     * Les données de la table.
     *
     * @var array
     *
     * @phpstan-var TableData
     */
    private $tableData = [];

    /**
     * Le schéma des tables utilisées par la requête.
     *
     * @var Table
     */
    private $table;

    /**
     * Le schéma de base de données.
     *
     * @var Schema
     */
    private $schema;

    /**
     * Réalise une requête sur un schéma de données
     *
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Retourne les paramètre de la requête en format pseudo SQL.
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->execute === self::INSERT) {
            return $this->insertIntoToString();
        }
        if ($this->execute === self::UPDATE) {
            return $this->updateToString();
        }
        if ($this->execute === self::DELETE) {
            return $this->deleteToString();
        }

        $output = sprintf('SELECT %s ', $this->columnNames ? addslashes(implode(', ', $this->columnNames)) : '*');
        if ($this->from !== '') {
            $output .= sprintf('FROM %s ', addslashes($this->from));
        }
        foreach ($this->joins as $value) {
            $output .= sprintf(
                '%s JOIN %s ON %s ',
                strtoupper($value[ 'type' ]),
                addslashes($value[ 'table' ]),
                (string) $value[ 'where' ]
            );
        }
        $output .= $this->whereToString();
        foreach ($this->unions as $union) {
            $type = $union[ 'type' ] === self::UNION_SIMPLE ? 'UNION' : 'UNION ALL';
            $output .= sprintf('%s %s ', $type, trim((string) $union[ 'request' ], ';'));
        }
        if ($this->orderBy) {
            $output .= 'ORDER BY ';
            foreach ($this->orderBy as $field => $order) {
                $output .= sprintf(
                    '%s %s, ',
                    addslashes($field),
                    $order === SORT_ASC ? 'ASC' : 'DESC'
                );
            }
            $output = trim($output, ', ') . ' ';
        }
        if ($this->limit !== 0) {
            $output .= sprintf('LIMIT %d ', (string) $this->limit);
        }
        if ($this->offset !== 0) {
            $output .= sprintf('OFFSET %d ', (string) $this->offset);
        }

        return trim($output) . ';';
    }

    /**
     * Ajoute un schéma de données à notre requête.
     *
     * @param Schema $schema
     *
     * @return $this
     */
    public function setSchema(Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Lit les données d'une table.
     *
     * @param string $tableName Nom de la table.
     *
     * @return array Données de la table.
     */
    public function getTableData(string $tableName): array
    {
        return $this->schema->read($tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function from(string $tableName): self
    {
        parent::from($tableName);
        $this->table     = $this->schema->getTableSchema($tableName);
        $this->tableData = $this->getTableData($tableName);

        return $this;
    }

    /**
     * Lance l'exécution d'une requête de création, modification ou suppression.
     *
     * @throws BadFunctionException
     */
    public function execute(): void
    {
        $this->filterFrom();
        $this->loadAllFieldsSchema();
        $this->filterSelect();
        $this->filterWhere();

        if ($this->execute === self::INSERT) {
            $this->executeInsert();
        } elseif ($this->execute === self::UPDATE) {
            $this->executeUpdate();
        } elseif ($this->execute === self::DELETE) {
            $this->executeDelete();
        } else {
            throw new BadFunctionException('Only the insert, update and delete functions can be executed.');
        }

        $this->schema->save($this->from, $this->tableData);
        $this->init();
    }

    /**
     * Retourne tous les résultats de la requête.
     *
     * @return array les données
     *
     * @phpstan-return TableData
     */
    public function fetchAll(): array
    {
        $this->filterFrom();
        $this->loadAllFieldsSchema();
        $this->filterSelect();
        $this->filterWhere();
        $this->filterUnion();
        $this->filterOrderBy();
        $this->filterLimit();

        $return = [];
        /* Le pointeur en cas de limite de résultat. */
        $i      = 0;

        $columnNameKeys = array_flip($this->columnNames);

        /*
         * Exécution des jointures.
         * La réunion et l'intersection des ensembles sont soumis à la loi interne * et donc commutative.
         */
        foreach ($this->joins as $value) {
            $this->executeJoins($value[ 'type' ], $value[ 'table' ], $value[ 'where' ]);
        }

        $limitHandel = $this->orderBy || $this->unions;
        foreach ($this->tableData as $row) {
            /* WHERE */
            if ($this->where && !$this->where->execute($row)) {
                continue;
            }

            /* LIMITE */
            if ($this->limit && !$limitHandel) {
                if ($i++ < $this->offset) {
                    continue;
                }
                if (($i++ > $this->sumLimit) && ($this->limit <= count($return))) {
                    break;
                }
            }

            /* SELECT */
            $return[] = $this->columnNames
                ? array_intersect_key($row, $columnNameKeys)
                : $row;
        }

        /* UNION */
        foreach ($this->unions as $union) {
            /**
             * UNION ALL
             * Pour chaque requêtes unions, on récupère les résultats.
             * On merge puis on supprime les doublons.
             */
            $return = array_merge($return, $union[ 'request' ]->fetchAll());

            /* UNION */
            if ($union[ 'type' ] === self::UNION_SIMPLE) {
                self::arrayUniqueMultidimensional($return);
            }
        }

        /* ORDER BY */
        if ($this->orderBy) {
            $this->executeOrderBy($return, $this->orderBy);
        }

        /* LIMIT */
        if ($this->limit && $limitHandel) {
            $return = array_slice($return, $this->offset, $this->limit);
        }

        $this->init();

        return $return;
    }

    /**
     * Retourne le premier résultat de la requête.
     *
     * @return array Résultat de la requête.
     *
     * @phpstan-return ?RowData
     */
    public function fetch(): ?array
    {
        $fetch = $this->limit(1)->fetchAll();

        return $fetch !== []
            ? $fetch[ 0 ]
            : null;
    }

    /**
     * Retourne les résultats de la requête sous forme de tableau simple,
     * composé uniquement du champ passé en paramètre ou du premier champ sélectionné.
     *
     * @param string      $columnName Nom du champ.
     * @param string|null $key        Clé des valeurs de la liste
     *
     * @throws ColumnsNotFoundException
     *
     * @return array<null|scalar> Liste du champ passé en paramètre.
     */
    public function lists(string $columnName, ?string $key = null): array
    {
        $data = $this->fetchAll();

        return array_column($data, $columnName, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function init(): self
    {
        parent::init();
        $this->allFieldsSchema = [];
        $this->execute         = null;
        $this->tableData       = [];
        $this->where           = null;

        return $this;
    }

    /**
     * Revoie les instances uniques d'un tableau multidimensionnel.
     *
     * @param array $input Table multidimensionnelle.
     *
     * @return void
     */
    protected static function arrayUniqueMultidimensional(array &$input): void
    {
        /* Sérialise les données du tableaux. */
        $serialized = array_map('serialize', $input);

        /* Supprime les doublons sérialisés. */
        $unique = array_unique($serialized);

        /* Redonne les clés au tableau */
        $output = array_intersect_key($input, $unique);

        /* Renvoie le tableau avec ses clés ré-indexé */
        $input = array_values($output);
    }

    /**
     * Execute les jointures.
     *
     * @param string $type
     * @param string $tableName
     * @param Where  $where
     *
     * @return void
     */
    protected function executeJoins(string $type, string $tableName, Where $where): void
    {
        $result       = [];
        $rowTableNull = $this->getRowTableNull($tableName);
        $isLeftJoin   = $type === self::JOIN_LEFT;
        $tableData    = $isLeftJoin
            ? $this->tableData
            : $this->getTableData($tableName);
        $tableJoin    = $isLeftJoin
            ? $this->getTableData($tableName)
            : $this->tableData;

        foreach ($tableData as $row) {
            /* Si les lignes se sont jointes. */
            $addRow = false;
            /* Join les tables. */
            foreach ($tableJoin as $rowJoin) {
                /* Vérifie les conditions. */

                if ($isLeftJoin
                        ? $where->executeJoin($row, $rowJoin)
                        : $where->executeJoin($rowJoin, $row)
                ) {
                    /* Join les lignes si la condition est bonne. */
                    $result[] = $rowJoin + $row;
                    $addRow   = true;
                }
            }

            /*
             * Si aucun resultat n'est trouvé alors la ligne est remplie
             * avec les colonnes de la table jointe avec des valeurs null.
             */
            if (!$addRow) {
                $result[] = array_merge($rowTableNull, $row);
            }
        }
        $this->tableData = $result;
        unset($tableData, $tableJoin, $result);
    }

    /**
     * Trie le tableau en fonction des clés paramétrés.
     *
     * @param array $data    Données à trier.
     * @param array $orderBy Clés sur lesquelles le trie s'exécute.
     *
     * @return void
     */
    protected function executeOrderBy(array &$data, array $orderBy): void
    {
        foreach ($orderBy as &$order) {
            $order = $order === SORT_DESC
                ? -1
                : 1;
        }
        unset($order);

        usort($data, static function ($a, $b) use ($orderBy): int {
            $sorted = 0;

            foreach ($orderBy as $field => $order) {
                if ($a[ $field ] == $b[ $field ]) {
                    continue;
                }

                $sorted = $a[ $field ] > $b[ $field ]
                    ? 1 * $order
                    : -1 * $order;

                if ($sorted !== 0) {
                    break;
                }
            }

            /** @var int $sorted */
            return $sorted;
        });
    }

    /**
     * Exécute l'insertion de données.
     *
     * @throws ColumnsNotFoundException
     *
     * @return void
     */
    protected function executeInsert(): void
    {
        /* Si l'une des colonnes est de type incrémental. */
        $increment = $this->table->getIncrement();
        /* Je charge les colonnes de mon schéma. */
        $fields    = $this->table->getFields();
        $count     = count($this->columnNames);

        foreach ($this->values as $values) {
            /* Pour chaque ligne je vérifie si le nombre de colonne correspond au nombre valeur insérée. */
            if ($count !== count($values)) {
                throw new ColumnsNotFoundException(
                    sprintf(
                        'The number of fields in the selections are different: %s != %s',
                        implode(', ', $this->columnNames),
                        implode(', ', $values)
                    )
                );
            }

            /* Je prépare l'association clé=>valeur pour chaque ligne à insérer. */
            $row = array_combine($this->columnNames, $values);

            $data = [];
            foreach ($fields as $fieldName => $field) {
                /* Si mon champs existe dans le schema. */
                /* @phpstan-ignore-next-line array_combine(): array|false */
                if (isset($row[ $fieldName ])) {
                    $data[ $fieldName ] = $field->filterValue($row[ $fieldName ]);
                    /* Si le champ est de type incrémental et que sa valeur est supérieure à celui enregistrer dans le schéma. */
                    if ($field instanceof IncrementType && ($data[ $fieldName ] > $increment)) {
                        $increment = $data[ $fieldName ];
                    }

                    continue;
                }
                /* Si mon champ n'existe pas et qu'il de type incrémental. */
                if ($field instanceof IncrementType) {
                    ++$increment;
                    $data[ $fieldName ] = $increment;

                    continue;
                }

                /* Sinon on vérifie si une valeur par défaut lui est attribué. */
                $data[ $fieldName ] = $field->getValueDefault();
            }

            $this->tableData[] = $data;
        }
        /* Met à jour les valeurs incrémentales dans le schéma de la table. */
        if ($increment !== null) {
            $this->schema->setIncrement($this->from, $increment);
        }
    }

    /**
     * Exécute le calcul de mise à jour des données.
     *
     * @return void
     */
    protected function executeUpdate(): void
    {
        /* La variable $row est utilisé dans le test d'évaluation. */
        foreach ($this->tableData as &$row) {
            if ($this->where && !$this->where->execute($row)) {
                continue;
            }
            $row = array_merge($row, $this->values[0]);
        }
        unset($row);
    }

    /**
     * Supprime des lignes de la table en fonction des conditions et sauvegarde la table.
     *
     * @return void
     */
    protected function executeDelete(): void
    {
        foreach ($this->tableData as $key => $row) {
            if ($this->where && !$this->where->execute($row)) {
                continue;
            }
            unset($this->tableData[ $key ]);
        }
        $this->tableData = array_values($this->tableData);
    }

    private function insertIntoToString(): string
    {
        $output = sprintf('INSERT INTO %s ', addslashes($this->from));

        if ($this->columnNames) {
            $output .= sprintf('(%s) VALUES%s', addslashes(implode(', ', $this->columnNames)), PHP_EOL);
        }
        $data = array_map(
            function ($values) {
                $data = array_map(
                    function ($item) {
                        return self::getValueToString($item);
                    },
                    $values
                );

                return sprintf('(%s)', implode(', ', $data));
            },
            $this->values
        );
        $output .= implode(',' . PHP_EOL, $data);

        return trim($output) . ';';
    }

    private function deleteToString(): string
    {
        $output = sprintf('DELETE %s ', addslashes($this->from));
        $output .= $this->whereToString();

        return trim($output) . ';';
    }

    private function updateToString(): string
    {
        $output = sprintf('UPDATE %s SET ', addslashes($this->from));
        $data   = [];
        foreach ($this->values[ 0 ] as $key => $value) {
            $data[] = sprintf(
                '%s = %s',
                addslashes($key),
                self::getValueToString($value)
            );
        }
        $output .= implode(', ', $data) . ' ';
        $output .= $this->whereToString();

        return trim($output) . ';';
    }

    private function whereToString(): string
    {
        return $this->where === null
            ? ''
            : sprintf('WHERE %s ', (string) $this->where);
    }

    /**
     * Charge les colonnes de la table courante et des tables de jointure.
     */
    private function loadAllFieldsSchema(): void
    {
        $this->allFieldsSchema = $this->table->getFields();

        foreach ($this->joins as $value) {
            $this->allFieldsSchema = array_merge(
                $this->allFieldsSchema,
                $this->schema->getTableSchema($value[ 'table' ])->getFields()
            );
        }
    }

    /**
     * Vérifie l'existence de la table courante.
     *
     * @throws TableNotFoundException
     */
    private function filterFrom(): void
    {
        if (empty($this->from)) {
            throw new TableNotFoundException();
        }
    }

    /**
     * Vérifie pour tous les champs sélectionnées, leur l'existence à partir du schéma.
     */
    private function filterSelect(): void
    {
        if ($this->columnNames) {
            $this->diffColumnNames($this->columnNames);
        }
    }

    /**
     * Vérifie que la limite est un entier positif.
     *
     * @throws QueryException
     */
    private function filterLimit(): void
    {
        if ($this->limit < self::ALL) {
            throw new QueryException('The limit must be a non-zero positive integer.');
        }
        if ($this->offset < 0) {
            throw new QueryException('The offset must be a non-zero positive integer.');
        }
    }

    /**
     * Vérifie pour toutes les jointures (LEFT JOIN, RIGHT JOIN) et les clauses conditionnées (WHERE),
     * l'existence des champs à partir du schéma.
     */
    private function filterWhere(): void
    {
        $columnNames = [];
        /* Merge les colonnes des conditions de la requête courante. */
        if ($this->where !== null) {
            $columnNames = $this->where->getColumnNames();
        }

        /* Merge toutes les colonnes des conditions de chaque jointure. */
        foreach ($this->joins as $value) {
            $columnNames = array_merge($columnNames, $value[ 'where' ]->getColumnNames());
        }

        if ($columnNames !== []) {
            $this->diffColumnNames($columnNames);
        }
    }

    /**
     * Vérifie pour tous les ORDER BY l'existence des champs à partir du schéma.
     *
     * @throws OperatorNotFound
     */
    private function filterOrderBy(): void
    {
        if ($this->orderBy === []) {
            return;
        }

        $this->diffColumnNames(array_keys($this->orderBy));

        foreach ($this->orderBy as $field => $order) {
            if ($order !== SORT_ASC && $order !== SORT_DESC) {
                throw new OperatorNotFound(
                    sprintf('The sort type of the %s field is not valid.', $field)
                );
            }
        }
    }

    /**
     * Vérifie la cohérence des champs dans chaque requêtes entre les UNIONS.
     *
     * @throws ColumnsNotFoundException
     */
    private function filterUnion(): void
    {
        $count = count($this->columnNames);
        foreach ($this->unions as $union) {
            if ($count === count($union[ 'request' ]->getColumnNames())) {
                continue;
            }

            throw new ColumnsNotFoundException(
                sprintf(
                    'The number of fields in the selections are different: %s != %s',
                    implode(', ', $this->columnNames),
                    implode(', ', $union[ 'request' ]->getColumnNames())
                )
            );
        }
    }

    /**
     * Déclenche une exception si l'un des champs passés en paramètre diffère
     * des champs disponibles dans les tables.
     *
     * @param string[] $columnNames Liste des nom des champs.
     *
     * @throws ColumnsNotFoundException
     */
    private function diffColumnNames(array $columnNames): void
    {
        $diff = array_diff_key(
            array_flip($columnNames),
            $this->allFieldsSchema
        );

        if ($diff !== []) {
            $columnsDiff = array_flip($diff);

            throw new ColumnsNotFoundException(
                sprintf(
                    'Column %s is absent: %s',
                    implode(',', $columnsDiff),
                    $this
                )
            );
        }
    }

    /**
     * Retourne un tableau associatif avec pour clé les champs de la table et pour valeur null.
     * Si des champs existent dans le schéma ils seront rajouté. Fonction utilisée
     * pour les jointures en cas d'absence de résultat.
     *
     * @param string $tableName Nom de la table.
     */
    private function getRowTableNull(string $tableName): array
    {
        /* Prend les noms des champs de la table à joindre. */
        $rowTableKey = $this->schema->getTableSchema($tableName)->getFieldsName();
        /* Prend les noms des champs dans la requête précédente. */
        if ($this->table->getFields() !== []) {
            $rowTableKey = array_merge($rowTableKey, $this->table->getFieldsName());
        }
        /* Utilise les noms pour créer un tableau avec des valeurs null. */
        return array_fill_keys($rowTableKey, null);
    }
}
