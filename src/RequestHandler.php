<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

/**
 * Met en forme et stock les données pour lancer une requête.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
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
        $this->from($table)->select($columns);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function leftJoin(string $table, $column, ?string $operator = null, ?string $value = null)
    {
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
    public function rightJoin(string $table, $column, ?string $operator = null, ?string $value = null)
    {
        $this->join(self::JOIN_RIGHT, $table, $column, $operator, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function select(...$columns)
    {
        foreach ($columns as $column) {
            /* Dans le cas ou les colonnes sont normales. */
            if (!\is_array($column)) {
                $this->columns[] = $column;

                continue;
            }
            /* Dans le cas ou les colonnes sont dans un tableau. */
            foreach ($column as $fields) {
                $this->columns[] = $fields;
            }
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
        $this->from($table)->select(array_keys($columns));
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
     * @param string          $type     Type de la jointure.
     * @param string          $table    Nom de la table à joindre
     * @param string|\Closure $column   Nom de la colonne d'une des tables précédentes
     *                                  ou une closure pour affiner les conditions.
     * @param string|null     $operator Opérateur logique ou null pour une closure.
     * @param string|null     $value    Valeur
     *                                  ou une colonne de la table jointe (au format nom_table.colonne)
     *                                  ou null pour une closure.
     */
    private function join(string $type, string $table, $column, ?string $operator = null, ?string $value = null): void
    {
        $where = new Where();

        $column instanceof \Closure
            ? call_user_func_array($column, [ &$where ])
            : $where->where($column, $operator, $value);

        $this->joins[] = compact('type', 'table', 'where');
    }
}
