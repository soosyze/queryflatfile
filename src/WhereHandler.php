<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

use Soosyze\Queryflatfile\Exception\Query\OperatorNotFoundException;
use Soosyze\Queryflatfile\Exception\Query\QueryException;

/**
 * Pattern fluent pour la création des clauses (conditions) de manipulation des données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @phpstan-type Between array{min: numeric|string, max: numeric|string}
 * @phpstan-type WhereToArray array{
 *      bool: string,
 *      columnName: string,
 *      columnNames?: array,
 *      condition: string,
 *      not: bool,
 *      type: string,
 *      value: array|Between|null|scalar|Where,
 * }
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
     * @phpstan-var WhereToArray[]
     */
    protected array $where = [];

    /**
     * Ajoute une condition simple pour la requête.
     * Si la valeur du champ est égal (non égale, supérieur à, ...)  par rapport à une valeur.
     *
     * @param string      $columnName Nom d'une colonne.
     * @param string      $operator   Type de condition.
     * @param null|scalar $value      Valeur de teste.
     * @param string      $bool       Porte logique de la condition (and|or).
     * @param bool        $not        Inverse la condition.
     *
     * @throws OperatorNotFoundException The condition is not exist.
     */
    public function where(
        string $columnName,
        string $operator,
        null|bool|string|int|float $value,
        string $bool = self::EXP_AND,
        bool $not = false
    ): self {
        $condition = $this->tryOperator($operator);

        if (in_array($condition, [ 'like', 'ilike', 'not like', 'not ilike' ])) {
            if (!\is_string($value)) {
                throw new QueryException();
            }
            $this->like(
                $columnName,
                $condition,
                $value,
                $bool,
                str_contains($condition, 'not')
            );

            return $this;
        }

        $type = __FUNCTION__;

        $this->where[] = ['bool' => $bool, 'columnName' => $columnName, 'condition' => $condition, 'not' => $not, 'type' => $type, 'value' => $value];

        return $this;
    }

    /**
     * Alias inverse de la fonction where().
     *
     * @param null|scalar $value Valeur de teste.
     */
    public function notWhere(
        string $columnName,
        string $operator,
        null|bool|string|int|float $value
    ): self {
        $this->where($columnName, $operator, $value, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction where().
     *
     * @param null|scalar $value Valeur de teste.
     */
    public function orWhere(
        string $columnName,
        string $operator,
        null|bool|string|int|float $value
    ): self {
        $this->where($columnName, $operator, $value, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction where().
     *
     * @param null|scalar $value Valeur de teste.
     */
    public function orNotWhere(string $columnName, string $operator, null|bool|string|int|float $value): self
    {
        $this->where($columnName, $operator, $value, self::EXP_OR, true);

        return $this;
    }

    /**
     * Ajoute une condition between à la requête.
     * Si la valeur du champ est compris entre 2 valeurs.
     *
     * @param string         $columnName Nom de la colonne.
     * @param numeric|string $min        Valeur minimum ou égale.
     * @param numeric|string $max        Valeur maximum ou égale.
     * @param string         $bool       Porte logique de la condition (and|or).
     * @param bool           $not        Inverse la condition.
     */
    public function between(
        string $columnName,
        string|int|float $min,
        string|int|float $max,
        string $bool = self::EXP_AND,
        bool $not = false
    ): self {
        $condition = 'between';
        $type      = __FUNCTION__;
        $value     = [ 'min' => $min, 'max' => $max ];

        $this->where[] = ['bool' => $bool, 'columnName' => $columnName, 'condition' => $condition, 'not' => $not, 'type' => $type, 'value' => $value];

        return $this;
    }

    /**
     * Alias inverse de la fonction between().
     *
     * @param numeric|string $min Valeur minimum ou égale.
     * @param numeric|string $max Valeur maximum ou égale.
     */
    public function notBetween(
        string $columnName,
        string|int|float $min,
        string|int|float $max
    ): self {
        $this->between($columnName, $min, $max, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction between().
     *
     * @param numeric|string $min Valeur minimum ou égale.
     * @param numeric|string $max Valeur maximum ou égale.
     */
    public function orBetween(
        string $columnName,
        string|int|float $min,
        string|int|float $max
    ): self {
        $this->between($columnName, $min, $max, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction between().
     *
     * @param numeric|string $min Valeur minimum ou égale.
     * @param numeric|string $max Valeur maximum ou égale.
     */
    public function orNotBetween(
        string $columnName,
        string|int|float $min,
        string|int|float $max
    ): self {
        $this->between($columnName, $min, $max, self::EXP_OR, true);

        return $this;
    }

    /**
     * Ajoute une condition in à la requête.
     * Si la valeur du champs est contenu dans une liste.
     *
     * @param string $columnName Nom de la colonne.
     * @param array  $value      Valeurs à tester.
     * @param string $bool       Porte logique de la condition (and|or).
     * @param bool   $not        Inverse la condition.
     */
    public function in(
        string $columnName,
        array $value,
        string $bool = self::EXP_AND,
        bool $not = false
    ): self {
        $condition = 'in';
        $type      = __FUNCTION__;

        $this->where[] = ['bool' => $bool, 'columnName' => $columnName, 'condition' => $condition, 'not' => $not, 'type' => $type, 'value' => $value];

        return $this;
    }

    /**
     * Alias inverse de la fonction in().
     */
    public function notIn(string $columnName, array $value): self
    {
        $this->in($columnName, $value, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction in().
     */
    public function orIn(string $columnName, array $value): self
    {
        $this->in($columnName, $value, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction in().
     */
    public function orNotIn(string $columnName, array $value): self
    {
        $this->in($columnName, $value, self::EXP_OR, true);

        return $this;
    }

    /**
     * Ajoute une condition isNull à la requête.
     * Si la valeur du champ est strictement égale à null.
     *
     * @param string $columnName Nom de la colonne.
     * @param string $bool       Porte logique de la condition (and|or).
     * @param bool   $not        Inverse la condition.
     */
    public function isNull(
        string $columnName,
        string $bool = self::EXP_AND,
        bool $not = false
    ): self {
        $condition = '===';
        $type      = __FUNCTION__;
        $value     = null;

        $this->where[] = ['bool' => $bool, 'columnName' => $columnName, 'condition' => $condition, 'not' => $not, 'type' => $type, 'value' => $value];

        return $this;
    }

    /**
     * Alias inverse de la fonction isNull().
     */
    public function isNotNull(string $columnName): self
    {
        $this->isNull($columnName, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction isNull().
     */
    public function orIsNull(string $columnName): self
    {
        $this->isNull($columnName, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction isNull()
     */
    public function orIsNotNull(string $columnName): self
    {
        $this->isNull($columnName, self::EXP_OR, true);

        return $this;
    }

    /**
     * Ajoute une condition avec une expression régulière à la requête.
     *
     * @param string $columnName Nom de la colonne.
     * @param string $value      Expression régulière.
     * @param string $bool       Porte logique de la condition (and|or).
     * @param bool   $not        Inverse la condition.
     */
    public function regex(
        string $columnName,
        string $value,
        string $bool = self::EXP_AND,
        bool $not = false
    ): self {
        $condition = 'regex';
        $type      = __FUNCTION__;

        $this->where[] = ['bool' => $bool, 'columnName' => $columnName, 'condition' => $condition, 'not' => $not, 'type' => $type, 'value' => $value];

        return $this;
    }

    /**
     *  Alias inverse de la fonction regex().
     */
    public function notRegex(string $columnName, string $pattern): self
    {
        $this->regex($columnName, $pattern, self::EXP_AND, true);

        return $this;
    }

    /**
     *  Alias avec la porte logique 'OR' de la fonction regex().
     */
    public function orRegex(string $columnName, string $pattern): self
    {
        $this->regex($columnName, $pattern, self::EXP_OR);

        return $this;
    }

    /**
     *  Alias inverse avec la porte logique 'OR' de la fonction regex()
     */
    public function orNotRegex(string $columnName, string $pattern): self
    {
        $this->regex($columnName, $pattern, self::EXP_OR, true);

        return $this;
    }

    /**
     * Ajoute une sous-condition pour la requête.
     */
    public function whereGroup(
        \Closure $callable,
        string $bool = self::EXP_AND,
        bool $not = false
    ): void {
        $where = new Where();
        call_user_func_array($callable, [ &$where ]);

        $this->where[] = [
            'type'        => __FUNCTION__,
            'columnName'  => '',
            'columnNames' => $where->getColumnNames(),
            'condition'   => '',
            'value'       => $where,
            'bool'        => $bool,
            'not'         => $not
        ];
    }

    /**
     * Alias inverse de la fonction whereGroup().
     */
    public function notWhereGroup(\Closure $callable): self
    {
        $this->whereGroup($callable, self::EXP_AND, true);

        return $this;
    }

    /**
     * Alias avec la porte logique 'OR' de la fonction whereGroup().
     */
    public function orWhereGroup(\Closure $callable): self
    {
        $this->whereGroup($callable, self::EXP_OR);

        return $this;
    }

    /**
     * Alias inverse avec la porte logique 'OR' de la fonction whereGroup().
     */
    public function orNotWhereGroup(\Closure $callable): self
    {
        $this->whereGroup($callable, self::EXP_OR, true);

        return $this;
    }

    /**
     * Ajoute une condition like pour la requête.
     */
    protected function like(
        string $columnName,
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

        $this->where[] = ['bool' => $bool, 'columnName' => $columnName, 'condition' => $condition, 'not' => $not, 'type' => $type, 'value' => $value];
    }

    /**
     * Filtre l'opérateur.
     *
     *
     * @throws OperatorNotFoundException
     */
    private function tryOperator(string $operator): string
    {
        $condition = strtolower($operator);

        if (!in_array($condition, self::CONDITION)) {
            throw new OperatorNotFoundException(
                sprintf('The condition %s is not exist.', $operator)
            );
        }

        return $condition;
    }
}
