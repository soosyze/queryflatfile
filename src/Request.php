<?php

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

/**
 * Réalise des requêtes à partir d'un schéma de données passé en paramètre.
 * Les requêtes se construisent avec le pattern fluent.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @method Request where( callable|string $column, null|string $operator = null, null|string $value = null ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notWhere( callable|string $column, null|string $operator = null, null|string $value = null ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orWhere( callable|string $column, null|string $operator = null, null|string $value = null ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotWhere( callable|string $column, null|string $operator = null, null|string $value = null ) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request between( string $column, mixed $min, mixed $max ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orBetween( string $column, mixed $min, mixed $max ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notBetween( string $column, mixed $min, mixed $max ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotBetween( string $column, mixed $min, mixed $max ) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request in( string $column, array $values) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIn( string $column, array $values ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notIn( string $column, array $values ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotIn( string $column, array $values ) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request isNull( string $column, string $condition = '===' ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIsNull( string $column ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request isNotNull( string $column ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIsNotNull( string $column ) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request regex( string $column, string $pattern ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orRegex( string $column, string $pattern ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notRegex( string $column, string $pattern ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotRegex( string $column, string $pattern ) Alias de la fonction de l'objet Queryflatfile\Where
 */
class Request extends RequestHandler
{
    /**
     * Toutes les configurations du schéma des champs utilisés.
     *
     * @var array
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
     * @var array
     */
    private $tableSchema = [];

    /**
     * Les conditions de la requête.
     *
     * @var Where|null
     */
    private $where = null;

    /**
     * Le schéma de base de données.
     *
     * @var Schema
     */
    private $schema;

    /**
     * Réalise une requête sur un schéma de données
     *
     * @param Schema $sch
     */
    public function __construct(Schema $sch)
    {
        $this->schema = $sch;
    }

    /**
     * Retourne les paramètre de la requête en format pseudo SQL.
     *
     * @return string
     */
    public function __toString()
    {
        $output = '';
        if ($this->columns) {
            $output .= 'SELECT ' . implode(', ', $this->columns) . ' ';
        } else {
            $output .= 'SELECT * ';
        }
        if ($this->from) {
            $output .= "FROM $this->from ";
        }
        foreach ($this->joins as $value) {
            $output .= strtoupper($value[ 'type' ]) . " JOIN {$value[ 'table' ]} ON {$value[ 'where' ]}";
        }
        if ($this->where) {
            $output .= 'WHERE ' . (string) $this->where;
        }
        foreach ($this->union as $union) {
            $output .= $union[ 'type' ] === self::UNION_SIMPLE
                ? 'UNION'
                : 'UNION ALL';
            $output .= " ({$union[ 'request' ]}) ";
        }
        if ($this->orderBy) {
            $output .= 'ORDER BY ';
            foreach ($this->orderBy as $field => $order) {
                $output .= $field . ' ' . ($order === SORT_ASC
                    ? 'ASC,'
                    : 'DESC,');
            }
            $output = substr($output, 0, -1) . ' ';
        }
        if ($this->limit) {
            $output .= "LIMIT $this->limit ";
        }
        if ($this->offset) {
            $output .= "OFFSET $this->offset ";
        }
        $output = substr($output, 0, -1) . ';';

        return htmlspecialchars($output);
    }

    /**
     * Permet d'utiliser les méthodes de l'objet \Queryflatfile\Where
     * et de personnaliser les closures pour certaines méthodes.
     *
     * @param string $name Nom de la méthode appelée.
     * @param array  $arg  Pararètre de la méthode.
     *
     * @throws \BadMethodCallException
     * @return $this
     */
    public function __call($name, $arg)
    {
        if ($this->where === null) {
            $this->where = new Where();
        }
        if (!method_exists($this->where, $name)) {
            throw new \BadMethodCallException("The $name method not exist");
        }

        call_user_func_array([ $this->where, $name ], $arg);

        return $this;
    }

    /**
     * Ajoute un schéma de données à notre requête.
     *
     * @param Schema $sch
     *
     * @return $this
     */
    public function setSchema(Schema $sch)
    {
        $this->schema = $sch;

        return $this;
    }

