<?php

/**
 * Queryflatfile
 *
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

/**
 * Pattern fluent pour la création des clauses (conditions) de manipulation des données.
 *
 * @author Mathieu NOËL
 */
class Where extends WhereHandler
{
    /**
     *
     */
    {








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
            if (!\is_array($value[ 'column' ])) {
                $output[] = $this->getColumn($value[ 'column' ]);

                continue;
            }

            $output = array_merge($output, $value[ 'column' ]);
        }

        return $this->columns;
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
        foreach ($this->where as $key => $value) {
            $columns = $value[ 'column' ];
            switch ($value[ 'type' ]) {
                case 'where':
                case 'isNull':
                case 'in':
                    $predicate = self::predicate($row[ $columns ], $value[ 'condition' ], $value[ 'value' ]);

                    break;
                case 'whereCallback':
                    $predicate = $value[ 'value' ]->execute($row);

                    break;
                case 'between':
                    $predicate = self::predicate($row[ $columns ], '>=', $value[ 'min' ]) && self::predicate($row[ $columns ], '<=', $value[ 'max' ]);

                    break;
                case 'regex':
                    $predicate = !empty($value[ 'not' ])
                        ? !preg_match($value[ 'value' ], $row[ $columns ])
                        : preg_match($value[ 'value' ], $row[ $columns ]);
                    $predicate &= self::predicate($row[ $columns ], '!==', null);

                    break;
            }

            if ($value[ 'type' ] !== 'regex') {
                $predicate = !empty($value[ 'not' ])
                    ? !$predicate
                    : $predicate;
            }

            if ($key == 0) {
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
        foreach ($this->where as $key => $value) {
            $columns = $value[ 'column' ];
            switch ($value[ 'type' ]) {
                case 'where':
                case 'isNull':
                    $val       = $this->isColumn($value[ 'value' ])
                        ? $rowTable[ substr(strrchr($value[ 'value' ], '.'), 1) ]
                        : $value[ 'value' ];
                    $predicate = self::predicate($row[ $columns ], $value[ 'condition' ], $val);

                    break;
                case 'whereCallback':
                    $predicate = $value[ 'value' ]->execute($row);

                    break;
            }

            if ($key == 0) {
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
    protected function isColumn($value)
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
    protected function getColumn($value)
    {
        return $this->isColumn($value)
            ? substr(strrchr($value, '.'), 1)
            : $value;
    }
}
