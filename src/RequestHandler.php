<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

use BadMethodCallException;

/**
 * Met en forme et stock les données pour lancer une requête.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @method Request where(string $columnName, string $operator, null|scalar $value)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notWhere(string $columnName, string $operator, null|scalar $value)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orWhere(string $columnName, string $operator, null|scalar $value)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotWhere(string $columnName, string $operator, null|scalar $value) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request between(string $columnName, numeric|string $min, numeric|string $max)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orBetween(string $columnName, numeric|string $min, numeric|string $max)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notBetween(string $columnName, numeric|string $min, numeric|string $max)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotBetween(string $columnName, numeric|string $min, numeric|string $max) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request in(string $columnName, array $values)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIn(string $columnName, array $values)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notIn(string $columnName, array $values)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotIn(string $columnName, array $values) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request isNull(string $columnName)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIsNull(string $columnName)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request isNotNull(string $columnName)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orIsNotNull(string $columnName) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request regex(string $columnName, string $pattern)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orRegex(string $columnName, string $pattern)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notRegex(string $columnName, string $pattern)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotRegex(string $columnName, string $pattern) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @method Request whereGroup(\Closure $callable)      Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request notWhereGroup(\Closure $callable)   Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orWhereGroup(\Closure $callable)    Alias de la fonction de l'objet Queryflatfile\Where
 * @method Request orNotWhereGroup(\Closure $callable) Alias de la fonction de l'objet Queryflatfile\Where
 *
 * @phpstan-import-type TableData from Schema
 *
 * @phpstan-type Join array{type: string, table: string, where: Where}
 * @phpstan-type Union array{request: RequestInterface, type: string}
 */
abstract class RequestHandler implements RequestInterface
{
    protected const INSERT = 'insert';

    protected const UPDATE = 'update';

    protected const DELETE = 'delete';

    /**
     * La valeur pour une union simple.
     */
    protected const UNION_SIMPLE = 'simple';

    /**
     * La valeur pour une union totale.
     */
    protected const UNION_ALL = 'all';

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
     *
     * @phpstan-var Join[]
     */
    protected $joins = [];

    /**
     * Les unions.
     *
     * @var array
     *
     * @phpstan-var Union[]
     */
    protected $unions = [];

    /**
     * Les colonnes à trier.
     *
     * @var array<string, int>
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
    protected $columnNames = [];

    /**
     * Les valeurs à insérer ou mettre à jour.
     *
     * @var array
     *
     * @phpstan-var TableData
     */
    protected $values = [];

    /**
     * Les conditions de la requête.
     *
     * @var Where|null
     */
    protected $where;

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
    public function from(string $tableName)
    {
        $this->from = $tableName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    /**
     * {@inheritdoc}
     */
    public function insertInto(string $tableName, array $columnNames)
    {
        $this->execute = self::INSERT;
        $this->from($tableName)->select(...$columnNames);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function leftJoin(string $tableName, $column, string $operator = '', string $value ='')
    {
        if ($column instanceof \Closure) {
            $this->joinGroup(self::JOIN_LEFT, $tableName, $column);

            return $this;
        }
        $this->join(self::JOIN_LEFT, $tableName, $column, $operator, $value);

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
    public function orderBy(string $columnName, int $order = SORT_ASC)
    {
        $this->orderBy[ $columnName ] = $order;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rightJoin(string $tableName, $column, string $operator = '', string $value = '')
    {
        if ($column instanceof \Closure) {
            $this->joinGroup(self::JOIN_RIGHT, $tableName, $column);

            return $this;
        }
        $this->join(self::JOIN_RIGHT, $tableName, $column, $operator, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function select(string ...$columnNames)
    {
        $this->columnNames = $columnNames;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function union(RequestInterface $request)
    {
        $this->unions[] = [ 'request' => $request, 'type' => self::UNION_SIMPLE ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unionAll(RequestInterface $request)
    {
        $this->unions[] = [ 'request' => $request, 'type' => self::UNION_ALL ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $tableName, array $row)
    {
        $this->execute     = self::UPDATE;
        $this->from($tableName)->select(...array_keys($row));
        $this->values[ 0 ] = $row;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function values(array $rowValues)
    {
        $this->values[] = $rowValues;

        return $this;
    }

    /**
     * Initialise les paramètre de la requête.
     *
     * @return $this
     */
    protected function init()
    {
        $this->columnNames = [];
        $this->execute     = null;
        $this->from        = '';
        $this->joins       = [];
        $this->limit       = self::ALL;
        $this->offset      = 0;
        $this->orderBy     = [];
        $this->sumLimit    = 0;
        $this->unions      = [];
        $this->values      = [];

        return $this;
    }

    /**
     * Enregistre une jointure.
     *
     * @param string $type       Type de la jointure.
     * @param string $tableName  Nom de la table à joindre
     * @param string $columnName Nom de la colonne d'une des tables précédentes.
     * @param string $operator   Opérateur logique ou null pour une closure.
     * @param string $value      Valeur ou une colonne de la table jointe (au format nom_table.colonne)
     */
    private function join(string $type, string $tableName, string $columnName, string $operator, string $value): void
    {
        $where = new Where();
        $where->where($columnName, $operator, $value);

        $this->joins[] = [ 'type' => $type, 'table' => $tableName, 'where' => $where ];
    }

    /**
     * Enregistre une jointure avec une condition groupée.
     *
     * @param string   $type      Type de la jointure.
     * @param string   $tableName Nom de la table à joindre
     * @param \Closure $callable  Nom de la colonne d'une des tables précédentes.
     */
    private function joinGroup(string $type, string $tableName, \Closure $callable): void
    {
        $where = new Where();
        call_user_func_array($callable, [ &$where ]);

        $this->joins[] = [ 'type' => $type, 'table' => $tableName, 'where' => $where ];
    }
}
