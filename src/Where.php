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
            switch ($where[ 'type' ]) {
                case 'where':
                    $value  = \is_int($where[ 'value' ])
                        ? $where[ 'value' ]
                        : sprintf('\'%s\'', addslashes($where[ 'value' ]));
                    $output .= sprintf('%s%s %s %s ', $not, addslashes($where[ 'column' ]), $where[ 'condition' ], $value);

                    break;
                case 'like':
                    $output .= sprintf('%s %sLIKE %s ', addslashes($where[ 'column' ]), $not, addslashes($where[ 'value' ]));

                    break;
                case 'isNull':
                    $output .= sprintf('%s IS %sNULL ', addslashes($where[ 'column' ]), $not);

                    break;
                case 'in':
                    $output .= sprintf(
                        '%s %sIN %s ',
                        addslashes($where[ 'column' ]),
                        $not,
                        addslashes(implode(', ', $where[ 'value' ]))
                    );

                    break;
                case 'whereGroup':
                    $output .= sprintf('%s(%s) ', $not, $where[ 'value' ]);

                    break;
                case 'between':
                    $output .= sprintf(
                        '%s %sBETWEEN %s AND %s ',
                        addslashes($where[ 'column' ]),
                        $not,
                        addslashes((string) $where[ 'value' ][ 'min' ]),
                        addslashes((string) $where[ 'value' ][ 'max' ])
                    );

                    break;
                case 'regex':
                    $output .= sprintf('%s %sREGEX %s ', addslashes($where[ 'column' ]), $not, $where[ 'value' ]);

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
     * Retourne toutes les colonnes utilisées pour créer la clause.
     *
     * @return array Colonnes.
     */
    public function getColumns(): array
    {
        $output = [];
        foreach ($this->where as $value) {
            if (\is_array($value[ 'column' ])) {
                $output = array_merge($output, $value[ 'column' ]);

                continue;
            }

            $output[] = self::getColumn($value[ 'column' ]);
        }

        return $output;
    }

    /**
     * Retourne TRUE si la suite de condition enregistrée valide les champs du tableau.
     *
     * @param array $row Tableau associatif de champ.
     *
     * @return bool
     */
    public function execute(array $row): bool
    {
        $output = true;
        foreach ($this->where as $key => $value) {
            /* Si la clause est standard ou une sous clause. */
            $predicate = $value[ 'type' ] === 'whereGroup'
                ? $value[ 'value' ]->execute($row)
                : self::predicate($row[ $value[ 'column' ] ], $value[ 'condition' ], $value[ 'value' ]);
            /* Si la clause est inversé. */
            if ($value[ 'not' ]) {
                $predicate = !$predicate;
            }
            /* Les retours des types regex et like doivent être non null. */
            if ($value[ 'type' ] === 'regex' || $value[ 'type' ] === 'like') {
                $predicate = $predicate && $row[ $value[ 'column' ] ] !== null;
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
     * @return bool
     */
    public function executeJoin(array $row, array &$rowTable): bool
    {
        $output = true;
        foreach ($this->where as $key => $value) {
            $predicate = true;

            if ($value[ 'type' ] === 'whereGroup') {
                $predicate = $value[ 'value' ]->executeJoin($row, $rowTable);
            } else {
                $val = $rowTable[ self::getColumn($value[ 'value' ]) ];

                $predicate = self::predicate($row[ $value[ 'column' ] ], $value[ 'condition' ], $val);
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
     * @param bool|null|numeric|string       $columns  Valeur à tester.
     * @param string                         $operator Condition à réaliser.
     * @param array|bool|null|numeric|string $value    Valeur de comparaison.
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected static function predicate($columns, string $operator, $value): bool
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
                /** @var array $value */
                return in_array($columns, $value);
            case 'regex':
                if ($columns === null) {
                    return false;
                }
                /** @var string $value */
                return preg_match($value, (string) $columns) === 1;
            case 'between':
                /** @var array{min: string|numeric, max: string|numeric} $value */
                return $columns >= $value[ 'min' ] && $columns <= $value[ 'max' ];
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
}