    /**
     * Lit les données d'une table.
     *
     * @param string $name Nom de la table.
     *
     * @return array Données de la table.
     */
    public function getTableData($name)
    {
        return $this->schema->read($name);
    }

    /**
     * Enregistre le nom de la source des données principale de la requête.
     *
     * @param string $from Nom de la table.
     *
     * @return $this
     */
    public function from($from)
    {
        parent::from($from);
        $this->tableSchema = $this->schema->getSchemaTable($from);
        $this->tableData   = $this->getTableData($from);

        return $this;
    }

    /**
     * Lance l'exécution d'une requête de création, modification ou suppression.
     *
     * @throws BadFunctionException
     */
    public function execute()
    {
        $this->filterFrom();
        $this->loadAllColumnsSchema();
        $this->filterSelect();
        $this->filterWhere();

        if ($this->execute === 'insert') {
            $this->executeInsert();
        } elseif ($this->execute === 'update') {
            $this->executeUpdate();
        } elseif ($this->execute === 'delete') {
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
    public function fetchAll()
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
    public function fetch()
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
     * @return array                    Liste du champ passé en paramètre.
     */
    public function lists($name, $key = null)
    {
        $data = $this->fetchAll();

        return array_column($data, $name, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function init()
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
    protected static function arrayUniqueMultidimensional(array &$input)
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
     * @return void;
     */
    protected function executeJoins($type, $table, Where $where)
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
    protected function executeOrderBy(array &$data, array $orderBy)
    {
        foreach ($orderBy as &$order) {
            $order = $order === SORT_DESC
                ? -1
                : 1;
        }
        unset($order);

        usort($data, static function ($a, $b) use ($orderBy) {
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

            return $sorted;
        });
    }

    /**
     * Exécute l'insertion de données.
     *
     * @throws ColumnsNotFoundException
     * @return void
     */
    protected function executeInsert()
    {
        /* Si l'une des colonnes est de type incrémental. */
        $increments    = $this->getIncrement();
        /* Je charge les colonnes de mon schéma. */
        $schemaColumns = $this->tableSchema[ 'fields' ];

        foreach ($this->values as $values) {
            /* Pour chaque ligne je vérifie si le nombre de colonne correspond au nombre valeur insérée. */
            try {
                /* Je prépare l'association clé=>valeur pour chaque ligne à insérer. */
                $row = array_combine($this->columns, $values);
            } catch (\Exception $ex) {
                throw new ColumnsNotFoundException('keys : ' . implode(',', $this->columns) . ' != ' . implode(',', $values));
            }

            $data = [];
            foreach ($schemaColumns as $field => $arg) {
                /* Si mon champs existe dans le schema. */
                if (isset($row[ $field ])) {
                    $data[ $field ] = TableBuilder::checkValue($field, $arg[ 'type' ], $row[ $field ], $arg);
                    /* Si le champ est de type incrémental et que sa valeur est supérieure à celui enregistrer dans le schéma. */
                    if ($arg[ 'type' ] === 'increments' && ($data[ $field ] > $increments)) {
                        $increments = $data[ $field ];
                    }

                    continue;
                }

                /* Si mon champ n'existe pas et qu'il de type incrémental. */
                if (!isset($row[ $field ]) && $arg[ 'type' ] === 'increments') {
                    $increments++;
                    $data[ $field ] = $increments;

                    continue;
                }

                /* Sinon on vérifie si une valeur par défaut lui est attribué. */
                $data[ $field ] = $this->schema->getValueDefault($field, $arg);
            }

            $this->tableData[] = $data;
        }
        /* Met à jour les valeurs incrémentales dans le schéma de la table. */
        if ($increments !== null) {
            $this->schema->setIncrements($this->from, $increments);
        }
    }

    /**
     * Exécute le calcul de mise à jour des données.
     *
     * @return void
     */
    protected function executeUpdate()
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
    protected function executeDelete()
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
     * Retourne les champs incrémental de la courrante.
     *
     * @return array Tableau des champ incrémentales et leur valeur.
     */
    protected function getIncrement()
    {
        return $this->tableSchema[ 'increments' ];
    }

    /**
     * Charge les colonnes de la table courante et des tables de jointure.
     *
     * @return void
     */
    private function loadAllColumnsSchema()
    {
        $this->allColumnsSchema = $this->tableSchema[ 'fields' ];

        foreach ($this->joins as $value) {
            $this->allColumnsSchema = array_merge(
                $this->allColumnsSchema,
                $this->schema->getSchemaTable($value[ 'table' ])[ 'fields' ]
            );
        }
    }

    /**
     * Vérifie l'existence de la table courante.
     *
     * @throws TableNotFoundException
     * @return void
     */
    private function filterFrom()
    {
        if (empty($this->from) || !\is_string($this->from)) {
            throw new TableNotFoundException("Table {$this->from} is missing.");
        }
    }

    /**
     * Vérifie pour tous les champs sélectionnées, leur l'existence à partir du schéma.
     *
     * @return void
     */
    private function filterSelect()
    {
        if ($this->columns) {
            $this->diffColumns($this->columns);
        }
    }

    /**
     * Vérifie que la limite est un entier positif.
     *
     * @throws QueryException
     * @return void
     */
    private function filterLimit()
    {
        if (!\is_int($this->limit) || $this->limit < self::ALL) {
            throw new QueryException('The limit must be a non-zero positive integer.');
        }
        if (!\is_int($this->offset) || $this->offset < 0) {
            throw new QueryException('The offset must be a non-zero positive integer.');
        }
    }

    /**
     * Vérifie pour toutes les jointures (LEFT JOIN, RIGHT JOIN) et les clauses conditionnées (WHERE),
     * l'existence des champs à partir du schéma.
     *
     * @return void
     */
    private function filterWhere()
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
     *
     * @return void
     */
    private function filterOrderBy()
    {
        if ($this->orderBy === []) {
            return;
        }

        $columns = array_keys($this->orderBy);
        $this->diffColumns($columns);

        foreach ($this->orderBy as $field => $order) {
            if ($order !== SORT_ASC && $order !== SORT_DESC) {
                throw new OperatorNotFound("The sort type of the $field field is not valid.");
            }
        }
    }

    /**
     * Vérifie la cohérence des champs dans chaque requêtes entre les UNIONS.
     *
     * @throws ColumnsNotFoundException
     * @return void
     */
    private function filterUnion()
    {
        $count = count($this->columns);
        foreach ($this->union as $request) {
            if ($count != count($request[ 'request' ]->columns)) {
                throw new ColumnsNotFoundException('The number of fields in the selections are different : '
                . implode(',', $this->columns)
                . ' != '
                . implode(',', $request[ 'request' ]->columns));
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
     * @return void
     */
    private function diffColumns(array $columns)
    {
        $diff = array_diff_key(
            array_flip($columns),
            $this->allColumnsSchema
        );

        if ($diff) {
            $columnsDiff = array_flip($diff);

            throw new ColumnsNotFoundException('Column ' . implode(',', $columnsDiff) . ' is absent : ' . $this);
        }
    }

    /**
     * Retourne un tableau associatif avec pour clé les champs de la table et pour valeur null.
     * Si des champs existent dans le schéma ils seront rajouté. Fonction utilisée
     * pour les jointures en cas d'absence de résultat.
     *
     * @param string $table Nom de la table.
     *
     * @return array
     */
    private function getRowTableNull($table)
    {
        /* Le schéma de la table à joindre. */
        $sch         = $this->schema->getSchemaTable($table);
        /* Prend les noms des champs de la table à joindre. */
        $rowTableKey = array_keys($sch[ 'fields' ]);
        /* Prend les noms des champs dans la requête précédente. */
        if (isset($this->tableSchema[ 'fields' ])) {
            $rowTableAllKey = array_keys($this->tableSchema[ 'fields' ]);
            $rowTableKey    = array_merge($rowTableKey, $rowTableAllKey);
        }
        /* Utilise les noms pour créer un tableau avec des valeurs null. */
        return array_fill_keys($rowTableKey, null);
    }
}
