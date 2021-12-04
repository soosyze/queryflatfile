<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use BadMethodCallException;

/**
 * Met en forme et stock les données pour lancer une requête.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @method Request where(string $column, string $operator, null|scalar $value)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notWhere(string $column, string $operator, null|scalar $value)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orWhere(string $column, string $operator, null|scalar $value)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotWhere(string $column, string $operator, null|scalar $value) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request between(string $column, numeric|string $min, numeric|string $max)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orBetween(string $column, numeric|string $min, numeric|string $max)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notBetween(string $column, numeric|string $min, numeric|string $max)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotBetween(string $column, numeric|string $min, numeric|string $max) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request in(string $column, array $values)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIn(string $column, array $values)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notIn(string $column, array $values)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotIn(string $column, array $values) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request isNull(string $column)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIsNull(string $column)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request isNotNull(string $column)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIsNotNull(string $column) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request regex(string $column, string $pattern)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orRegex(string $column, string $pattern)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notRegex(string $column, string $pattern)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotRegex(string $column, string $pattern) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request whereGroup(\Closure $callable)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notWhereGroup(\Closure $callable)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orWhereGroup(\Closure $callable)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotWhereGroup(\Closure $callable) Alias de la fonction de l'objet Queryflatfile\Where
 */
abstract class RequestHandler implements RequestInterface
{
    protected const INSERT = 'insert';

    protected const UPDATE = 'update';

    protected const DELETE = 'delete';

    /**
     * Le type d'exécution (delete|update|insert).
     *
     * @var string|null
     */
    protected $execute;

    /**
     * Le nom de la table courante.
     *
     * @var string
     */
    protected $from = '';

    /**
     * Les jointures à calculer.
     *
     * @var array
     */
    protected $joins = [];

    /**
     * Les unions.
     *
     * @var array
     */
    protected $union = [];

    /**
     * Les colonnes à trier.
     *
     * @var array
     */
    protected $orderBy = [];

    /**
     * Le nombre de résultat de la requête.
     *
     * @var int
     */
    protected $limit = self::ALL;

    /**
     * Le décalage des résultats de la requête.
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * La somme de l'offset et de la limite.
     *
     * @var int
     */
    protected $sumLimit = 0;

    /**
     * La liste des colonnes à mettre à jour.
     *
     * @var string[]
     */
    protected $columns = [];

    /**
     * Les valeurs à insérer ou mettre à jour.
     *
     * @var array
     */
    protected $values = [];

    /**
     * Les conditions de la requête.
     *
     * @var Where|null
     */
    protected $where = null;

    /**
     * Permet d'utiliser les méthodes de l'objet \Queryflatfile\Where
     * et de personnaliser les closures pour certaines méthodes.
     *
     * @param string $name Nom de la méthode appelée.
     * @param array  $args Pararètre de la méthode.
     *
     * @throws BadMethodCallException
     *
     * @return $this
     */
    public function __call(string $name, array $args): self
    {
        $this->where = $this->where ?? new Where();

        if (!method_exists($this->where, $name)) {
            throw new BadMethodCallException(sprintf('The %s method is missing.', $name));
        }

        $this->where->$name(...$args);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function delete()
    {
        $this->execute = self::DELETE;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function from(string $table)
    {
        $this->from = $table;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function insertInto(string $table, array $columns)
    {
        $this->execute = self::INSERT;
        $this->from($table)->select(...$columns);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function leftJoin(string $table, $column, string $operator = '', $value = null)
    {
        if ($column instanceof \Closure) {
            $this->joinGroup(self::JOIN_LEFT, $table, $column);

            return $this;
        }
        $this->join(self::JOIN_LEFT, $table, $column, $operator, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(int $limit, int $offset = 0)
    {
        $this->limit    = $limit;
        $this->offset   = $offset;
        $this->sumLimit = $offset + $limit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy(string $columns, int $order = SORT_ASC)
    {
        $this->orderBy[ $columns ] = $order;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rightJoin(string $table, $column, string $operator = '', $value = null)
    {
        if ($column instanceof \Closure) {
            $this->joinGroup(self::JOIN_RIGHT, $table, $column);

            return $this;
        }
        $this->join(self::JOIN_RIGHT, $table, $column, $operator, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function select(string ...$columns)
    {
        foreach ($columns as $column) {
            $this->columns[] = $column;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function union(RequestInterface $request, string $type = self::UNION_SIMPLE)
    {
        $this->union[] = compact('request', 'type');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unionAll(RequestInterface $request)
    {
        return $this->union($request, self::UNION_ALL);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $table, array $columns)
    {
        $this->execute = self::UPDATE;
        $this->from($table)->select(...array_keys($columns));
        $this->values  = $columns;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function values(array $columns)
    {
        $this->values[] = $columns;

        return $this;
    }

    /**
     * Initialise les paramètre de la requête.
     *
     * @return $this
     */
    protected function init()
    {
        $this->columns  = [];
        $this->execute  = null;
        $this->from     = '';
        $this->joins    = [];
        $this->limit    = self::ALL;
        $this->offset   = 0;
        $this->orderBy  = [];
        $this->sumLimit = 0;
        $this->union    = [];
        $this->values   = [];

        return $this;
    }

    /**
     * Enregistre une jointure.
     *
     * @param string      $type     Type de la jointure.
     * @param string      $table    Nom de la table à joindre
     * @param string      $column   Nom de la colonne d'une des tables précédentes.
     * @param string      $operator Opérateur logique ou null pour une closure.
     * @param null|scalar $value    Valeur ou une colonne de la table jointe (au format nom_table.colonne)
     */
    private function join(string $type, string $table, string $column, string $operator, $value): void
    {
        $where = (new Where())->where($column, $operator, $value);

        $this->joins[] = compact('type', 'table', 'where');
    }

    private function joinGroup(string $type, string $table, \Closure $callable): void
    {
        $where = new Where();
        call_user_func_array($callable, [ &$where ]);

        $this->joins[] = compact('type', 'table', 'where');
    }
}
