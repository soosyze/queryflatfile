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
 */
class Request extends RequestHandler
{
    /**
     * Toutes les configurations du schéma des champs utilisés.
     *
     * @var array<Field>
     */
    private $allColumnsSchema;

    /**
     * Les données de la table.
     *
     * @var array
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
        $output = '';
        if ($this->execute) {
            $output .= strtoupper($this->execute) . ' ';
        } else {
            $output .= sprintf('SELECT %s ', $this->columns ? addslashes(implode(', ', $this->columns)) : '*');
        }
        if ($this->from) {
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
        if ($this->where) {
            $output .= sprintf('WHERE %s ', (string) $this->where);
        }
        foreach ($this->union as $union) {
            $type = $union[ 'type' ] === self::UNION_SIMPLE ? 'UNION' : 'UNION ALL';
            $subRequest = trim((string) $union[ 'request' ], ';');
            $output .= sprintf('%s %s ', $type, $subRequest);
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
        if ($this->limit) {
            $output .= sprintf('LIMIT %d ', (string) $this->limit);
        }
        if ($this->offset) {
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
     * @param string $name Nom de la table.
     *
     * @return array Données de la table.
     */
    public function getTableData(string $name): array
    {
        return $this->schema->read($name);
    }

    /**
     * {@inheritdoc}
     */
    public function from(string $table): self
    {
        parent::from($table);
        $this->table     = $this->schema->getTableSchema($table);
        $this->tableData = $this->getTableData($table);

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
        $this->loadAllColumnsSchema();
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
     */
    public function fetchAll(): array
    {
        $this->filterFrom();
        $this->loadAllColumnsSchema();
        $this->filterSelect();
        $this->filterWhere();
        $this->filterUnion();
        $this->filterOrderBy();
        $this->filterLimit();

        $return = [];
        /* Le pointeur en cas de limite de résultat. */
        $i      = 0;

        $column = array_flip($this->columns);

        /*
         * Exécution des jointures.
         * La réunion et l'intersection des ensembles sont soumis à la loi interne * et donc commutative.
         */
        foreach ($this->joins as $value) {
            $this->executeJoins($value[ 'type' ], $value[ 'table' ], $value[ 'where' ]);
        }

        $limitHandel = $this->orderBy || $this->union;
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
            $return[] = $this->columns
                ? array_intersect_key($row, $column)
                : $row;
        }

        /* UNION */
        foreach ($this->union as $union) {
            /* Si le retour est demandé en liste. */
            $fetchAll = $union[ 'request' ]->fetchAll();

            /**
             * UNION ALL
             * Pour chaque requêtes unions, on récupère les résultats.
             * On merge puis on supprime les doublons.
             */
            $return = array_merge($return, $fetchAll);

            /* UNION */
            if ($union[ 'type' ] !== self::UNION_ALL) {
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
     */
    public function fetch(): array
    {
        $fetch = $this->limit(1)->fetchAll();

        return $fetch
            ? $fetch[ 0 ]
            : [];
    }

    /**
     * Retourne les résultats de la requête sous forme de tableau simple,
     * composé uniquement du champ passé en paramètre ou du premier champ sélectionné.
     *
     * @param string      $name Nom du champ.
     * @param string|null $key  Clé des valeurs de la liste
     *
     * @throws ColumnsNotFoundException
     *
     * @return array Liste du champ passé en paramètre.
     */
    public function lists(string $name, ?string $key = null): array
    {
        $data = $this->fetchAll();

        return array_column($data, $name, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function init(): self
    {
        parent::init();
        $this->allColumnsSchema = [];
        $this->execute          = null;
        $this->tableData        = [];
        $this->where            = null;

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
     * @param string $table
     * @param Where  $where
     *
     * @return void
     */
    protected function executeJoins(string $type, string $table, Where $where): void
    {
        $result       = [];
        $rowTableNull = $this->getRowTableNull($table);
        $left         = $type === self::JOIN_LEFT;
        $tableData    = $left
            ? $this->tableData
            : $this->getTableData($table);
        $tableJoin    = $left
            ? $this->getTableData($table)
            : $this->tableData;

        foreach ($tableData as $row) {
            /* Si les lignes se sont jointes. */
            $addRow = false;
            /* Join les tables. */
            foreach ($tableJoin as $rowJoin) {
                /* Vérifie les conditions. */

                if ($left
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
            if ($addRow === false) {
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
        $count     = count($this->columns);

        foreach ($this->values as $values) {
            /* Pour chaque ligne je vérifie si le nombre de colonne correspond au nombre valeur insérée. */
            if ($count !== count($values)) {
                throw new ColumnsNotFoundException(
                    sprintf(
                        'The number of fields in the selections are different: %s != %s',
                        implode(', ', $this->columns),
                        implode(', ', $values)
                    )
                );
            }

            /* Je prépare l'association clé=>valeur pour chaque ligne à insérer. */
            $row = array_combine($this->columns, $values);
            if ($row == false) {
                throw new ColumnsNotFoundException(
                    sprintf(
                        'The number of fields in the selections are different: %s != %s',
                        implode(', ', $this->columns),
                        implode(', ', $values)
                    )
                );
            }

            $data = [];
            foreach ($fields as $fieldName => $field) {
                /* Si mon champs existe dans le schema. */
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
            $row = array_merge($row, $this->values);
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

    /**
     * Charge les colonnes de la table courante et des tables de jointure.
     */
    private function loadAllColumnsSchema(): void
    {
        $this->allColumnsSchema = $this->table->getFields();

        foreach ($this->joins as $value) {
            $this->allColumnsSchema = array_merge(
                $this->allColumnsSchema,
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
        if ($this->columns) {
            $this->diffColumns($this->columns);
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
        $columns = [];
        /* Merge toutes les colonnes des conditions de chaque jointure. */
        foreach ($this->joins as $value) {
            $columns = array_merge($columns, $value[ 'where' ]->getColumns());
        }

        /* Merge les colonnes des conditions de la requête courante. */
        if ($this->where) {
            $columns = array_merge($columns, $this->where->getColumns());
        }

        if ($columns) {
            $this->diffColumns($columns);
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

        $columns = array_keys($this->orderBy);
        $this->diffColumns($columns);

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
        $count = count($this->columns);
        foreach ($this->union as $request) {
            if ($count != count($request[ 'request' ]->columns)) {
                throw new ColumnsNotFoundException(
                    sprintf(
                        'The number of fields in the selections are different: %s != %s',
                        implode(', ', $this->columns),
                        implode(', ', $request[ 'request' ]->columns)
                    )
                );
            }
        }
    }

    /**
     * Déclenche une exception si l'un des champs passés en paramètre diffère
     * des champs disponibles dans les tables.
     *
     * @param array $columns Liste des champs.
     *
     * @throws ColumnsNotFoundException
     */
    private function diffColumns(array $columns): void
    {
        $diff = array_diff_key(
            array_flip($columns),
            $this->allColumnsSchema
        );

        if ($diff) {
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
     * @param string $table Nom de la table.
     */
    private function getRowTableNull(string $table): array
    {
        /* Prend les noms des champs de la table à joindre. */
        $rowTableKey = $this->schema->getTableSchema($table)->getFieldsName();
        /* Prend les noms des champs dans la requête précédente. */
        if ($this->table->getFields() !== []) {
            $rowTableAllKey = $this->table->getFieldsName();
            $rowTableKey    = array_merge($rowTableKey, $rowTableAllKey);
        }
        /* Utilise les noms pour créer un tableau avec des valeurs null. */
        return array_fill_keys($rowTableKey, null);
    }
}
