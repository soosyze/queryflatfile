<?php

/**
 * Queryflatfile
 *
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\Exception\Query\BadFunctionException;
use Queryflatfile\Exception\Query\ColumnsNotFoundException;
use Queryflatfile\Exception\Query\TableNotFoundException;

/**
 * Réalise des requêtes à partir d'un schéma de données passé en paramètre.
 * Les requêtes se construisent avec le pattern fluent.
 *
 * @author Mathieu NOËL
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
class Request
{
    /**
     * Le nombre de résultat de la requête.
     *
     * @var int
     */
    private $limit = null;

    /**
     * Le décalage des résultats de la requête.
     *
     * @var int
     */
    private $offset = 0;

    /**
     * La configuration de la requête.
     *
     * @var array
     */
    private $request = [];

    /**
     * Le nom de la table courante.
     *
     * @var string
     */
    private $table = '';

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
     * Les colonnes à trier.
     *
     * @var array
     */
    private $orderBy = [];

    /**
     * Le schéma de base de données.
     *
     * @var Schema
     */
    private $schema = null;

    /**
     * Le type d'exécution (delete|update|insert).
     *
     * @var string
     */
    private $execute = null;

    /**
     * Retourne la requête sous forme de liste.
     *
     * @var bool
     */
    private $lists = null;

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
        if (isset($this->request[ 'columns' ])) {
            $output .= 'SELECT ' . implode(', ', $this->request[ 'columns' ]) . ' ';
        } else {
            $output .= 'SELECT * ';
        }
        if (isset($this->table)) {
            $output .= 'FROM ' . $this->table . ' ';
        }
        if (isset($this->request[ 'leftJoin' ])) {
            foreach ($this->request[ 'leftJoin' ] as $value) {
                $output .= 'LEFT JOIN ' . $value[ 'table' ] . ' ON ';
            }
        }
        if (!empty($this->where)) {
            $output .= 'WHERE ';
        }
        if (!empty($this->orderBy)) {
            foreach ($this->orderBy as $table => $order) {
                $output .= 'ORDER BY ' . $table . ' ' . $order . ' ';
            }
        }
        if (isset($this->limit)) {
            $output .= 'LIMIT ' . $this->limit . ' ';
        }
        if ($this->offset != 0) {
            $output .= 'OFFSET ' . $this->offset . ' ';
        }

        if (isset($this->request[ 'union' ])) {
            foreach ($this->request[ 'union' ] as $union) {
                $output .= 'UNION (' . $union[ 'request' ] . ')';
            }
        }
        if (isset($this->request[ 'unionAll' ])) {
            foreach ($this->request[ 'unionAll' ] as $union) {
                $output .= 'UNION ALL (' . $union . ')';
            }
        }

        return $output;
    }

    /**
     * Permet d'utiliser les méthodes de l'objet \Queryflatfile\Where
     * et de personnaliser les closures pour certaines méthodes.
     *
     * @param string $name Nom de la méthode appelée.
     * @param array $arg Pararètre de la méthode.
     *
     * @return $this
     */
    public function __call($name, $arg)
    {
        if ($this->where === null) {
            $this->where = new Where();
        }
        if (method_exists($this->where, $name)) {
            if ($name === 'in' && is_callable($arg[ 1 ])) {
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
        $sch = $this->schema->getSchemaTable($name);

        return $this->schema->read($sch[ 'path' ], $sch[ 'table' ]);
    }

    /**
     * Retourne le nom des champs sélectionnées.
     *
     * @return array
     */
    public function getSelect()
    {
        return isset($this->request[ 'columns' ])
            ? $this->request[ 'columns' ]
            : [];
    }

    /**
     * Retourne les clauses de la requête exécutées.
     *
     * @return string Chaine de caractère à évaluer.
     */
    public function getWhere()
    {
        return $this->where->execute();
    }

    /**
     * Enregistre les champs sélectionnées par la requête.
     * En cas d'absence de selection, la requêtes retournera toutes les champs.
     *
     * @param array $columns Liste ou tableau des noms des colonnes.
     *
     * @return $this
     */
    public function select()
    {
        $columns = func_get_args();

        foreach ($columns as $column) {
            /* Dans le cas ou les colonnes sont normales. */
            if (!is_array($column)) {
                $this->request[ 'columns' ][] = $column;

                continue;
            }
            /* Dans le cas ou les colonnes sont dans un tableau. */
            foreach ($column as $fields) {
                $this->request[ 'columns' ][] = $fields;
            }
        }

        return $this;
    }

    /**
     * Enregistre le nom de la source des données principale de la requête.
     *
     * @param string $table Nom de la table.
     *
     * @return $this
     */
    public function from($table)
    {
        $this->table       = $table;
        $this->tableData   = $this->getTableData($table);
        $this->tableSchema = $this->schema->getSchemaTable($table);

        return $this;
    }

    /**
     * Enregistre une jointure gauche.
     *
     * @param string $table Nom de la table à joindre.
     * @param string|Closure $column Nom de la colonne d'une des tables précédentes
     * ou une closure pour affiner les conditions.
     * @param string|null $operator Opérateur logique ou null pour une closure.
     * @param string|null $value Valeur
     * ou une colonne de la table jointe (au format nom_table.colonne)
     * ou null pour une closure.
     *
     * @return $this
     */
    public function leftJoin($table, $column, $operator = null, $value = null)
    {
        if (is_callable($column)) {
            $where = new Where();
            call_user_func_array($column, [ &$where ]);
        } else {
            $where = ( new Where())
                ->where($column, $operator, $value);
        }

        $this->request[ 'leftJoin' ][ $table ][ 'table' ] = $table;
        $this->request[ 'leftJoin' ][ $table ][ 'where' ] = $where;

        return $this;
    }

    /**
     * Enregistre une jointure droite.
     *
     * @param string $table Nom de la table à joindre
     * @param string|Closure $column Nom de la colonne d'une des tables précédentes
     * ou une closure pour affiner les conditions.
     * @param string|null $operator Opérateur logique ou null pour une closure.
     * @param string|null $value Valeur
     * ou une colonne de la table jointe (au format nom_table.colonne)
     * ou null pour une closure.
     *
     * @return $this
     */
    public function rightJoin($table, $column, $operator = null, $value = null)
    {
        if (is_callable($column)) {
            $where = new Where();
            call_user_func_array($column, [ &$where ]);
        } else {
            $where = ( new Where())
                ->where($column, $operator, $value);
        }

        $this->request[ 'rightJoin' ][ $table ][ 'table' ] = $table;
        $this->request[ 'rightJoin' ][ $table ][ 'where' ] = $where;

        return $this;
    }

    /**
     * Enregistre une limitation et un décalage au retour de la requête.
     *
     * @param int $limit Nombre de résultat maximum à retourner.
     * @param int $offset Décalage sur le jeu de résultat.
     *
     * @return $this
     */
    public function limit($limit, $offset = 0)
    {
        $this->limit  = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Enregistre un trie des résultats de la requête.
     *
     * @param string $columns Colonnes à trier.
     * @param string $order Ordre du trie (asc|desc).
     *
     * @return $this
     */
    public function orderBy($columns, $order = 'asc')
    {
        $this->orderBy[ $columns ] = $order;

        return $this;
    }

    /**
     * Enregistre l'action d'insertion de données.
     * Cette fonction doit-être suivie la fonction values().
     *
     * @param string $table Nom de la table.
     * @param array $columns Liste des champs par ordre d'insertion dans
     * la fonction values().
     *
     * @return $this
     */
    public function insertInto($table, array $columns = null)
    {
        $this->execute = 'insert';
        $this->from($table)->select($columns);

        return $this;
    }

    /**
     * Cette fonction doit suivre la fonction insertInto().
     * Les valeurs doivent suivre le même ordre que les clés précédemment enregistrées.
     *
     * @param array $columns Valeurs des champs.
     *
     * @return $this
     */
    public function values(array $columns)
    {
        $this->request[ 'values' ][] = $columns;

        return $this;
    }

    /**
     * Enregistre l'action de modification de données.
     *
     * @param string $table Nom de la table.
     * @param array $columns key=>value des données à modifier.
     *
     * @return $this
     */
    public function update($table, array $columns = null)
    {
        $this->execute                = 'update';
        $this->from($table);
        $this->request[ 'setUpdate' ] = $columns;
        $this->request[ 'columns' ]   = array_keys($columns);

        return $this;
    }

    /**
     * Enregistre l'action de suppression de données.
     *
     * @return $this
     */
    public function delete()
    {
        $this->execute = 'delete';

        return $this;
    }

    /**
     * Enregistre une union 'simple' entre 2 ensembles.
     * Le résultat de l'union ne possède pas de doublon de ligne.
     *
     * @param \Queryflatfile\Request $request Seconde requête.
     * @param string $type (simple|all) Type d'union.
     *
     * @return $this
     */
    public function union(Request $request, $type = 'simple')
    {
        $this->request[ 'union' ][] = [ 'request' => $request, 'type' => $type ];

        return $this;
    }

    /**
     * Enregistre une union all entre 2 ensembles.
     * Les doublons de lignes figure dans le resultat de l'union.
     *
     * @param \Queryflatfile\Request $request
     *
     * @return $this
     */
    public function unionAll(Request $request)
    {
        return $this->union($request, 'all');
    }

    /**
     * Lance l'exécution d'une requête de création, modification ou suppression.
     *
     * @throws BadFunctionException
     */
    public function execute()
    {
        $this->fetchPrepareFrom();
        $this->loadAllColumnsSchema();
        $this->fetchPrepareSelect();
        $this->fetchPrepareWhere();

        if ($this->execute === 'insert') {
            $this->executeInsert();
        } elseif ($this->execute === 'update') {
            $this->executeUpdate();
        } elseif ($this->execute === 'delete') {
            $this->executeDelete();
        } else {
            throw new BadFunctionException("Only the insert, update and delete functions can be executed.");
        }

        $path = $this->tableSchema[ 'path' ];
        $file = $this->tableSchema[ 'table' ];

        $this->schema->save($path, $file, $this->tableData);
        $this->init();
    }

    /**
     * Retourne tous les résultats de la requête.
     *
     * @return array les données
     */
    public function fetchAll()
    {
        $this->fetchPrepareFrom();
        $this->loadAllColumnsSchema();
        $this->fetchPrepareSelect();
        $this->fetchPrepareWhere();
        $this->fetchPrepareOrderBy();
        $this->fetchPrepareUnion();

        $return = [];
        /* Le pointeur en cas de limite de résultat. */
        $i      = 0;

        /* Exécution des jointures. */
        if (isset($this->request[ 'leftJoin' ])) {
            foreach ($this->request[ 'leftJoin' ] as $value) {
                $this->executeLeftJoin($value[ 'table' ], $value[ 'where' ]);
            }
        }

        if (isset($this->request[ 'rightJoin' ])) {
            foreach ($this->request[ 'rightJoin' ] as $value) {
                $this->executeRightJoin($value[ 'table' ], $value[ 'where' ]);
            }
        }

        $test = !empty($this->where);

        foreach ($this->tableData as $row) {
            /* WHERE */
            if ($test && !$this->where->execute($row)) {
                continue;
            }
            $rowEval = $row;

            /* LIMITE */
            if (!empty($this->limit) && empty($this->orderBy)) {
                if ($i++ < $this->offset) {
                    continue;
                }
                if (($i++ > $this->offset + $this->limit) && ($this->limit <= count($return))) {
                    break;
                }
            }

            /* SELECT */
            if ($this->lists !== null) {
                $return[] = $rowEval[ $this->lists ];
            } elseif (!empty($this->request[ 'columns' ])) {
                $column   = array_flip($this->request[ 'columns' ]);
                $return[] = array_intersect_key($rowEval, $column);
            } else {
                $return[] = $rowEval;
            }
            unset($rowEval);
        }

        /* UNION */
        if (isset($this->request[ 'union' ])) {
            foreach ($this->request[ 'union' ] as $union) {
                /* Si le retour est demandé en liste. */
                $fetch = $this->lists !== null
                    ? $union[ 'request' ]->lists()
                    : $union[ 'request' ]->fetchAll();

                /**
                 * UNION ALL
                 * Pour chaque requêtes unions, on récupère les résultats.
                 * On merge puis on supprime les doublons.
                 */
                $return = array_merge($return, $fetch);

                /* UNION */
                if ($union[ 'type' ] !== 'all') {
                    $return = $this->arrayUniqueMultidimensional($return);
                }
            }
        }

        /* ORDER BY */
        if (!empty($this->orderBy)) {
            $return = $this->executeOrderBy($return, $this->orderBy);

            if (!empty($this->limit)) {
                $return = array_slice($return, $this->offset, $this->limit);
            }
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
        $this->limit = $this->orderBy !== []
            ? null
            : 1;
        $fetch       = $this->fetchAll();

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
     * @return array Liste du champ passé en paramètre.
     *
     * @throws ColumnsNotFoundException
     */
    public function lists($name = null)
    {
        if ($name !== null) {
            $this->lists = $name;
        } elseif (!empty($this->request[ 'columns' ])) {
            $this->lists = $this->request[ 'columns' ][ 0 ];
        } else {
            throw new ColumnsNotFoundException('No key selected for the list.');
        }

        return $this->fetchAll();
    }

    /**
     * Initialise les paramètre de la requête.
     */
    public function init()
    {
        $this->where     = null;
        $this->tableData = null;
        $this->table     = '';
        $this->request   = null;
        $this->limit     = null;
        $this->offset    = 0;
        $this->delete    = false;
        $this->orderBy   = [];
        $this->execute   = null;
        $this->lists     = null;

        return $this;
    }

    /**
     * Revoie les instances uniques d'un tableau multidimensionnel.
     *
     * @param array $input Table multidimensionnelle.
     *
     * @return array Tableau multidimensionnel avec des entrées uniques.
     */
    public static function arrayUniqueMultidimensional(array $input)
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

    /**
     * Exécute le calcule d'une jointure droite entre 2 ensembles.
     *
     * @param string $table Nom de la table à joindre.
     * @param Where $where Condition de la jointure.
     *
     * @return $this
     */
    protected function executeRightJoin($table, Where $where)
    {
        $result       = [];
        $tableJoin    = $this->getTableData($table);
        $rowTableNull = $this->getRowTableNull($table);

        foreach ($tableJoin as $rowJoin) {
            /* Si les lignes se sont jointes. */
            $addRow = false;
            /* Join les tables. */
            foreach ($this->tableData as $row) {
                /* Vérifie les conditions. */
                if ($where->executeJoin($row, $rowJoin)) {
                    /* Join les lignes si la condition est bonne. */
                    $result[] = array_merge($row, $rowJoin);
                    $addRow   = true;
                }
            }

            /**
             * Si aucun résultat n'est trouvé alors la ligne est remplie
             * avec les colonnes de la table jointe avec des valeurs null.
             */
            if (!$addRow) {
                $result[] = array_merge($rowTableNull, $rowJoin);
            }
        }
        $this->tableData = $result;

        return $this;
    }

    /**
     * Exécute le calcule d'une jointure gauche entre 2 ensembles.
     *
     * @param string $table Nom de la table à joindre.
     * @param Where $where Condition de la jointure.
     *
     * @return $this
     */
    protected function executeLeftJoin($table, Where $where)
    {
        $result       = [];
        $tableJoin    = $this->getTableData($table);
        $rowTableNull = $this->getRowTableNull($table);

        foreach ($this->tableData as $row) {
            /* Si les lignes se sont jointes. */
            $addRow = false;
            /* Join les tables. */
            foreach ($tableJoin as $rowJoin) {
                /* Vérifie les conditions. */
                if ($where->executeJoin($row, $rowJoin)) {
                    /* Join les lignes si la condition est bonne. */
                    $result[] = array_merge($rowJoin, $row);
                    $addRow   = true;
                }
            }

            /**
             * Si aucun resultat n'est trouvé alors la ligne est remplie
             * avec les colonnes de la table jointe avec des valeurs null.
             */
            if (!$addRow) {
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

        if (empty($keys)) {
            return $data;
        }

        foreach ($keys as &$value) {
            $value = $value == 'desc' || $value == -1
                ? -1
                : ($value == 'skip' || $value === 0
                ? 0
                : 1);
        }

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
        $increments = $this->getIncrement();

        /* Je charge les colonnes de mon schéma. */
        $schemaColumns = $this->tableSchema[ 'fields' ];

        foreach ($this->request[ 'values' ] as $column) {
            /* Pour chaque ligne je vérifie si le nombre de colonne correspond au nombre valeur insérée. */
            if (count($this->request[ 'columns' ]) != count($column)) {
                throw new ColumnsNotFoundException('keys : ' . implode(',', $this->request[ 'columns' ]) . ' != ' . implode(',', $column));
            }

            /* Je prépare l'association clé=>valeur pour chaque ligne à insérer. */
            $row = array_combine($this->request[ 'columns' ], $column);

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
            $this->schema->setIncrements($this->table, $increments);
        }
    }

    /**
     * Exécute le calcul de mise à jour des données.
     */
    protected function executeUpdate()
    {
        $test = !empty($this->where);

        /* La variable $row est utilisé dans le test d'évaluation. */
        foreach ($this->tableData as &$row) {
            if ($test && !$this->where->execute($row)) {
                continue;
            }
            $row = array_merge($row, $this->request[ 'setUpdate' ]);
        }
    }

    /**
     * Supprime des lignes de la table en fonction des conditions et sauvegarde la table.
     */
    protected function executeDelete()
    {
        $test = !empty($this->where);

        foreach ($this->tableData as $key => $row) {
            if ($test && !$this->where->execute($row)) {
                continue;
            }
            unset($this->tableData[$key]);
        }
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
     * @param int $ix
     *
     * @return bool|int
     */
    private function obIx(array $obj, $ix)
    {
        $size = 0;
        foreach ($obj as $key => $value) {
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
        $schema = $this->tableSchema[ 'fields' ];

        if (isset($this->request[ 'leftJoin' ])) {
            foreach ($this->request[ 'leftJoin' ] as $value) {
                $schemaTable = $this->schema->getSchemaTable($value[ 'table' ]);
                $schema      = array_merge($schema, $schemaTable[ 'fields' ]);
            }
        }

        if (isset($this->request[ 'rightJoin' ])) {
            foreach ($this->request[ 'rightJoin' ] as $value) {
                $schemaTable = $this->schema->getSchemaTable($value[ 'table' ]);
                $schema      = array_merge($schema, $schemaTable[ 'fields' ]);
            }
        }

        $this->request[ 'allColumnsSchema' ] = $schema;
    }

    /**
     * Vérifie l'existence de la table courante.
     *
     * @throws TableNotFoundException
     */
    private function fetchPrepareFrom()
    {
        if ($this->table === '') {
            throw new TableNotFoundException("La table est absente : " . $this);
        }
    }

    /**
     * Vérifie pour tous les champs sélectionnées, leur l'existence à partir du schéma.
     */
    private function fetchPrepareSelect()
    {
        if (isset($this->request[ 'columns' ])) {
            $this->diffColumns($this->request[ 'columns' ]);
        } else {
            /* Si aucunes colonnes selectionnées. */
            $this->request[ 'columns' ] = [];
        }
    }

    /**
     * Vérifie pour toutes les jointures (LEFT JOIN, RIGHT JOIN) et les clauses conditionnées (WHERE),
     * l'existence des champs à partir du schéma.
     */
    private function fetchPrepareWhere()
    {
        $columns = [];
        if (isset($this->request[ 'leftJoin' ])) {
            /* Merge toutes les colonnes des conditions de chaque jointure. */
            foreach ($this->request[ 'leftJoin' ] as $value) {
                $columns = array_merge($columns, $value[ 'where' ]->getColumns());
            }
        }

        if (isset($this->request[ 'rightJoin' ])) {
            /* Merge toutes les colonnes des conditions de chaque jointure. */
            foreach ($this->request[ 'rightJoin' ] as $value) {
                $columns = array_merge($columns, $value[ 'where' ]->getColumns());
            }
        }
        /* Merge les colonnes des conditions de la requête courante. */
        if (isset($this->where)) {
            $columns = array_merge($columns, $this->where->getColumns());
        }

        if (!empty($columns)) {
            $this->diffColumns($columns);
        }
    }

    /**
     * Vérifie pour tous les ORDER BY l'existence des champs à partir du schéma.
     */
    private function fetchPrepareOrderBy()
    {
        if (isset($this->orderBy)) {
            $columns = array_keys($this->orderBy);
            $this->diffColumns($columns);
        }
    }

    /**
     * Vérifie la cohérence des champs dans chaque requêtes entre les UNIONS.
     *
     * @throws ColumnsNotFoundException
     */
    private function fetchPrepareUnion()
    {
        if (!isset($this->request[ 'union' ])) {
            return;
        }

        foreach ($this->request[ 'union' ] as $request) {
            if (count($this->getSelect()) != count($request[ 'request' ]->getSelect())) {
                throw new ColumnsNotFoundException('The number of fields in the selections are different : '
                . implode(',', $this->getSelect())
                . ' != '
                . implode(',', $request[ 'request' ]->getSelect()));
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
        $all = $this->request[ 'allColumnsSchema' ];

        $diff = array_diff_key(array_flip($columns), $all);

        if (!empty($diff)) {
            $columnsDiff = array_flip($diff);

            throw new ColumnsNotFoundException("Column " . implode(',', $columnsDiff) . " is absent : " . $this);
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
        $sch          = $this->schema->getSchemaTable($table);
        /* Prend les noms des champs de la table à joindre. */
        $rowTableKey  = array_keys($sch[ 'fields' ]);
        /* Prend les noms des champs dans la requête précédente. */
        if (isset($this->tableSchema[ 'fields' ])) {
            $rowTableAllKey  = array_keys($this->tableSchema[ 'fields' ]);
            $rowTableKey  = array_merge($rowTableKey, $rowTableAllKey);
        }
        /* Utilise les noms pour créer un tableau avec des valeurs null. */
        return array_fill_keys($rowTableKey, null);
    }
}
