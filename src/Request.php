<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\Exception\Query\BadFunctionException;
use Queryflatfile\Exception\Query\ColumnsNotFoundException;
use Queryflatfile\Exception\Query\QueryException;
use Queryflatfile\Exception\Query\TableNotFoundException;

/**
 * Réalise des requêtes à partir d'un schéma de données passé en paramètre.
 * Les requêtes se construisent avec le pattern fluent.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @method Request where( callable|string $column, null|string $operator = null, null|string $value = null, string $bool = 'and', boolean $not = false ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notWhere( callable|string $column, null|string $operator = null, null|string $value = null ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orWhere( callable|string $column, null|string $operator = null, null|string $value = null ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotWhere( callable|string $column, null|string $operator = null, null|string $value = null ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request between( string $column, mixed $min, mixed $max, string $bool = 'and', boolean $not = false ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orBetween( string $column, mixed $min, mixed $max ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notBetween( string $column, mixed $min, mixed $max ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotBetween( string $column, mixed $min, mixed $max ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request in( string $column, array $values, string $bool = 'and', boolean $not = false ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIn( string $column, array $values ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notIn( string $column, array $values ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotIn( string $column, array $values ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request isNull( string $column, string $condition = '===', string $bool = 'and', boolean $not = false ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIsNull( string $column ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request isNotNull( string $column ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIsNotNull( string $column ) Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request regex( string $column, string $pattern, string $bool = 'and', boolean $not = false ) Alias de la fonction de l'objet Queryflatfile\Where
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
     * @var Where
     */
    private $where = null;

    /**
     * Le schéma de base de données.
     *
     * @var Schema
     */
    private $schema = null;

    /**
     * Réalise une requête sur un schéma de données
     *
     * @param \Queryflatfile\Schema $sch
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
            foreach ($this->orderBy as $table => $order) {
                $output .= " $table " . strtoupper($order) . ',';
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
     * @return $this
     */
    public function __call($name, $arg)
    {
        if ($this->where === null) {
            $this->where = new Where();
        }
        if (method_exists($this->where, $name)) {
            if ($name === 'in' && $arg[ 1 ] instanceof \Closure) {
                $request  = new Request($this->schema);
                call_user_func_array($arg[ 1 ], [ &$request ]);
                $arg[ 1 ] = $request->lists();
            }

            call_user_func_array([ $this->where, $name ], $arg);

            return $this;
        }
    }

    /**
     * Ajoute un schéma de données à notre requête.
     *
     * @param \Queryflatfile\Schema $sch
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
        return $this->schema->read($this->schema->getPath(), $name);
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

        $this->schema->save($this->schema->getPath(), $this->from, $this->tableData);
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
                if (($i++ > $this->offset + $this->limit) && ($this->limit <= count($return))) {
                    break;
                }
            }

            /* SELECT */
            if ($this->columns) {
                $column   = array_flip($this->columns);
                $return[] = array_intersect_key($row, $column);
            } else {
                $return[] = $row;
            }
        }

        /* UNION */
        foreach ($this->union as $union) {
            /* Si le retour est demandé en liste. */
            $fetch = $union[ 'request' ]->fetchAll();

            /**
             * UNION ALL
             * Pour chaque requêtes unions, on récupère les résultats.
             * On merge puis on supprime les doublons.
             */
            $return = array_merge($return, $fetch);

            /* UNION */
            if ($union[ 'type' ] !== self::UNION_ALL) {
                $return = self::arrayUniqueMultidimensional($return);
            }
        }

        /* ORDER BY */
        if ($this->orderBy) {
            $return = $this->executeOrderBy($return, $this->orderBy);
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

        return !empty($fetch[ 0 ])
            ? $fetch[ 0 ]
            : [];
    }

    /**
     * Retourne les résultats de la requête sous forme de tableau simple,
     * composé uniquement du champ passé en paramètre ou du premier champ sélectionné.
     *
     * @param string|null $name Nom du champ.
     *
     * @throws ColumnsNotFoundException
     * @return array                    Liste du champ passé en paramètre.
     */
    public function lists($name = null)
    {
        if ($name !== null && !in_array($name, $this->columns)) {
            $this->columns[] = $name;
        } elseif ($this->columns) {
            $name = $this->columns[ 0 ];
        } else {
            throw new ColumnsNotFoundException('No key selected for the list.');
        }

        $data = $this->fetchAll();
        foreach ($data as &$row) {
            $row = $row[ $name ];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->allColumnsSchema = [];
        $this->execute          = null;
        $this->tableData        = null;
        $this->where            = null;

        return $this;
    }

    /**
     * Revoie les instances uniques d'un tableau multidimensionnel.
     *
     * @param array $input Table multidimensionnelle.
     *
     * @return array Tableau multidimensionnel avec des entrées uniques.
     */
    protected static function arrayUniqueMultidimensional(array $input)
    {
        /* Sérialise les données du tableaux. */
        $serialized = array_map('serialize', $input);

        /* Supprime les doublons sérialisés. */
        $unique = array_unique($serialized);

        /* Redonne les clés au tableau */
        $output = array_intersect_key($input, $unique);

        /* Renvoie le tableau avec ses clés ré-indexé */
        return array_values($output);
    }

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

        return $this;
    }

    /**
     * Trie le tableau en fonction des clés paramétrés.
     *
     * @param array $data Données à trier.
     * @param array $keys Clés sur lesquelles le trie s'exécute.
     *
     * @return array les données triées
     */
    protected function executeOrderBy(array $data, array $keys)
    {
        $keyLength = count($keys);

        if (!$keys) {
            return $data;
        }

        foreach ($keys as &$value) {
            $value = $value == self::DESC || $value == -1
                ? -1
                : ($value == 'skip' || $value === 0
                ? 0
                : 1);
        }
        unset($value);
        usort($data, function ($a, $b) use ($keys, $keyLength) {
            $sorted = 0;
            $ix     = 0;

            while ($sorted === 0 && $ix < $keyLength) {
                $k = $this->obIx($keys, $ix);
                if ($k) {
                    $dir    = $keys[ $k ];
                    $sorted = $this->keySort($a[ $k ], $b[ $k ], $dir);
                    $ix++;
                }
            }

            return $sorted;
        });

        return $data;
    }

    /**
     * Exécute l'insertion de données.
     *
     * @throws ColumnsNotFoundException
     */
    protected function executeInsert()
    {
        /* Si l'une des colonnes est de type incrémental. */
        $increments    = $this->getIncrement();
        /* Je charge les colonnes de mon schéma. */
        $schemaColumns = $this->tableSchema[ 'fields' ];
        $count         = count($this->columns);

        foreach ($this->values as $values) {
            /* Pour chaque ligne je vérifie si le nombre de colonne correspond au nombre valeur insérée. */
            if ($count != count($values)) {
                throw new ColumnsNotFoundException('keys : ' . implode(',', $this->columns) . ' != ' . implode(',', $values));
            }

            /* Je prépare l'association clé=>valeur pour chaque ligne à insérer. */
            $row = array_combine($this->columns, $values);

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
     * Fonction d'appui à orderByExecute().
     *
     * @param array $obj
     * @param int   $ix
     *
     * @return bool|int
     */
    private function obIx(array $obj, $ix)
    {
        $size = 0;
        foreach (array_keys($obj) as $key) {
            if ($size == $ix) {
                return $key;
            }
            $size++;
        }

        return false;
    }

    /**
     * Fonction de trie de orderByExecute
     *
     * @param type $a
     * @param type $b
     * @param type $c
     *
     * @return int
     */
    private function keySort($a, $b, $c = null)
    {
        $d = $c !== null
            ? $c
            : 1;
        if ($a == $b) {
            return 0;
        }

        return ($a > $b)
            ? 1 * $d
            : -1 * $d;
    }

    /**
     * Charge les colonnes de la table courante et des tables de jointure.
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
     */
    private function filterFrom()
    {
        if (empty($this->from) || !\is_string($this->from)) {
            throw new TableNotFoundException("Table {$this->from} is missing.");
        }
    }

    /**
     * Vérifie pour tous les champs sélectionnées, leur l'existence à partir du schéma.
     */
    private function filterSelect()
    {
        if ($this->columns) {
            $this->diffColumns($this->columns);
        } else {
            /* Si aucunes colonnes selectionnées. */
            $this->columns = [];
        }
    }

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
     */
    private function filterOrderBy()
    {
        if ($this->orderBy) {
            $columns = array_keys($this->orderBy);
            $this->diffColumns($columns);
        }
    }

    /**
     * Vérifie la cohérence des champs dans chaque requêtes entre les UNIONS.
     *
     * @throws ColumnsNotFoundException
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
     */
    private function diffColumns(array $columns)
    {
        $all  = $this->allColumnsSchema;
        $diff = array_diff_key(array_flip($columns), $all);

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
