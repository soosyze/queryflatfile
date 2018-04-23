<?php

/**
 * Class Where | src/Where.php
 * 
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * 
 */

namespace Queryflatfile;

use Queryflatfile\Exception\Query\ColumnsNotFoundException;

/**
 * Pattern fluent pour la création des clauses (conditions) de manipulation des données.
 */
class Where
{
    /**
     * Les conditions à exécuter.
     * @var array 
     */
    protected $where = [];

    /**
     * Les conditions binaires autorisées.
     * @var array 
     */
    protected $contidion = [
        '=',
        '==',
        '===',
        '!==',
        '!=',
        '<>',
        '<',
        '<=',
        '>',
        '>=',
        'like',
        'not like',
        'ilike',
        'not ilike'
    ];

    /**
     * Les colonnes appelées pour les clauses.
     * @var array 
     */
    protected $columns = [];

    /**
     * Ajoute une condition simple pour la requête.
     * 
     * Si la valeur du champ est égal (non égale, supérieur à, ...)  par rapport à une valeur.
     * 
     * @param callable|string $column une sous condition ou une colonne
     * @param null|string $operator le type de condition
     * @param null|string $value la valeur de teste
     * @param string $bool la porte logique de la condition (and|or)
     * @param boolean $not inverse la condition
     * 
     * @return $this
     */
    public function where( $column, $operator = null, $value = null,
        $bool = 'and', $not = false )
    {
        if( is_callable($column) )
        {
            $where         = new Where();
            call_user_func_array($column, [ &$where ]);
            $this->where[] = [
                'type'   => __FUNCTION__ . 'Callback',
                'column' => $where->getColumns(),
                'value'  => $where,
                'bool'   => $bool,
                'not'    => $not
            ];
            $this->columns = array_merge($this->columns, $where->getColumns());
            return $this;
        }
        else if( $value === null )
        {
            $value    = $operator;
            $operator = '=';
        }

        /* Pour que l'opérateur soit insensible à la case */
        $condition = strtolower($operator);

        /* Si l'opérateur n'est pas autorisé */
        if( !in_array($condition, $this->contidion) )
        {
            throw new Exception\Query\OperatorNotFound('The condition ' . htmlspecialchars($condition) . ' is not exist.');
        }
        else if( $condition === '=' )
        {
            $condition = '===';
        }
        else if( in_array($condition, [ 'like', 'ilike', 'not like', 'not ilike' ]) )
        {
            /* Le paterne commun au 4 conditions */
            $pattern = '/' . str_replace('%', '.*', $value);

            /* Pour rendre lea regex inssencible à la case */
            $pattern .= $condition === 'like' || $condition === 'not like'
                ? '/'
                : '/i';

            /* Pour inverser le comportement du like */
            $not = $condition === 'like' || $condition === 'ilike'
                ? false
                : true;

            return $this->regex($column, $pattern, $bool, $not);
        }

        $this->where[]   = [
            'type'      => __FUNCTION__,
            'column'    => $column,
            'condition' => $condition,
            'value'     => $value,
            'bool'      => $bool,
            'not'       => $not
        ];
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Alias inverse de la fonction where().
     * 
     * @param callable|string $column
     * @param null|string $operator
     * @param null|string $value
     * 
     * @return $this
     */
    public function notWhere( $column, $operator = null, $value = null )
    {
        $this->where($column, $operator, $value, 'and', true);
        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction where().
     * 
     * @param callable|string $column
     * @param null|string $operator
     * @param null|string
     * 
     * @return $this
     */
    public function orWhere( $column, $operator = null, $value = null )
    {
        $this->where($column, $operator, $value, 'or');
        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction where().
     * 
     * @param callable|string
     * @param null|string
     * @param null|string
     * 
     * @return $this
     */
    public function orNotWhere( $column, $operator = null, $value = null )
    {
        $this->where($column, $operator, $value, 'or', true);
        return $this;
    }

    /**
     * Ajoute une condition between à la requête.
     * 
     * Si la valeur du champ est compris entre 2 valeurs.
     * 
     * @param string $column nom de la colonne
     * @param mixed $min la valeur minimum ou égale
     * @param mixed $max la valeur maximum ou égale
     * @param string $bool la porte logique de la condition (and|or)
     * @param boolean $not inverse la condition
     * 
     * @return $this
     */
    public function between( $column, $min, $max, $bool = 'and', $not = false )
    {
        $this->where[]   = [
            'type'   => __FUNCTION__,
            'column' => $column,
            'min'    => $min,
            'max'    => $max,
            'bool'   => $bool,
            'not'    => $not
        ];
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Alias inverse de la fonction between()
     * 
     * @param callable|string $column
     * @param mixed $min
     * @param mixed $max
     * 
     * @return $this
     */
    public function notBetween( $column, $min, $max )
    {
        $this->between($column, $min, $max, 'and', true);
        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction between()
     * 
     * @param callable|string $column
     * @param mixed $min
     * @param mixed $max
     * 
     * @return $this
     */
    public function orBetween( $column, $min, $max )
    {
        $this->between($column, $min, $max, 'or');
        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction between()
     * 
     * @param callable|string $column
     * @param mixed $min
     * @param mixed $max
     * 
     * @return $this
     */
    public function orNotBetween( $column, $min, $max )
    {
        $this->between($column, $min, $max, 'or', true);
        return $this;
    }

    /**
     * Ajoute une condition in à la requête
     * 
     * Si la valeur du champs est contenu dans une liste.
     *  
     * @param string $column nom de la colonne
     * @param array $values les valeurs a tester
     * @param string $bool la porte logique de la condition (and|or)
     * @param boolean $not inverse la condition
     * 
     * @return $this
     */
    public function in( $column, array $values, $bool = 'and', $not = false )
    {
        $this->where[]   = [
            'type'   => __FUNCTION__,
            'column' => $column,
            'values' => $values,
            'bool'   => $bool,
            'not'    => $not
        ];
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Alias inverse de la fonction in()
     * 
     * @param string $column
     * @param array $values
     * 
     * @return $this
     */
    public function notIn( $column, array $values )
    {
        $this->in($column, $values, 'and', true);
        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction in()
     * 
     * @param string $column
     * @param array $values
     * 
     * @return $this
     */
    public function orIn( $column, array $values )
    {
        $this->in($column, $values, 'or');
        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction in()(
     * 
     * @param string $column
     * @param array $values
     * 
     * @return $this
     */
    public function orNotIn( $column, array $values )
    {
        $this->in($column, $values, 'or', true);
        return $this;
    }

    /**
     * Ajoute une condition isNull à la requête.
     * 
     * Si la valeur du champ est strictement égale à null.
     * 
     * @param string $column nom de la colonne
     * @param string $condition la condition de validation (===|!==)
     * @param string $bool la porte logique de la condition (and|or)
     * @param boolean $not inverse la condition
     * 
     * @return $this
     */
    public function isNull( $column, $condition = '===', $bool = 'and',
        $not = false )
    {
        $this->where[]   = [
            'type'      => __FUNCTION__,
            'condition' => $condition,
            'column'    => addslashes($column),
            'bool'      => $bool,
            'not'       => $not
        ];
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Alias inverse de la fonction isNull
     * 
     * @param type $column
     * 
     * @return $this
     */
    public function isNotNull( $column )
    {
        $this->isNull($column, '===', 'and', true);
        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction isNull()
     * 
     * @param type $column
     * 
     * @return $this
     */
    public function orIsNull( $column )
    {
        $this->isNull($column, '===', 'or');
        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction isNull()
     * 
     * @param type $column
     * 
     * @return $this
     */
    public function orIsNotNull( $column )
    {
        $this->isNull($column, '===', 'or', true);
        return $this;
    }

    /**
     * Ajoute une condition avec une expression régulière à la requête.
     * 
     * @param string $column nom de la colonne
     * @param string $pattern l'expression régulière
     * @param string $bool la porte logique de la condition (and|or)
     * @param boolean $not inverse la condition
     * 
     * @return $this
     */
    public function regex( $column, $pattern, $bool = 'and', $not = false )
    {
        $this->where[]   = [
            'type'   => __FUNCTION__,
            'column' => $column,
            'values' => $pattern,
            'bool'   => $bool,
            'not'    => $not
        ];
        $this->columns[] = $column;
        return $this;
    }

    /**
     *  Alias inverse de la fonction regex()
     * 
     * @param string $column nom de la colonne
     * @param string $pattern l'expression régulière
     * 
     * @return $this
     */
    public function notRegex( $column, $pattern )
    {
        $this->regex($column, $pattern, 'and', true);
        return $this;
    }

    /**
     *  Alias avec la porte logique 'OR' de la fonction regex()
     * 
     * @param string $column nom de la colonne
     * @param string $pattern l'expression régulière
     * 
     * @return $this
     */
    public function orRegex( $column, $pattern )
    {
        $this->regex($column, $pattern, 'or');
        return $this;
    }

    /**
     *  Alias inverse avec la porte logique 'OR' de la fonction regex()
     * 
     * @param string $column nom de la colonne
     * @param string $pattern l'expression régulière
     * 
     * @return $this
     */
    public function orNotRegex( $column, $pattern )
    {
        $this->regex($column, $pattern, 'or', true);
        return $this;
    }

    /**
     * Construit la chaine conditionnée pour les requêtes.
     * 
     * Cette chaine de caractère représente les conditions au format PHP et 
     * sera utilisé dans une fonction d'évaluation.
     * 
     * @return string la chaine
     */
    public function execute()
    {
        if( empty($this->where) )
        {
            return '1';
        }

        $evalWhere = '';

        foreach( $this->where as $key => $values )
        {
            /* OPERATEUR BINAIRE */
            $evalWhere .= $values[ 'bool' ] === 'and'
                ? ' && '
                : ' || ';

            /* OPERATEUR INVERSE */
            $evalWhere .= !empty($values[ 'not' ])
                ? '!'
                : '';

            $columns = $values[ 'column' ];

            /* CONDITIONS */
            switch( $values[ 'type' ] )
            {
                case 'where':
                    $evalWhere .= '(' . $this->getRow($columns) . ' ' . $values[ 'condition' ] . ' ' . $this->getValue($values[ 'value' ]) . ')';
                    break;
                case 'whereCallback':
                    $evalWhere .= '(' . $values[ 'value' ]->execute() . ')';
                    break;
                case 'between':
                    $evalWhere .= '(' . $this->getRow($columns) . ' >= ' . $this->getValue($values[ 'min' ])
                        . ' && ' . $this->getRow($columns) . ' <= ' . $this->getValue($values[ 'max' ]) . ')';
                    break;
                case 'in':
                    $countIn   = count($values[ 'values' ]);
                    foreach( $values[ 'values' ] as $key => $value )
                    {
                        $evalWhere .= '(' . $this->getRow($columns)
                            . ' == ' . $this->getValue($value) . ')';
                        if( $countIn != $key + 1 )
                        {
                            $evalWhere .= '||';
                        }
                    }
                    break;
                case 'isNull':
                    $evalWhere .= '(' . $this->getRow($columns) . ' ' . $values[ 'condition' ] . ' null )';
                    break;
                case 'regex':
                    $evalWhere .= '(preg_match("' . $values[ 'values' ] . '",' . $this->getRow($columns) . '))';
                    break;
            }
        }

        return substr($evalWhere, 4);
    }

    /**
     * Construit une chaine conditionné pour les jointures.
     * 
     * Cette chaine de caractère représente les conditions au format PHP et 
     * sera utilisé dans une fonction d'évaluation.
     * 
     * @return string la chaine
     */
    public function executeJoin()
    {
        if( empty($this->where) )
        {
            return '1';
        }

        $evalWhere = '';

        foreach( $this->where as $values )
        {
            /* OPERATEUR BINAIRE */
            $evalWhere .= $values[ 'bool' ] === 'and'
                ? ' && '
                : ' || ';

            /* OPERATEUR INVERSE */
            $evalWhere .= !empty($values[ 'not' ])
                ? '!'
                : '';

            $columns = $values[ 'column' ];

            /* CONDITIONS */
            switch( $values[ 'type' ] )
            {
                case 'where':
                    $evalWhere .= '(' . $this->getRow($columns) . ' ' . $values[ 'condition' ] . ' '
                        . $this->evalWhere($values[ 'value' ]) . ')';
                    break;
                case 'whereCallback':
                    $evalWhere .= '(' . $values[ 'value' ]->execute() . ')';
                    break;
                case 'between':
                    $evalWhere .= '(' . $this->getRow($columns) . ' >= ' . $this->evalWhere($values[ 'min' ])
                        . ' && ' . $this->getRow($columns) . ' <= ' . $this->evalWhere($values[ 'max' ]) . ')';
                    break;
                case 'in':
                    $countIn   = count($values[ 'values' ]);
                    foreach( $values[ 'values' ] as $key => $value )
                    {
                        $evalWhere .= '(' . $this->getRow($columns)
                            . ' == ' . $this->evalWhere($value) . ')';
                        if( $countIn != $key + 1 )
                        {
                            $evalWhere .= ' || ';
                        }
                    }
                    break;
                case 'isNull':
                    $evalWhere .= '(' . $this->getRow($columns) . ' ' . $values[ 'condition' ] . ' null )';
                    break;
                case 'regex':
                    $evalWhere .= '( preg_match("' . $values[ 'values' ] . '",' . $this->getRow($columns) . ') )';
                    break;
            }
        }

        unset($this->where);
        return substr($evalWhere, 4);
    }

    /**
     * Retourne toutes les colonnes utilisées pour créer la clause.
     * 
     * @return array les colonnes
     */
    public function getColumns()
    {
        $output = [];
        foreach( $this->where as $value )
        {
            if( !is_array($value[ 'column' ]) )
            {
                $output[] = $this->getColumn($value[ 'column' ]);
                continue;
            }

            $output = array_merge($output, $value[ 'column' ]);
        }

        return $this->columns;
    }

    /**
     * Retourne une chaine de caractère au format PHP représentant la colonne pour la requête.
     * 
     * @param string $key le nom de la colonne
     * 
     * @return string
     */
    protected function getRow( $key )
    {
        /* Le nom de la colonne doit-être au format string */
        if( !is_string($key) )
        {
            throw new ColumnsNotFoundException('The column name must be in string format.');
        }
        return '$row[' . "'" . addslashes($key) . "'" . ']';
    }

    /**
     * Retourne une chaine de caractère au format PHP représentant la colonne pour les jointures.
     * 
     * @param string $key le nom de la colonne
     * 
     * @return string
     */
    protected function getRowJoin( $key )
    {
        /* Le nom de la colonne doit-être au format string */
        if( !is_string($key) )
        {
            throw new ColumnsNotFoundException('The column name must be in string format.');
        }
        return '$rowJoin[' . "'" . addslashes($key) . "'" . ']';
    }

    /**
     * Revoie la valeur si elle est strictement  numérique (int, float, double, long..)
     * Sinon ajoute des simples quottes et des slashs si la valeur est une chaine de caractère.
     * 
     * @param mixed $value la valeur d'une condition
     * 
     * @return mixed
     */
    protected function getValue( $value )
    {
        /* La valeur de test doit-être doit-être au format string|numérique|boolean */
        if( !is_string($value) && !is_numeric($value) && !is_bool($value) )
        {
            throw new ColumnsNotFoundException('The column name must be in string format.');
        }
        return is_numeric($value) && !is_string($value)
            ? $value
            : "'" . addslashes($value) . "'";
    }

    /**
     * Retourne le nom de la colonne ou la valeur.
     * 
     * @param mixed $value
     * 
     * @return string
     */
    protected function getColumn( $value )
    {
        return $this->isColumn($value)
            ? substr(strrchr($value, '.'), 1)
            : $value;
    }

    /**
     * Si la valeur représente une colonne ou une valeur.
     * 
     * where('id', '=', 'test') ici 'test' est une valeur de type chaine de caractère
     * where('id', '=', 'table.test') ici 'test' est une colonne puisqu'il est précédé du nom de sa table
     * 
     * @param string $value
     * 
     * @return boolean
     */
    protected function isColumn( $value )
    {
        return strstr($value, '.');
    }

    /**
     * Si la valeur est une colonne alors retourne une chaine représentant une condition,
     * sinon retour la valeur.
     * 
     * @param mixed $value
     * 
     * @return string
     */
    protected function evalWhere( $value )
    {
        return $this->isColumn($value)
            ? $this->getRowJoin($this->getColumn($value))
            : $this->getValue($value);
    }
}