<?php

/**
 * Class Request | src/Request.php
 * 
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * 
 */

namespace Queryflatfile;

use Queryflatfile\Exception\Query\BadFunctionException,
    Queryflatfile\Exception\Query\ColumnsNotFoundException,
    Queryflatfile\Exception\Query\ColumnsValueException,
    Queryflatfile\Exception\Query\TableNotFoundException;

/**
 * Réalise des requêtes à partir d'un schéma de données passé en paramètre.
 * Les requêtes se construisent avec le pattern fluent.
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
     * Le nombre de résultat de la requête
     * @var integer 
     */
    private $limit = null;

    /**
     * Le décalage des résultats de la requête
     * @var integer 
     */
    private $offset = 0;

    /**
     * La configuration de la requête
     * @var array 
     */
    private $request = [];

    /**
     * Le nom de la table courante
     * @var string 
     */
    private $table = '';

    /**
     * Les données de la table
     * @var array 
     */
    private $tableData = [];

    /**
     * Le schéma des tables utilisées par la requête
     * @var array 
     */
    private $tableSchema = [];

    /**
     * Les conditions de la requête
     * @var Where
     */
    private $where;

    /**
     * Les colonnes à trier
     * @var array 
     */
    private $orderBy = [];

    /**
     * Le schéma de base de données
     * @var Schema 
     */
    private $schema = null;

    /**
     * Le type d'exécution (delete/update/insert)
     * @var string 
     */
    private $execute = null;

    /**
     * Retourne la requête sous forme de liste
     * @var boolean 
     */
    private $lists = null;

    /**
     * Réalise une requête sur un schéma de données
     * 
     * @param \Queryflatfile\Schema $sch
     */
    public function __construct( Schema $sch )
    {
        $this->where  = new Where();
        $this->schema = $sch;
    }

    /**
     * Ajoute un schéma de données à notre requête.
     * 
     * @param \Queryflatfile\Schema $sch
     * 
     * @return $this
     */
    public function setSchema( Schema $sch )
    {
        $this->schema = $sch;
        return $this;
    }

    /**
     * Lit les données d'une table.
     * 
     * @param string $name nom de la table
     * 
     * @return array les données de la table
     */
    public function getTableData( $name )
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
     * @return string la chaine de caractère à évaluer
     */
    public function getWhere()
    {
        return $this->where->execute();
    }

    /**
     * Enregistre les champs sélectionnées par la requête.
     * En cas d'absence de selection, la requêtes retournera toutes les champs.
     * 
     * @param array $columns liste ou tableau des noms des colonnes
     * 
     * @return $this
     */
    public function select()
    {
        $columns = func_get_args();

        foreach( $columns as $column )
        {
            /* Dans le cas ou les colonnes sont normales */
            if( !is_array($column) )
            {
                $this->request[ 'columns' ][] = $column;
                continue;
            }
            /* Dans le cas ou les colonnes sont dans un tableau */
            foreach( $column as $fields )
            {
                $this->request[ 'columns' ][] = $fields;
            }
        }

        return $this;
    }

    /**
     * Enregistre le nom de la source des données principale de la requête.
     * 
     * @param string $table nom de la table
     * 
     * @return $this
     */
    public function from( $table )
    {
        $this->table       = $table;
        $this->tableData   = $this->getTableData($table);
        $this->tableSchema = $this->schema->getSchemaTable($table);

        return $this;
    }

    /**
     * Enregistre une jointure gauche.
     * 
     * @param string $table le nom de la table à joindre
     * @param string|Closure $column le nom de la colonne d'une des tables précédentes
     * ou une closure pour affiner les conditions
     * @param string|null $operator l'opérateur logique ou null pour une closure
     * @param string|null $value valeur
     * ou une colonne de la table jointe (au format nom_table.colonne)
     * ou null pour une closure
     * 
     * @return $this
     */
    public function leftJoin( $table, $column, $operator = null, $value = null )
    {
        if( is_callable($column) )
        {
            $where = new Where();
            call_user_func_array($column, [ &$where ]);
        }
        else
        {
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
     * @param string $table le nom de la table à joindre
     * @param string|Closure $column le nom de la colonne d'une des tables précédentes
     * ou une closure pour affiner les conditions
     * @param string|null $operator l'opérateur logique ou null pour une closure
     * @param string|null $value valeur
     * ou une colonne de la table jointe (au format nom_table.colonne)
     * ou null pour une closure
     * 
     * @return $this
     */
    public function rightJoin( $table, $column, $operator = null, $value = null )
    {
        if( is_callable($column) )
        {
            $where = new Where();
            call_user_func_array($column, [ &$where ]);
        }
        else
        {
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
     * @param int $limit nombre de résultat maximum à retourner
     * @param int $offset décalage sur le jeu de résultat
     * 
     * @return $this
     */
    public function limit( $limit, $offset = 0 )
    {
        $this->limit  = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Enregistre un trie des résultats de la requête.
     * 
     * @param string $columns les colonnes à trier
     * @param string $order l'ordre du trie (asc|desc)
     * 
     * @return $this
     */
    public function orderBy( $columns, $order = 'asc' )
    {
        $this->orderBy[ $columns ] = $order;

        return $this;
    }

    /**
     * Enregistre l'action d'insertion de données.
     * Cette fonction doit-être suivie la fonction values().
     * 
     * @param string $table nom de la table
     * @param array $columns la liste des champs par ordre d'insertion dans 
     * la fonction values()
     * 
     * @return $this
     */
    public function insertInto( $table, array $columns = null )
    {
        $this->execute = 'insert';
        $this->from($table)->select($columns);

        return $this;
    }

    /**
     * Cette fonction doit suivre la fonction insertInto(). 
     * Les valeurs doivent suivre le même ordre que les clés précédemment enregistrées.
     * 
     * @param array $columns les valeurs des champs
     * 
     * @return $this
     */
    public function values( array $columns )
    {
        $this->request[ 'values' ][] = $columns;

        return $this;
    }

    /**
     * Enregistre l'action de modification de données. 
     * 
     * @param string $table nom de la table
     * @param array $columns key=>value des données à modifier
     * 
     * @return $this
     */
    public function update( $table, array $columns = null )
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
     * @param \Queryflatfile\Request $request la seconde requête
     * @param string $type ('simple'|'all') le type d'union
     * 
     * @return $this
     */
    public function union( Request $request, $type = 'simple' )
    {
        $this->request[ 'union' ][] = [ 'request' => $request, 'type' => $type ];

        return $this;
    }

    /**
     * Enregistre une union 'all' entre 2 ensembles.
     * Les doublons de lignes figure dans le resultat de l'union.
     * 
     * @param \Queryflatfile\Request $request
     * 
     * @return $this
     */
    public function unionAll( Request $request )
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

        if( $this->execute === 'insert' )
        {
            $this->executeInsert();
        }
        else if( $this->execute === 'update' )
        {
            $this->executeUpdate();
        }
        else if( $this->execute === 'delete' )
        {
            $this->executeDelete();
        }
        else
        {
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
        /* Le pointeur en cas de limite de résultat */
        $i      = 0;

        /* Exécution des jointures */
        if( isset($this->request[ 'leftJoin' ]) )
        {
            foreach( $this->request[ 'leftJoin' ] as $value )
            {
                $this->executeLeftJoin($value[ 'table' ], $value[ 'where' ]);
            }
        }

        if( isset($this->request[ 'rightJoin' ]) )
        {
            foreach( $this->request[ 'rightJoin' ] as $value )
            {
                $this->executeRightJoin($value[ 'table' ], $value[ 'where' ]);
            }
        }

        /* Exécution des conditions */
        if( !empty($this->where) )
        {
            $testEval = $this->where->execute();
        }

        foreach( $this->tableData as $row )
        {
            /* LIMITE */
            if( !empty($this->limit) )
            {
                if( $i++ < $this->offset )
                {
                    continue;
                }
                if( ($i++ > $this->offset + $this->limit) && ($this->limit <= count($return)) )
                {
                    break;
                }
            }

            /* WHERE */
            if( isset($testEval) )
            {
                $test = eval('return ' . $testEval . ';');

                if( $test )
                {
                    $rowEval = $row;
                }
                else
                {
                    continue;
                }
            }
            else
            {
                $rowEval = $row;
            }

            /* SELECT */
            if( $this->lists !== null )
            {
                $return[] = $rowEval[ $this->lists ];
            }
            else if( !empty($this->request[ 'columns' ]) )
            {
                $column   = array_flip($this->request[ 'columns' ]);
                $return[] = array_intersect_key($rowEval, $column);
            }
            else
            {
                $return[] = $rowEval;
            }
            unset($rowEval);
        }

        /* UNION */
        if( isset($this->request[ 'union' ]) )
        {
            foreach( $this->request[ 'union' ] as $union )
            {
                /* Si le retour est demandé en liste */
                $fetch = $this->lists !== null
                    ? $union[ 'request' ]->lists()
                    : $union[ 'request' ]->fetchAll();

                /**
                 * UNION ALL
                 * Pour chaque requêtes unions, on récupère les résultats
                 * On merge puis on supprime les doublons
                 */
                $return = array_merge($return, $fetch);

                /* UNION */
                if( $union[ 'type' ] !== 'all' )
                {
                    $return = $this->arrayUniqueMultidimensional($return);
                }
            }
        }

        /* ORDER BY */
        if( !empty($this->orderBy) )
        {
            $return = $this->executeOrderBy($return, $this->orderBy);
        }

        $this->init();
        return $return;
    }

    /**
     * Retourne le premier résultat de la requête.
     * 
     * @return array résultat de la requête
     */
    public function fetch()
    {
        $this->limit = 1;
        $fetch       = $this->fetchAll();
        return !empty($fetch[ 0 ])
            ? $fetch[ 0 ]
            : [];
    }

    /**
     * Retourne les résultats de la requête sous forme de tableau simple,
     * composé uniquement du champ passé en paramètre ou du premier champ sélectionné.
     * 
     * @param string $name nom du champ
     * 
     * @return array la liste du champ passé en paramètre
     * 
     * @throws ColumnsNotFoundException
     */
    public function lists( $name = null )
    {
        if( $name !== null )
        {
            $this->lists = $name;
        }
        else if( !empty($this->request[ 'columns' ]) )
        {
            $this->lists = $this->request[ 'columns' ][ 0 ];
        }
        else
        {
            throw new ColumnsNotFoundException('No key selected for the list.');
        }
        return $this->fetchAll();
    }

    /**
     * Initialise les paramètre de la requête
     */
    public function init()
    {
        $this->where     = new Where();
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
     * Retourne les paramètre de la requête en format pseudo SQL
     * 
     * @return string la requête
     */
    public function __toString()
    {
        $output = '';
        if( isset($this->request[ 'columns' ]) )
        {
            $output .= 'SELECT ' . implode(', ', $this->request[ 'columns' ]) . ' ';
        }
        else
        {
            $output .= 'SELECT * ';
        }
        if( isset($this->table) )
        {
            $output .= 'FROM ' . $this->table . ' ';
        }
        if( isset($this->request[ 'leftJoin' ]) )
        {
            foreach( $this->request[ 'leftJoin' ] as $value )
            {
                $output .= 'LEFT JOIN ' . $value[ 'table' ] . ' ON ' . $value[ 'where' ]->executeJoin() . ' ';
            }
        }
        if( !empty($this->where) )
        {
            $output .= 'WHERE ' . $this->where->execute() . ' ';
        }
        if( !empty($this->orderBy) )
        {
            foreach( $this->orderBy as $table => $order )
            {
                $output .= 'ORDER BY ' . $table . ' ' . $order . ' ';
            }
        }
        if( isset($this->limit) )
        {
            $output .= 'LIMIT ' . $this->limit . ' ';
        }
        if( $this->offset != 0 )
        {
            $output .= 'OFFSET ' . $this->offset . ' ';
        }

        if( isset($this->request[ 'union' ]) )
        {
            foreach( $this->request[ 'union' ] as $union )
            {
                $output .= 'UNION (' . $union[ 'request' ] . ')';
            }
        }
        if( isset($this->request[ 'unionAll' ]) )
        {
            foreach( $this->request[ 'unionAll' ] as $union )
            {
                $output .= 'UNION ALL (' . $union . ')';
            }
        }
        return $output;
    }

    /**
     * Permet d'utiliser les méthodes de l'objet \Queryflatfile\Where
     * et de personnaliser les closures pour certaines méthodes.
     * 
     * @param string $name nom de la méthode appelée
     * @param array $arg pararètre de la méthode
     * 
     * @return $this
     */
    public function __call( $name, $arg )
    {
        if($this->where === null)
        {
            $this->where = new Where();
        }
        if( method_exists($this->where, $name) )
        {
            if( $name === 'in' && is_callable($arg[ 1 ]) )
            {
                $request  = new Request($this->schema);
                call_user_func_array($arg[ 1 ], [ &$request ]);
                $arg[ 1 ] = $request->lists();
            }

            call_user_func_array([ $this->where, $name ], $arg);
            return $this;
        }
    }

    /**
     * Exécute le calcule d'une jointure droite entre 2 ensembles.
     * 
     * @param string $table nom de la table à joindre
     * @param Where $where condition de la jointure
     * 
     * @return $this
     */
    protected function executeRightJoin( $table, Where $where )
    {
        $result    = [];
        $tableJoin = $this->getTableData($table);
        $testEval  = $where->executeJoin();

        /* Si les lignes se sont jointes */
        $addRow       = false;
        /* Le schéma de la table à joindre */
        $sch          = $this->tableSchema;
        /* Prend les noms des champs de la table à joindre */
        $rowTableKey  = array_keys($sch[ 'fields' ]);
        /* Utilise les nom pour créer un tableau avec des valeurs null */
        $rowTableNull = array_fill_keys($rowTableKey, null);

        foreach( $tableJoin as $rowJoin )
        {
            /* Join les tables */
            foreach( $this->tableData as $row )
            {
                /* Vérifie les conditions */
                $test = eval('return ' . $testEval . ';');

                if( $test )
                {
                    /* Join les lignes si la condition est bonne */
                    $result[] = array_merge($row, $rowJoin);
                    $addRow   = true;
                }
            }

            /**
             * Si aucun résultat n'est trouvé alors la ligne est remplie
             * avec les colonnes de la table jointe avec des valeurs null 
             */
            if( !$addRow )
            {
                $result[] = array_merge($rowTableNull, $rowJoin);
            }

            $addRow = false;
        }
        $this->tableData = $result;

        return $this;
    }

    /**
     * Exécute le calcule d'une jointure gauche entre 2 ensembles.
     * 
     * @param string $table nom de la table à joindre
     * @param Where $where condition de la jointure
     * 
     * @return $this
     */
    protected function executeLeftJoin( $table, Where $where )
    {
        $result    = [];
        $tableJoin = $this->getTableData($table);
        $testEval  = $where->executeJoin();

        /* Si les lignes se sont jointes */
        $addRow       = false;
        /* Le schéma de la table à joindre */
        $sch          = $this->schema->getSchemaTable($table);
        /* Prend les noms des champs de la table à joindre */
        $rowTableKey  = array_keys($sch[ 'fields' ]);
        /* Utilise les noms pour créer un tableau avec des valeurs null */
        $rowTableNull = array_fill_keys($rowTableKey, null);

        foreach( $this->tableData as $row )
        {
            /* Join les tables */
            foreach( $tableJoin as $rowJoin )
            {
                /* Vérifie les conditions */
                $test = eval('return ' . $testEval . ';');
                if( $test )
                {
                    /* Join les lignes si la condition est bonne */
                    $result[] = array_merge($rowJoin, $row);
                    $addRow   = true;
                }
            }

            /**
             * Si aucun resultat n'est trouvé alors la ligne est remplie
             * avec les colonnes de la table jointe avec des valeurs null 
             */
            if( !$addRow )
            {
                $result[] = array_merge($rowTableNull, $row);
            }

            $addRow = false;
        }
        $this->tableData = $result;

        return $this;
    }

    /**
     * Trie le tableau en fonction des clés paramétrés.
     * 
     * @param array $data les données à trier
     * @param array $keys les clés sur lesquelles le trie s'exécute
     * 
     * @return array les données triées
     */
    protected function executeOrderBy( array $data, array $keys )
    {
        $keyLength = count($keys);

        if( empty($keys) )
        {
            return $data;
        }

        foreach( $keys as $k => $value )
        {
            $keys[ $k ] = $keys[ $k ] == 'desc' || $keys[ $k ] == -1
                ? -1
                : ($keys[ $k ] == 'skip' || $keys[ $k ] === 0
                ? 0
                : 1);
        }

        usort($data, function($a, $b) use ($keys, $keyLength)
        {
            $sorted = 0;
            $ix     = 0;

            while( $sorted === 0 && $ix < $keyLength )
            {
                $k = $this->obIx($keys, $ix);
                if( $k )
                {
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
        /* Si l'une des colonnes est de type incrémental */
        $increments = $this->getIncrement();

        /* Je charge les colonnes de mon schéma */
        $schemaColumns = $this->tableSchema[ 'fields' ];

        foreach( $this->request[ 'values' ] as $column )
        {
            /* Pour chaque ligne je vérifie si le nombre de colonne correspond au nombre valeur insérée */
            if( count($this->request[ 'columns' ]) != count($column) )
            {
                throw new ColumnsNotFoundException('keys : ' . implode(',', $this->request[ 'columns' ]) . ' != ' . implode(',', $column));
            }

            /* Je prépare l'association clé=>valeur pour chaque ligne à insérer */
            $row = array_combine($this->request[ 'columns' ], $column);

            foreach( $schemaColumns as $field => $arg )
            {
                /* Si mon champs existe dans le schema */
                if( isset($row[ $field ]) )
                {
                    $data[ $field ] = $this->getValue($field, $arg[ 'type' ], $row[ $field ], $arg);
                    /* Si le champ est de type incrémental et que sa valeur est supérieure à celui enregistrer dans le schéma */
                    if( $arg[ 'type' ] === 'increments' && ($data[ $field ] > $increments[ $field ] ) )
                    {
                        $increments[ $field ] = $data[ $field ];
                    }
                    continue;
                }

                /* Si mon champ n'existe pas qu'il est de type incrémental */
                if( !isset($row[ $field ]) && $arg[ 'type' ] === 'increments' )
                {
                    $increments[ $field ] ++;
                    $data[ $field ] = $increments[ $field ];
                    continue;
                }

                /* Sinon on vérifie si une valeur par défaut lui est attribué */
                $data[ $field ] = $this->getValueDefault($field, $arg);
            }

            $this->tableData[] = $data;
        }
        /* Met à jour les valeurs incrémentales dans le schéma de la table */
        $this->schema->setIncrements($this->table, $increments);
    }

    /**
     * Exécute le calcul de mise à jour des données.
     */
    protected function executeUpdate()
    {
        if( isset($this->where) )
        {
            $testEval = $this->where->execute();
        }

        /* La variable $row est utilisé dans le test d'évaluation */
        foreach( $this->tableData as $key => $row )
        {
            if( isset($testEval) )
            {
                $test = eval('return ' . $testEval . ';');
                if( $test )
                {
                    $this->tableData[ $key ] = array_merge($this->tableData[ $key ], $this->request[ 'setUpdate' ]);
                }
            }
            else
            {
                $this->tableData[ $key ] = array_merge($this->tableData[ $key ], $this->request[ 'setUpdate' ]);
            }
        }
    }

    /**
     * Supprime des lignes de la table en fonction des conditions et sauvegarde la table.
     */
    protected function executeDelete()
    {
        if( isset($this->where) )
        {
            $testEval = $this->where->execute();
        }
        foreach( $this->tableData as $key => $row )
        {
            if( isset($testEval) )
            {
                $test = eval('return ' . $testEval . ';');
                if( $test )
                {
                    unset($this->tableData[ $key ]);
                }
            }
            else
            {
                unset($this->tableData[ $key ]);
            }
        }
        $this->tableData = array_values($this->tableData);
    }

    /**
     * Retourne la valeur s'il correspond au type déclaré.
     * Sinon déclenche une exception.
     * 
     * @param string $field la clé du champ
     * @param string $type le type de donnée (string|text|integer|float|boolean|char|date|datetime)
     * @param mixed $value la valeur à tester
     * @param array $arg les arguments de tests optionnels (length)
     * 
     * @return mixed
     * 
     * @throws ColumnsValueException
     */
    protected function getValue( $field, $type, $value, array $arg = [] )
    {
        $error = htmlspecialchars('The default value (' . $value . ') for column ' . $field . ' does not correspond to type ' . $type . '.');

        if( $type === 'string' && !is_string($value) )
        {
            throw new ColumnsValueException($error);
        }
        else if( $type === 'string' && is_string($value) )
        {
            if( strlen($value) > $arg[ 'length' ] )
            {
                throw new ColumnsValueException("The default value is larger than the specified size.");
            }
            return $value;
        }
        else if( $type === 'text' && !is_string($value) )
        {
            throw new ColumnsValueException($error);
        }
        else if( ($type === 'integer' || $type === 'increments' ) )
        {
            if( !is_numeric($value) && !is_int($value) )
            {
                throw new ColumnsValueException($error);
            }
            return ( int ) $value;
        }
        else if( ($type === 'float' ) )
        {
            if( !is_numeric($value) && !is_float($value) )
            {
                throw new ColumnsValueException($error);
            }
            return ( float ) $value;
        }
        else if( ($type === 'boolean') && !is_bool($value) )
        {
            throw new ColumnsValueException($error);
        }
        else if( ($type === 'char') && !is_string($value) )
        {
            throw new ColumnsValueException($error);
        }
        else if( ($type === 'char') && is_string($value) )
        {
            if( strlen($value) > $arg[ 'length' ] )
            {
                throw new ColumnsValueException($error);
            }
            return $value;
        }
        else if( $type === 'date' )
        {
            if( ($timestamp = strtotime($value)) === false )
            {
                throw new ColumnsValueException($error);
            }
            return date('Y-m-d', $timestamp);
        }
        else if( $type === 'datetime' )
        {
            $date   = new \DateTime($value);
            if( ($format = date_format($date, 'Y-m-d H:i:s')) === false )
            {
                throw new ColumnsValueException($error);
            }
            return $format;
        }

        return $value;
    }

    /**
     * Retourne la valeur par defaut du champ passé en paramêtre.
     * 
     * @param string $field le nom du champ
     * @param array $arg les différente configurations
     * 
     * @return mixed la valeur par defaut
     * 
     * @throws ColumnsValueException
     */
    protected function getValueDefault( $field, $arg )
    {
        if( !isset($arg[ 'nullable' ]) && !isset($arg[ 'default' ]) )
        {
            throw new ColumnsValueException(htmlspecialchars($field) . " not nullable or not default.");
        }
        else if( isset($arg[ 'default' ]) )
        {
            if( $arg[ 'type' ] === 'date' && $arg[ 'default' ] === 'current_date' )
            {
                return date('Y-m-d', time());
            }
            else if( $arg[ 'type' ] === 'datetime' && $arg[ 'default' ] === 'current_datetime' )
            {
                return date('Y-m-d H:i:s', time());
            }

            /* Si les variables magiques ne sont pas utilisé alors la vrais valeur par defaut est retourné */
            return $arg[ 'default' ];
        }
        // Si il n'y a pas default il est donc nullable
        return null;
    }

    /**
     * Retourne les champs incrémental de la courrante.
     * 
     * @return array le tableau des champ incrémentales et leur valeur
     */
    protected function getIncrement()
    {
        return $this->tableSchema[ 'increments' ];
    }

    /**
     * Fonction d'appui à orderByExecute().
     * 
     * @param array $obj
     * @param type $ix
     * 
     * @return boolean|integer
     */
    private function obIx( array $obj, $ix )
    {
        $size = 0;
        foreach( $obj as $key => $value )
        {
            if( $size == $ix )
            {
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
     * @param type $d
     * 
     * @return int
     */
    private function keySort( $a, $b, $d = null )
    {
        $d = $d !== null
            ? $d
            : 1;
        if( $a == $b )
        {
            return 0;
        }
        return ($a > $b)
            ? 1 * $d
            : -1 * $d;
    }

    /**
     * Revoie les instances uniques d'un tableau multidimensionnel.
     * 
     * @param array $input table multidimensionnelle
     * 
     * @return array tableau multidimensionnel avec des entrées uniques
     */
    private function arrayUniqueMultidimensional( array $input )
    {
        /* Sérialise les données du tableaux */
        $serialized = array_map('serialize', $input);

        /* Supprime les doublons sérialisés */
        $unique = array_unique($serialized);

        /* Redonne les clés au tableau */
        $output = array_intersect_key($input, $unique);

        /* Renvoie le tableau avec ses clés ré-indexé */
        return array_values($output);
    }

    /**
     * Charge les colonnes de la table courante et des tables de jointure.
     */
    private function loadAllColumnsSchema()
    {
        $schema = $this->tableSchema[ 'fields' ];

        if( isset($this->request[ 'leftJoin' ]) )
        {
            foreach( $this->request[ 'leftJoin' ] as $value )
            {
                $schemaTable = $this->schema->getSchemaTable($value[ 'table' ]);
                $schema      = array_merge($schema, $schemaTable[ 'fields' ]);
            }
        }

        if( isset($this->request[ 'rightJoin' ]) )
        {
            foreach( $this->request[ 'rightJoin' ] as $value )
            {
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
        if( $this->table === '' )
        {
            throw new TableNotFoundException("La table est absente : " . $this);
        }
    }

    /**
     * Vérifie pour tous les champs sélectionnées, leur l'existence à partir du schéma.
     */
    private function fetchPrepareSelect()
    {
        if( isset($this->request[ 'columns' ]) )
        {
            $this->diffColumns($this->request[ 'columns' ]);
        }
        else
        {
            /* Si aucunes colonnes selectionnées */
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
        if( isset($this->request[ 'leftJoin' ]) )
        {
            /* Merge toutes les colonnes des conditions de chaque jointure */
            foreach( $this->request[ 'leftJoin' ] as $value )
            {
                $columns = array_merge($columns, $value[ 'where' ]->getColumns());
            }
        }

        if( isset($this->request[ 'rightJoin' ]) )
        {
            /* Merge toutes les colonnes des conditions de chaque jointure */
            foreach( $this->request[ 'rightJoin' ] as $value )
            {
                $columns = array_merge($columns, $value[ 'where' ]->getColumns());
            }
        }
        /* Merge les colonnes des conditions de la requête courante */
        if( isset($this->where) )
        {
            $columns = array_merge($columns, $this->where->getColumns());
        }

        if( !empty($columns) )
        {
            $this->diffColumns($columns);
        }
    }

    /**
     * Vérifie pour tous les ORDER BY l'existence des champs à partir du schéma.
     */
    private function fetchPrepareOrderBy()
    {
        /* Si aucunes colonnes sélectionnées alors */
        if( isset($this->orderBy) )
        {
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
        if( !isset($this->request[ 'union' ]) )
        {
            return;
        }

        foreach( $this->request[ 'union' ] as $request )
        {
            if( count($this->getSelect()) != count($request[ 'request' ]->getSelect()) )
            {
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
     * @param array $columns liste des champs
     * 
     * @throws ColumnsNotFoundException
     */
    private function diffColumns( array $columns )
    {
        $all = $this->request[ 'allColumnsSchema' ];

        $diff = array_diff_key(array_flip($columns), $all);

        if( !empty($diff) )
        {
            $columnsDiff = array_flip($diff);
            throw new ColumnsNotFoundException("Column " . implode(',', $columnsDiff) . " is absent : " . $this);
        }
    }
}