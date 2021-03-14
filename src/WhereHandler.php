<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\Exception\Query\OperatorNotFound;

/**
 * Pattern fluent pour la création des clauses (conditions) de manipulation des données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class WhereHandler
{
    /**
     * Représente une expréssion AND.
     */
    const EXP_AND = 'and';

    /**
     * Représente une expréssion OR.
     */
    const EXP_OR = 'or';

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
     * Ajoute une condition simple pour la requête.
     * Si la valeur du champ est égal (non égale, supérieur à, ...)  par rapport à une valeur.
     *
     * @param callable|string $column   Sous condition ou une colonne.
     * @param null|string     $operator Type de condition.
     * @param null|string     $value    Valeur de teste.
     * @param string          $bool     Porte logique de la condition (and|or).
     * @param bool            $not      Inverse la condition.
     *
     * @throws OperatorNotFound The condition is not exist.
     *
     * @return $this
     */
    public function where(
        $column,
        $operator = null,
        $value = null,
        $bool = self::EXP_AND,
        $not = false
    ) {
        if ($column instanceof \Closure) {
            $this->whereCallback($column, $bool, $not);

            return $this;
        }
        if ($operator !== null && $value === null) {
            list($value, $operator) = [ $operator, '=' ];
        }

        /* Pour que l'opérateur soit insensible à la case. */
        $condition = strtolower($operator);

        /* Si l'opérateur n'est pas autorisé. */
        if (!in_array($condition, $this->contidion)) {
            throw new OperatorNotFound("The condition $condition is not exist.");
        }
        if (in_array($condition, [ 'like', 'ilike', 'not like', 'not ilike' ])) {
            $this->like($column, $condition, $value, $bool);

            return $this;
        }
        $this->where[]   = [
            'type'      => __FUNCTION__,
            'column'    => $column,
            'condition' => $condition,
            'value'     => $value,
            'bool'      => $bool,
            'not'       => $not
        ];

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
        $this->where($column, $operator, $value, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction where().
     *
     * @param callable|string $column
     * @param null|string     $operator
     * @param null|string     $value
     *
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        $this->where($column, $operator, $value, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction where().
     *
     * @param callable|string $column
     * @param null|string     $operator
     * @param null|string     $value
     *
     * @return $this
     */
    public function orNotWhere($column, $operator = null, $value = null)
    {
        $this->where($column, $operator, $value, self::EXP_OR, true);

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
    public function between($column, $min, $max, $bool = self::EXP_AND, $not = false)
    {
        $this->where[]   = [
            'type'      => __FUNCTION__,
            'column'    => $column,
            'condition' => __FUNCTION__,
            'value'     => [ 'min' => $min, 'max' => $max ],
            'bool'      => $bool,
            'not'       => $not
        ];

        return $this;
    }

    /**
     * Alias inverse de la fonction between().
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     *
     * @return $this
     */
    public function notBetween($column, $min, $max)
    {
        $this->between($column, $min, $max, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction between().
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     *
     * @return $this
     */
    public function orBetween($column, $min, $max)
    {
        $this->between($column, $min, $max, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction between().
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     *
     * @return $this
     */
    public function orNotBetween($column, $min, $max)
    {
        $this->between($column, $min, $max, self::EXP_OR, true);

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
    public function in($column, array $value, $bool = self::EXP_AND, $not = false)
    {
        $this->where[]   = [
            'type'      => __FUNCTION__,
            'column'    => $column,
            'condition' => 'in',
            'value'     => $value,
            'bool'      => $bool,
            'not'       => $not
        ];

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
        $this->in($column, $value, self::EXP_AND, true);

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
        $this->in($column, $value, self::EXP_OR);

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
        $this->in($column, $value, self::EXP_OR, true);

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
    public function isNull($column, $bool = self::EXP_AND, $not = false)
    {
        $this->where[]   = [
            'type'      => __FUNCTION__,
            'column'    => $column,
            'condition' => '===',
            'value'     => null,
            'bool'      => $bool,
            'not'       => $not
        ];

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
        $this->isNull($column, self::EXP_AND, true);

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
        $this->isNull($column, self::EXP_OR);

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
        $this->isNull($column, self::EXP_OR, true);

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
    public function regex($column, $pattern, $bool = self::EXP_AND, $not = false)
    {
        $this->where[]   = [
            'type'      => __FUNCTION__,
            'condition' => 'regex',
            'column'    => $column,
            'value'     => $pattern,
            'bool'      => $bool,
            'not'       => $not
        ];

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
        $this->regex($column, $pattern, self::EXP_AND, true);

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
        $this->regex($column, $pattern, self::EXP_OR);

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
        $this->regex($column, $pattern, self::EXP_OR, true);

        return $this;
    }

    /**
     * Ajoute une sous-condition pour la requête.
     *
     * @param \Closure $column
     * @param string   $bool
     * @param bool     $not
     *
     * @return void
     */
    protected function whereCallback(\Closure $column, $bool = self::EXP_AND, $not = false)
    {
        $where = new Where();
        call_user_func_array($column, [ &$where ]);

        $this->where[] = [
            'type'   => __FUNCTION__,
            'column' => $where->getColumns(),
            'value'  => $where,
            'bool'   => $bool,
            'not'    => $not
        ];
    }

    /**
     * Ajoute une condition like pour la requête.
     *
     * @param string $column
     * @param string $operator
     * @param string $value
     * @param string $bool
     *
     * @return void
     */
    protected function like($column, $operator, $value, $bool = self::EXP_AND)
    {
        /* Protection des caractères spéciaux des expressions rationnelles autre que celle imposée. */
        $str = preg_quote($value, '/');

        /* Le paterne commun au 4 conditions. */
        $pattern = '/^' . strtr($str, [ '%' => '.*' ]);

        /* Pour rendre la regex inssencible à la case. */
        $pattern .= $operator === 'like' || $operator === 'not like'
            ? '$/'
            : '$/i';

        $this->where[]   = [
            'type'      => __FUNCTION__,
            'column'    => $column,
            'condition' => 'regex',
            'value'     => $pattern,
            'bool'      => $bool,
            /* Pour inverser le comportement du like. */
            'not'       => $operator === 'not like' || $operator === 'not ilike'
        ];
    }
}
