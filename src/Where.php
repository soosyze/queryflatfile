<?php

declare(strict_types=1);

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
 *
 * @phpstan-import-type RowData from Schema
 * @phpstan-import-type Between from WhereHandler
 */
class Where extends WhereHandler
{
    /**
     * Retourne les paramètre des clauses en format pseudo SQL.
     *
     * @return string
     */
    public function __toString(): string
    {
        $output = '';
        foreach ($this->where as $where) {
            $output .= $where[ 'bool' ] === self::EXP_AND
                ? 'AND '
                : 'OR ';

            $not = $where[ 'not' ]
                ? 'NOT '
                : '';
            $whereColumn = $where[ 'columnName' ];
            switch ($where[ 'type' ]) {
                case 'where':
                    $output .= sprintf('%s%s %s %s ', $not, addslashes($whereColumn), $where[ 'condition' ], self::getValueToString($where['value']));

                    break;
                case 'like':
                    $output .= sprintf('%s %sLIKE %s ', addslashes($whereColumn), $not, self::getValueToString($where['value']));

                    break;
                case 'isNull':
                    $output .= sprintf('%s IS %sNULL ', addslashes($whereColumn), $not);

                    break;
                case 'in':
                    $output .= sprintf(
                        '%s %sIN %s ',
                        addslashes($whereColumn),
                        $not,
                        self::getValueToString($where['value'])
                    );

                    break;
                case 'whereGroup':
                    $output .= sprintf('%s(%s) ', $not, self::getValueToString($where['value']));

                    break;
                case 'between':
                    $output .= sprintf(
                        '%s %sBETWEEN %s ',
                        addslashes($whereColumn),
                        $not,
                        self::getValueToString($where['value'])
                    );

                    break;
                case 'regex':
                    $output .= sprintf('%s %sREGEX %s ', addslashes($whereColumn), $not, self::getValueToString($where['value']));

                    break;
            }
        }
        $output = trim($output, ' ');
        $str    = strpos($output, 'AND ') === 0
            ? 'AND '
            : 'OR ';

        return substr_replace($output, '', 0, strlen($str));
    }

    /**
     * Retourne les nom de toutes les colonnes utilisées pour créer la clause.
     *
     * @return string[] Colonnes.
     */
    public function getColumnNames(): array
    {
        $output = [];
        foreach ($this->where as $value) {
            if (isset($value[ 'columnNames' ])) {
                $output = array_merge($output, $value[ 'columnNames' ]);

                continue;
            }

            $output[] = self::getColumn($value[ 'columnName' ]);
        }

        return $output;
    }

    /**
     * Retourne TRUE si la suite de condition enregistrée valide les champs du tableau.
     *
     * @param array $row Tableau associatif de champ.
     *
     * @phpstan-param RowData $row
     *
     * @return bool
     */
    public function execute(array $row): bool
    {
        $output = true;
        foreach ($this->where as $key => $value) {
            /* Si la clause est standard ou une sous clause. */
            if ($value[ 'value' ] instanceof Where) {
                $predicate = $value[ 'value' ]->execute($row);
            } else {
                $predicate = self::predicate($row[ $value[ 'columnName' ] ], $value[ 'condition' ], $value[ 'value' ]);
            }
            /* Si la clause est inversé. */
            if ($value[ 'not' ]) {
                $predicate = !$predicate;
            }
            /* Les retours des types regex et like doivent être non null. */
            if ($value[ 'type' ] === 'regex' || $value[ 'type' ] === 'like') {
                $predicate = $predicate && $row[ $value[ 'columnName' ] ] !== null;
            }

            if ($key === 0) {
                $output = $predicate;

                continue;
            }
            $output = $value[ 'bool' ] === self::EXP_AND
                ? $output && $predicate
                : $output || $predicate;
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
     * @phpstan-param RowData $row
     * @phpstan-param RowData $rowTable
     *
     * @return bool
     */
    public function executeJoin(array $row, array &$rowTable): bool
    {
        $output = true;
        foreach ($this->where as $key => $value) {
            $predicate = true;

            if ($value[ 'value' ] instanceof Where) {
                $predicate = $value[ 'value' ]->executeJoin($row, $rowTable);
            } else {
                /** @var array{value:string, columnName: string, condition: string, bool:string} $value */
                $val = $rowTable[ self::getColumn($value[ 'value' ]) ];

                $predicate = self::predicate($row[ $value[ 'columnName' ] ], $value[ 'condition' ], $val);
            }

            if ($key === 0) {
                $output = $predicate;

                continue;
            }
            $output = $value[ 'bool' ] === self::EXP_AND
                ? $output && $predicate
                : $output || $predicate;
        }

        return $output;
    }

    /**
     * Retourne TRUE si la condition est validée.
     *
     * @param null|scalar       $column   Valeur à tester.
     * @param string            $operator Condition à réaliser.
     * @param array|null|scalar $value    Valeur de comparaison.
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected static function predicate($column, string $operator, $value): bool
    {
        switch ($operator) {
            case '==':
                return $column == $value;
            case '=':
            case '===':
                return $column === $value;
            case '!==':
                return $column !== $value;
            case '!=':
                return $column != $value;
            case '<>':
                return $column <> $value;
            case '<':
                return $column < $value;
            case '<=':
                return $column <= $value;
            case '>':
                return $column > $value;
            case '>=':
                return $column >= $value;
            case 'in':
                /** @var array $value */
                return in_array($column, $value);
            case 'regex':
                if ($column === null) {
                    return false;
                }
                /** @var string $value */
                return preg_match($value, (string) $column) === 1;
            case 'between':
                /** @var Between $value */
                return $column >= $value[ 'min' ] && $column <= $value[ 'max' ];
        }

        throw new OperatorNotFound(
            sprintf('The %s operator is not supported.', $operator)
        );
    }

    /**
     * Retourne le nom de la colonne ou la valeur.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function getColumn(string $value): string
    {
        return strrchr($value, '.') !== false
            ? substr(strrchr($value, '.'), 1)
            : $value;
    }

    /**
     * @param array|null|scalar|Where $value
     *
     * @return string
     */
    protected static function getValueToString($value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return sprintf('\'%s\'', addslashes($value));
        }
        if ($value instanceof Where) {
            return (string) $value;
        }
        if (is_array($value)) {
            if (isset($value[ 'min' ], $value[ 'max' ]) && is_scalar($value[ 'min' ]) && is_scalar($value[ 'max' ])) {
                return sprintf(
                    '%s AND %s',
                    self::getValueToString($value[ 'min' ]),
                    self::getValueToString($value[ 'max' ])
                );
            }

            return implode(
                ', ',
                array_map(
                    function ($item): string {
                        return self::getValueToString($item);
                    },
                    $value
                )
            );
        }

        return 'null';
    }
}
