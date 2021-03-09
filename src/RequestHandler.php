<?php

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
        $this->execute = 'delete';

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function from($table)
    {
        $this->from = $table;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function insertInto($table, array $columns)
    {
        $this->execute = 'insert';
        $this->from($table)->select($columns);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function leftJoin($table, $column, $operator = null, $value = null)
    {
        $this->join(self::JOIN_LEFT, $table, $column, $operator, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit($limit, $offset = 0)
    {
        $this->limit    = $limit;
        $this->offset   = $offset;
        $this->sumLimit = $offset + $limit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy($columns, $order = SORT_ASC)
    {
        $this->orderBy[ $columns ] = $order;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rightJoin($table, $column, $operator = null, $value = null)
    {
        $this->join(self::JOIN_RIGHT, $table, $column, $operator, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function select()
    {
        foreach (func_get_args() as $column) {
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
    public function union(RequestInterface $request, $type = self::UNION_SIMPLE)
    {
        $this->union[] = [ 'request' => $request, 'type' => $type ];

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
    public function update($table, array $columns)
    {
        $this->execute = 'update';
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
     *
     * @return void
     */
    private function join($type, $table, $column, $operator = null, $value = null)
    {
        if ($column instanceof \Closure) {
            $where = new Where();
            call_user_func_array($column, [ &$where ]);
        } else {
            $where = ( new Where() )
                ->where($column, $operator, $value);
        }
        $this->joins[] = [
            'type'  => $type, 'table' => $table, 'where' => $where
        ];
    }
}
