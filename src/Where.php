<?php

/**
 * Queryflatfile
 *
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\Exception\Query\OperatorNotFound;

/**
 * Pattern fluent pour la création des clauses (conditions) de manipulation des données.
 *
 * @author Mathieu NOËL
 */
class Where
{
    /**
     * Les conditions à exécuter.
     *
     * @var array
     */
    protected $where = [];

    /**
     * Les conditions binaires autorisées.
     *
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
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Ajoute une condition simple pour la requête.
     * Si la valeur du champ est égal (non égale, supérieur à, ...)  par rapport à une valeur.
     *
     * @param callable|string $column   Sous condition ou une colonne.
     * @param null|string     $operator Type de condition.
     * @param null|string     $value    Valeur de teste.
     * @param string          $bool     Porte logique de la condition (and|or).
     * @param bool            $not      Inverse la condition.
     *
     * @throws OperatorNotFound
     * @return $this
     */
    public function where(
    $column,
        $operator = null,
        $value = null,
        $bool = 'and',
        $not = false
    ) {
        if ($column instanceof \Closure) {
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
        if ($value === null) {
            $value    = $operator;
            $operator = '=';
        }

        /* Pour que l'opérateur soit insensible à la case. */
        $condition = strtolower($operator);

        /* Si l'opérateur n'est pas autorisé. */
        if (!in_array($condition, $this->contidion)) {
            throw new OperatorNotFound("The condition $condition is not exist.");
        }
        if ($condition === '=') {
            $condition = '===';
        } elseif (in_array($condition, [ 'like', 'ilike', 'not like', 'not ilike' ])) {
            /* Protection des caractères spéciaux des expressions rationnelles autre que celle imposée. */
            $value   = preg_quote($value);
            /* Le paterne commun au 4 conditions. */
            $pattern = '/' . strtr($value, '%', '.*');

            /* Pour rendre la regex inssencible à la case. */
            $pattern .= $condition === 'like' || $condition === 'not like'
                ? '/'
                : '/i';

            /* Pour inverser le comportement du like. */
            $not = $condition === 'not like' || $condition === 'not ilike';

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
     * @param null|string     $operator
     * @param null|string     $value
     *
     * @return $this
     */
    public function notWhere($column, $operator = null, $value = null)
    {
        $this->where($column, $operator, $value, 'and', true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction where().
     *
     * @param callable|string $column
     * @param null|string     $operator
     * @param null|string
     *
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
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
    public function orNotWhere($column, $operator = null, $value = null)
    {
        $this->where($column, $operator, $value, 'or', true);

        return $this;
    }

    /**
     * Ajoute une condition between à la requête.
     * Si la valeur du champ est compris entre 2 valeurs.
     *
     * @param string $column Nom de la colonne.
     * @param mixed  $min    Valeur minimum ou égale.
     * @param mixed  $max    Valeur maximum ou égale.
     * @param string $bool   Porte logique de la condition (and|or).
     * @param bool   $not    Inverse la condition.
     *
     * @return $this
     */
    public function between($column, $min, $max, $bool = 'and', $not = false)
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
     * Alias inverse de la fonction between().
     *
     * @param callable|string $column
     * @param mixed           $min
     * @param mixed           $max
     *
     * @return $this
     */
    public function notBetween($column, $min, $max)
    {
        $this->between($column, $min, $max, 'and', true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction between().
     *
     * @param callable|string $column
     * @param mixed           $min
     * @param mixed           $max
     *
     * @return $this
     */
    public function orBetween($column, $min, $max)
    {
        $this->between($column, $min, $max, 'or');

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction between().
     *
     * @param callable|string $column
     * @param mixed           $min
     * @param mixed           $max
     *
     * @return $this
     */
    public function orNotBetween($column, $min, $max)
    {
        $this->between($column, $min, $max, 'or', true);

        return $this;
    }

    /**
     * Ajoute une condition in à la requête.
     * Si la valeur du champs est contenu dans une liste.
     *
     * @param string $column Nom de la colonne.
     * @param array  $value  Valeurs à tester.
     * @param string $bool   Porte logique de la condition (and|or).
     * @param bool   $not    Inverse la condition.
     *
     * @return $this
     */
    public function in($column, array $value, $bool = 'and', $not = false)
    {
        $this->where[]   = [
            'type'      => __FUNCTION__,
            'condition' => 'in',
            'column'    => $column,
            'value'     => $value,
            'bool'      => $bool,
            'not'       => $not
        ];
        $this->columns[] = $column;

        return $this;
    }

    /**
     * Alias inverse de la fonction in().
     *
     * @param string $column
     * @param array  $value
     *
     * @return $this
     */
    public function notIn($column, array $value)
    {
        $this->in($column, $value, 'and', true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction in().
     *
     * @param string $column
     * @param array  $value
     *
     * @return $this
     */
    public function orIn($column, array $value)
    {
        $this->in($column, $value, 'or');

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction in().
     *
     * @param string $column
     * @param array  $value
     *
     * @return $this
     */
    public function orNotIn($column, array $value)
    {
        $this->in($column, $value, 'or', true);

        return $this;
    }

    /**
     * Ajoute une condition isNull à la requête.
     * Si la valeur du champ est strictement égale à null.
     *
     * @param string $column Nom de la colonne.
     * @param string $bool   Porte logique de la condition (and|or).
     * @param bool   $not    Inverse la condition.
     *
     * @return $this
     */
    public function isNull(
    $column,
        $bool = 'and',
        $not = false
    ) {
        $this->where[]   = [
            'type'      => __FUNCTION__,
            'condition' => '===',
            'column'    => $column,
            'value'     => null,
            'bool'      => $bool,
            'not'       => $not
        ];
        $this->columns[] = $column;

        return $this;
    }

    /**
     * Alias inverse de la fonction isNull().
     *
     * @param string $column
     *
     * @return $this
     */
    public function isNotNull($column)
    {
        $this->isNull($column, 'and', true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction isNull().
     *
     * @param string $column
     *
     * @return $this
     */
    public function orIsNull($column)
    {
        $this->isNull($column, 'or');

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction isNull()
     *
     * @param string $column
     *
     * @return $this
     */
    public function orIsNotNull($column)
    {
        $this->isNull($column, 'or', true);

        return $this;
    }

    /**
     * Ajoute une condition avec une expression régulière à la requête.
     *
     * @param string $column  Nom de la colonne.
     * @param string $pattern Expression régulière.
     * @param string $bool    Porte logique de la condition (and|or).
     * @param bool   $not     Inverse la condition.
     *
     * @return $this
     */
    public function regex($column, $pattern, $bool = 'and', $not = false)
    {
        $this->where[]   = [
            'type'   => __FUNCTION__,
            'column' => $column,
            'value'  => $pattern,
            'bool'   => $bool,
            'not'    => $not
        ];
        $this->columns[] = $column;

        return $this;
    }

    /**
     *  Alias inverse de la fonction regex().
     *
     * @param string $column
     * @param string $pattern
     *
     * @return $this
     */
    public function notRegex($column, $pattern)
    {
        $this->regex($column, $pattern, 'and', true);

        return $this;
    }

    /**
     *  Alias avec la porte logique 'OR' de la fonction regex().
     *
     * @param string $column
     * @param string $pattern
     *
     * @return $this
     */
    public function orRegex($column, $pattern)
    {
        $this->regex($column, $pattern, 'or');

        return $this;
    }

    /**
     *  Alias inverse avec la porte logique 'OR' de la fonction regex()
     *
     * @param string $column
     * @param string $pattern
     *
     * @return $this
     */
    public function orNotRegex($column, $pattern)
    {
        $this->regex($column, $pattern, 'or', true);

        return $this;
    }

    /**
     * Retourne toutes les colonnes utilisées pour créer la clause.
     *
     * @return array Colonnes.
     */
    public function getColumns()
    {
        $output = [];
        foreach ($this->where as $value) {
            if (!\is_array($value[ 'column' ])) {
                $output[] = $this->getColumn($value[ 'column' ]);

                continue;
            }

            $output = array_merge($output, $value[ 'column' ]);
        }

        return $this->columns;
    }

    /**
     * Retourne TRUE si la suite de condition enregistrée valide les champs du tableau.
     *
     * @param array $row Tableau associatif de champ.
     *
     * @return bool
     */
    public function execute(array $row)
    {
        foreach ($this->where as $key => $value) {
            $columns = $value[ 'column' ];
            switch ($value[ 'type' ]) {
                case 'where':
                case 'isNull':
                case 'in':
                    $predicate = self::predicate($row[ $columns ], $value[ 'condition' ], $value[ 'value' ]);

                    break;
                case 'whereCallback':
                    $predicate = $value[ 'value' ]->execute($row);

                    break;
                case 'between':
                    $predicate = self::predicate($row[ $columns ], '>=', $value[ 'min' ]) && self::predicate($row[ $columns ], '<=', $value[ 'max' ]);

                    break;
                case 'regex':
                    $predicate = !empty($value[ 'not' ])
                        ? !preg_match($value[ 'value' ], $row[ $columns ])
                        : preg_match($value[ 'value' ], $row[ $columns ]);
                    $predicate &= self::predicate($row[ $columns ], '!==', null);

                    break;
            }

            if ($value[ 'type' ] !== 'regex') {
                $predicate = !empty($value[ 'not' ])
                    ? !$predicate
                    : $predicate;
            }

            if ($key == 0) {
                $output = $predicate;
            } elseif ($value[ 'bool' ] === 'and') {
                $output &= $predicate;
            } else {
                $output |= $predicate;
            }
        }

        return $output;
    }

    /**
     * Retourne TRUE si la suite de condition enregistrée valide les champs du tableau
     * par rapport à un autre tableau.
     *
     * @param array $row      Tableau associatif de champ.
     * @param array $rowTable Tableau associatif de champ à tester.
     *
     * @return bool
     */
    public function executeJoin(array $row, array $rowTable)
    {
        foreach ($this->where as $key => $value) {
            $columns = $value[ 'column' ];
            switch ($value[ 'type' ]) {
                case 'where':
                case 'isNull':
                    $val       = $this->isColumn($value[ 'value' ])
                        ? $rowTable[ substr(strrchr($value[ 'value' ], '.'), 1) ]
                        : $value[ 'value' ];
                    $predicate = self::predicate($row[ $columns ], $value[ 'condition' ], $val);

                    break;
                case 'whereCallback':
                    $predicate = $value[ 'value' ]->execute($row);

                    break;
            }

            if ($key == 0) {
                $output = $predicate;
            } elseif ($value[ 'bool' ] === 'and') {
                $output &= $predicate;
            } else {
                $output |= $predicate;
            }
        }

        return $output;
    }

    /**
     * Retourne TRUE si la condition est validée.
     *
     * @param mixed  $columns  Valeur à tester.
     * @param string $operator Condition à réaliser.
     * @param mixed  $value    Valeur de comparaison.
     *
     * @throws \Exception
     * @return bool
     */
    public static function predicate($columns, $operator, $value)
    {
        switch ($operator) {
            case '==':
                return $columns == $value;
            case '=':
            case '===':
                return $columns === $value;
            case '!==':
                return $columns !== $value;
            case '!=':
                return $columns != $value;
            case '<>':
                return $columns <> $value;
            case '<':
                return $columns < $value;
            case '<=':
                return $columns <= $value;
            case '>':
                return $columns > $value;
            case '>=':
                return $columns >= $value;
            case 'in':
                return in_array($columns, $value);
        }

        throw new \Exception("The $operator operator is not supported.");
    }

    /**
     * Si la valeur représente une colonne ou une valeur,
     * where('id', '=', 'test') ici 'test' est une valeur de type chaine de caractère,
     * where('id', '=', 'table.test') ici 'test' est une colonne puisqu'il est précédé du nom de sa table.
     *
     * @param string $value
     *
     * @return bool
     */
    protected function isColumn($value)
    {
        return \is_string($value) && strstr($value, '.');
    }

    /**
     * Retourne le nom de la colonne ou la valeur.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function getColumn($value)
    {
        return $this->isColumn($value)
            ? substr(strrchr($value, '.'), 1)
            : $value;
    }
}
