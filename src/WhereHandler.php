<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\Exception\Query\OperatorNotFound;
use Queryflatfile\Exception\Query\QueryException;

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
    public const EXP_AND = 'and';

    /**
     * Représente une expréssion OR.
     */
    public const EXP_OR = 'or';

    /**
     * Les conditions binaires autorisées.
     */
    private const CONDITION = [
        '=', '==',  '===', '!==', '!=', '<>', '<', '<=', '>', '>=',
        'like', 'not like', 'ilike', 'not ilike'
    ];

    /**
     * Les conditions à exécuter.
     *
     * @var array
     */
    protected $where = [];

    /**
     * Ajoute une condition simple pour la requête.
     * Si la valeur du champ est égal (non égale, supérieur à, ...)  par rapport à une valeur.
     *
     * @param callable|string     $column   Sous condition ou une colonne.
     * @param null|string         $operator Type de condition.
     * @param null|numeric|string $value    Valeur de teste.
     * @param string              $bool     Porte logique de la condition (and|or).
     * @param bool                $not      Inverse la condition.
     *
     * @throws OperatorNotFound The condition is not exist.
     */
    public function where(
        $column,
        ?string $operator = null,
        $value = null,
        string $bool = self::EXP_AND,
        bool $not = false
    ): self {
        if (\is_callable($column)) {
            $this->whereCallback($column, $bool, $not);

            return $this;
        }

        $condition = $this->filterOperator($operator);

        if (in_array($condition, [ 'like', 'ilike', 'not like', 'not ilike' ])) {
            if (!\is_string($value)) {
                throw new QueryException();
            }
            $this->like(
                $column,
                $condition,
                $value,
                $bool,
                strpos($condition, 'not') !== false
            );

            return $this;
        }

        $type = __FUNCTION__;

        $this->where[] = compact('bool', 'column', 'condition', 'not', 'type', 'value');

        return $this;
    }

    /**
     * Alias inverse de la fonction where().
     *
     * @param callable|string $column Sous condition ou une colonne.
     * @param mixed           $value  Valeur de teste.
     */
    public function notWhere($column, ?string $operator = null, $value = null): self
    {
        $this->where($column, $operator, $value, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction where().
     *
     * @param callable|string $column Sous condition ou une colonne.
     * @param mixed           $value  Valeur de teste.
     */
    public function orWhere($column, ?string $operator = null, $value = null): self
    {
        $this->where($column, $operator, $value, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction where().
     *
     * @param callable|string $column Sous condition ou une colonne.
     * @param mixed           $value  Valeur de teste.
     */
    public function orNotWhere($column, ?string $operator = null, $value = null): self
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
     */
    public function between(
        string $column,
        $min,
        $max,
        string $bool = self::EXP_AND,
        bool $not = false
    ): self {
        $condition = 'between';
        $type      = __FUNCTION__;
        $value     = [ 'min' => $min, 'max' => $max ];

        $this->where[] = compact('bool', 'column', 'condition', 'not', 'type', 'value');

        return $this;
    }

    /**
     * Alias inverse de la fonction between().
     *
     * @param mixed $min Valeur minimum ou égale.
     * @param mixed $max Valeur maximum ou égale.
     */
    public function notBetween(string $column, $min, $max): self
    {
        $this->between($column, $min, $max, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction between().
     *
     * @param mixed $min Valeur minimum ou égale.
     * @param mixed $max Valeur maximum ou égale.
     */
    public function orBetween(string $column, $min, $max): self
    {
        $this->between($column, $min, $max, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction between().
     *
     * @param mixed $min Valeur minimum ou égale.
     * @param mixed $max Valeur maximum ou égale.
     */
    public function orNotBetween(string $column, $min, $max): self
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
     */
    public function in(
        string $column,
        array $value,
        string $bool = self::EXP_AND,
        bool $not = false
    ): self {
        $condition = 'in';
        $type      = __FUNCTION__;

        $this->where[] = compact('bool', 'column', 'condition', 'not', 'type', 'value');

        return $this;
    }

    /**
     * Alias inverse de la fonction in().
     */
    public function notIn(string $column, array $value): self
    {
        $this->in($column, $value, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction in().
     */
    public function orIn(string $column, array $value): self
    {
        $this->in($column, $value, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction in().
     */
    public function orNotIn(string $column, array $value): self
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
     */
    public function isNull(
        string $column,
        string $bool = self::EXP_AND,
        bool $not = false
    ): self {
        $condition = '===';
        $type      = __FUNCTION__;
        $value     = null;

        $this->where[] = compact('bool', 'column', 'condition', 'not', 'type', 'value');

        return $this;
    }

    /**
     * Alias inverse de la fonction isNull().
     */
    public function isNotNull(string $column): self
    {
        $this->isNull($column, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction isNull().
     */
    public function orIsNull(string $column): self
    {
        $this->isNull($column, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction isNull()
     */
    public function orIsNotNull(string $column): self
    {
        $this->isNull($column, self::EXP_OR, true);

        return $this;
    }

    /**
     * Ajoute une condition avec une expression régulière à la requête.
     *
     * @param string $column Nom de la colonne.
     * @param string $value  Expression régulière.
     * @param string $bool   Porte logique de la condition (and|or).
     * @param bool   $not    Inverse la condition.
     */
    public function regex(
        string $column,
        string $value,
        string $bool = self::EXP_AND,
        bool $not = false
    ): self {
        $condition = 'regex';
        $type      = __FUNCTION__;

        $this->where[] = compact('bool', 'column', 'condition', 'not', 'type', 'value');

        return $this;
    }

    /**
     *  Alias inverse de la fonction regex().
     */
    public function notRegex(string $column, string $pattern): self
    {
        $this->regex($column, $pattern, self::EXP_AND, true);

        return $this;
    }

    /**
     *  Alias avec la porte logique 'OR' de la fonction regex().
     */
    public function orRegex(string $column, string $pattern): self
    {
        $this->regex($column, $pattern, self::EXP_OR);

        return $this;
    }

    /**
     *  Alias inverse avec la porte logique 'OR' de la fonction regex()
     */
    public function orNotRegex(string $column, string $pattern): self
    {
        $this->regex($column, $pattern, self::EXP_OR, true);

        return $this;
    }

    /**
     * Ajoute une sous-condition pour la requête.
     */
    protected function whereCallback(
        callable $column,
        string $bool = self::EXP_AND,
        bool $not = false
    ): void {
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
     * @param string $pattern
     * @param string $bool
     * @param bool   $not
     *
     * @return void
     */
    protected function like(
        string $column,
        string $operator,
        string $pattern,
        string $bool = self::EXP_AND,
        bool $not = false
    ): void {
        /* Protection des caractères spéciaux des expressions rationnelles autre que celle imposée. */
        $str = preg_quote($pattern, '/');

        /* Le paterne commun au 4 conditions. */
        $value = '/^' . strtr($str, [ '%' => '.*' ]);

        /* Pour rendre la regex inssencible à la case. */
        $value .= $operator === 'like' || $operator === 'not like'
            ? '$/'
            : '$/i';

        $type      = __FUNCTION__;
        $condition = 'regex';

        $this->where[] = compact('bool', 'column', 'condition', 'not', 'type', 'value');
    }

    /**
     * Filtre l'opérateur.
     *
     * @param string|null $operator
     *
     * @throws QueryException
     * @throws OperatorNotFound
     *
     * @return string
     */
    private function filterOperator(?string $operator): string
    {
        if ($operator === null) {
            throw new QueryException();
        }

        $condition = strtolower($operator);

        if (!in_array($condition, self::CONDITION)) {
            throw new OperatorNotFound("The condition $operator is not exist.");
        }

        return $condition;
    }
}
