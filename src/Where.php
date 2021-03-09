<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

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
    public function __toString()
    {
        $output = '';
        foreach ($this->where as $where) {
            $not = $where[ 'not' ]
                ? 'NOT'
                : '';
            switch ($where[ 'type' ]) {
                case 'where':
                    $value  = \is_int($where[ 'value' ])
                        ? $where[ 'value' ]
                        : "'{$where[ 'value' ]}'";
                    $output .= "{$where[ 'column' ]} $not " . strtoupper($where[ 'condition' ]) . " $value ";

                    break;
                case 'like':
                    $output .= "{$where[ 'column' ]} $not LIKE '{$where[ 'value' ]}' ";

                    break;
                case 'isNull':
                    $output .= "{$where[ 'column' ]} IS $not NULL ";

                    break;
                case 'in':
                    $output .= "{$where[ 'column' ]} $not IN " . implode(', ', $where[ 'value' ]) . ' ';

                    break;
                case 'whereCallback':
                    $output .= "$not ({$where[ 'value' ]}) ";

                    break;
                case 'between':
                    $output .= "{$where[ 'column' ]} $not BETWEEN {$where[ 'value' ][ 'min' ]} AND {$where[ 'value' ][ 'max' ]} ";

                    break;
                case 'regex':
                    $output .= "{$where[ 'column' ]} $not REGEX {$where[ 'value' ]} ";

                    break;
            }

            $output .= $where[ 'bool' ] === self::EXP_AND
                ? ' AND '
                : ' OR ';
        }
        /* Cherche si la dernière occurence est AND ou OR. */
        $nb = substr(strrchr($output, 'AND'), 0) === 'AND '
            ? -4
            : -3;

        /* Supprime la dernière occurence et renvoie la chaine. */
        return htmlspecialchars(substr($output, 0, $nb));
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
            if (\is_array($value[ 'column' ])) {
                $output = array_merge($output, $value[ 'column' ]);

                continue;
            }

            $output[] = $this->getColumn($value[ 'column' ]);
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
    public function execute(array $row)
    {
        $output = true;
        foreach ($this->where as $key => $value) {
            /* Si la clause est standard ou une sous clause. */
            $predicate = $value[ 'type' ] === 'whereCallback'
                ? $value[ 'value' ]->execute($row)
                : self::predicate($row[ $value[ 'column' ] ], $value[ 'condition' ], $value[ 'value' ]);
            /* Si la clause est inversé. */
            if ($value[ 'not' ]) {
                $predicate ^= 1;
            }
            /* Les retours des types regex et like doivent être non null. */
            if ($value[ 'type' ] === 'regex' || $value[ 'type' ] === 'like') {
                $predicate &= $row[ $value[ 'column' ] ] !== null;
            }

            if ($key === 0) {
                $output = $predicate;
            } elseif ($value[ 'bool' ] === self::EXP_AND) {
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
        $output = true;
        foreach ($this->where as $key => $value) {
            $predicate = true;

            switch ($value[ 'type' ]) {
                case 'where':
                case 'isNull':
                    $val = self::isColumn($value[ 'value' ])
                        ? $rowTable[ substr(strrchr($value[ 'value' ], '.'), 1) ]
                        : $value[ 'value' ];

                    $predicate = self::predicate($row[ $value[ 'column' ] ], $value[ 'condition' ], $val);

                    break;
                case 'whereCallback':
                    $predicate = $value[ 'value' ]->execute($row);

                    break;
            }

            if ($key === 0) {
                $output = $predicate;
            } elseif ($value[ 'bool' ] === self::EXP_AND) {
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
     *
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
            case 'regex':
                return preg_match($value, $columns) === 1;
            case 'between':
                return $columns >= $value[ 'min' ] && $columns <= $value[ 'max' ];
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
    protected static function isColumn($value)
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
    protected static function getColumn($value)
    {
        return self::isColumn($value)
            ? substr(strrchr($value, '.'), 1)
            : $value;
    }
}
