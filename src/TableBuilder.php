<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\Exception\TableBuilder\ColumnsNotFoundException;
use Queryflatfile\Exception\TableBuilder\ColumnsValueException;
use Queryflatfile\Exception\TableBuilder\TableBuilderException;

/**
 * Pattern fluent pour la création et configuration des types de données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class TableBuilder
{
    const CURRENT_DATE_DEFAULT = 'current_date';

    const CURRENT_DATETIME_DEFAULT = 'current_datetime';

    /**
     * Les champs et leurs paramètres.
     *
     * @var array
     */
    private $builder = [];

    /**
     * La valeur des champs incrémentaux.
     *
     * @var int|null
     */
    private $increment = null;

    /**
     * Enregistre un champ de type `boolean`, true ou false.
     * http://php.net/manual/fr/language.types.boolean.php
     *
     * @param string $name Nom du champ.
     *
     * @return $this
     */
    public function boolean($name)
    {
        $this->builder[ $name ][ 'type' ] = 'boolean';

        return $this;
    }

    /**
     * Enregistre un champ de type `char` avec une limite de taille par défaut de un caractère.
     * http://php.net/language.types.string
     *
     * @param string $name   Nom du champ
     * @param int    $length longueur maximum de la chaine.
     *
     * @throws TableBuilderException
     *
     * @return $this
     */
    public function char($name, $length = 1)
    {
        if (!\is_int($length) || $length < 0) {
            throw new TableBuilderException('The length passed in parameter is not of numeric type.');
        }
        $this->builder[ $name ] = [ 'type' => 'char', 'length' => $length ];

        return $this;
    }

    /**
     * Enregistre un champ de type `date` sous le format Y-m-d.
     *
     * @param string $name Nom du champ.
     *
     * @return $this
     */
    public function date($name)
    {
        $this->builder[ $name ][ 'type' ] = 'date';

        return $this;
    }

    /**
     * Enregistre un champ de type `datetime`, sous le format Y-m-d H:i:s.
     *
     * @param string $name Nom du champ.
     *
     * @return $this
     */
    public function datetime($name)
    {
        $this->builder[ $name ][ 'type' ] = 'datetime';

        return $this;
    }

    /**
     * Enregistre un champ de type `float`, nombre à virgule flottant.
     * http://php.net/manual/fr/language.types.float.php
     * Valeur d'insertion autorisé : 1, '1', 1.0, '1.0' (enregistrera 1.0).
     *
     * @param string $name Nom du champ.
     *
     * @return $this
     */
    public function float($name)
    {
        $this->builder[ $name ][ 'type' ] = 'float';

        return $this;
    }

    /**
     * Enregistre un champ de type `increments`, entier positif qui s'incrémente automatiquement.
     * Un seul champ increments est autorisé par table.
     * http://php.net/manual/fr/language.types.integer.php
     *
     * @param string $name nom du champ
     *
     * @throws TableBuilderException
     *
     * @return $this
     */
    public function increments($name)
    {
        if ($this->increment !== null) {
            throw new TableBuilderException('Only one incremental column is allowed per table.');
        }

        $this->builder[ $name ][ 'type' ] = 'increments';
        $this->increment                  = 0;

        return $this;
    }

    /**
     * Enregistre un champ de type `integer`, entier signié.
     * http://php.net/manual/fr/language.types.integer.php
     * Valeur d'insertion autorisé : 1, '1', 1.1, '1.1' (enregistrera 1)
     *
     * @param string $name Nom du champ.
     *
     * @return $this
     */
    public function integer($name)
    {
        $this->builder[ $name ][ 'type' ] = 'integer';

        return $this;
    }

    /**
     * Enregistre un champ de type `string` avec une limite de taille  par défaut de 255 caractères.
     * http://php.net/language.types.string
     *
     * @param string $name   Nom du champ.
     * @param int    $length Longueur maximum de la chaine.
     *
     * @throws Exception
     * @return $this
     */
    public function string($name, $length = 255)
    {
        if (!\is_int($length) || $length < 0) {
            throw new TableBuilderException('The length passed in parameter is not of numeric type.');
        }
        $this->builder[ $name ] = [ 'type' => 'string', 'length' => $length ];

        return $this;
    }

    /**
     * Enregistre un champ de type `text` sans limite de taille.
     * http://php.net/language.types.string
     *
     * @param string $name Nom du champ.
     *
     * @return $this
     */
    public function text($name)
    {
        $this->builder[ $name ][ 'type' ] = 'text';

        return $this;
    }

    /**
     * Enregistre un commentaire sur le dernier champ appelé.
     *
     * @param string $comment Commentaire du champ précédent.
     *
     * @return $this
     */
    public function comment($comment)
    {
        $this->checkPreviousBuild('comment');
        $this->builder[ key($this->builder) ][ '_comment' ] = $comment;

        return $this;
    }

    /**
     * Enregistre le champ précédent comme acceptant la valeur NULL.
     *
     * @return $this
     */
    public function nullable()
    {
        $this->checkPreviousBuild('nullable');
        $this->builder[ key($this->builder) ][ 'nullable' ] = true;

        return $this;
    }

    /**
     * Enregistre le champ précédent (uniquement de type integer) comme étant non signié.
     *
     * @throws ColumnsValueException
     * @return $this
     */
    public function unsigned()
    {
        $current = $this->checkPreviousBuild('unsigned');
        if ($current[ 'type' ] !== 'integer') {
            throw new ColumnsValueException("Impossiblie of unsigned type {$current[ 'type' ]} only integer).");
        }

        $this->builder[ key($this->builder) ][ 'unsigned' ] = true;

        return $this;
    }

    /**
     * Enregistre une valeur par défaut au champ précédent.
     * Lève une exception si la valeur par défaut ne correspond pas au type de valeur passée en paramètre.
     *
     * @param mixed $value Valeur à tester.
     *
     * @throws Exception
     * @return $this
     */
    public function valueDefault($value)
    {
        $current = $this->checkPreviousBuild('value default');
        $name    = key($this->builder);
        $type    = $current[ 'type' ];

        if ($type === 'increments') {
            throw new TableBuilderException('An incremental type column can not have a default value.');
        }

        $this->builder[ $name ][ 'default' ] = self::checkValue($name, $type, $value, $current);

        return $this;
    }

    /**
     * Retourne la valeur s'il correspond au type déclaré.
     * Sinon déclenche une exception.
     *
     * @param string $name  Nom du champ.
     * @param string $type  Type de donnée (string|text|int|float|bool|char|date|datetime).
     * @param mixed  $value Valeur à tester.
     * @param array  $arg   Arguments de tests optionnels (length).
     *
     * @throws ColumnsValueException
     * @return mixed
     */
    public static function checkValue($name, $type, $value, array $arg = [])
    {
        $error = 'The default value (' . $value . ') for column ' . $name . ' does not correspond to type ' . $type . '.';

        switch (strtolower($type)) {
            case 'string':
            case 'char':
                if (!\is_string($value)) {
                    throw new ColumnsValueException($error);
                }
                if (!isset($arg[ 'length' ]) || strlen($value) > $arg[ 'length' ]) {
                    throw new ColumnsValueException('The default value is larger than the specified size.');
                }

                break;
            case 'text':
                if (!\is_string($value)) {
                    throw new ColumnsValueException($error);
                }

                break;
            case 'integer':
            case 'increments':
                if (!\is_numeric($value)) {
                    throw new ColumnsValueException($error);
                }

                return (int) $value;
            case 'float':
                if (!\is_numeric($value)) {
                    throw new ColumnsValueException($error);
                }

                return (float) $value;
            case 'boolean':
                if (!\is_bool($value)) {
                    throw new ColumnsValueException($error);
                }

                break;
            case 'date':
                if (strtolower($value) === self::CURRENT_DATE_DEFAULT) {
                    return self::CURRENT_DATE_DEFAULT;
                }
                if (($timestamp = strtotime($value))) {
                    return date('Y-m-d', $timestamp);
                }

                throw new ColumnsValueException($error);
            case 'datetime':
                if (strtolower($value) === self::CURRENT_DATETIME_DEFAULT) {
                    return self::CURRENT_DATETIME_DEFAULT;
                }
                if (($timestamp = strtotime($value))) {
                    return date('Y-m-d H:i:s', $timestamp);
                }

                throw new ColumnsValueException($error);
            default:
                throw new ColumnsValueException("Type $type not supported");
        }

        return $value;
    }

    /**
     * Retourne le tableau contenant les configurations d'ajout.
     *
     * @return array Les configurations.
     */
    public function build()
    {
        return array_filter($this->builder, static function ($var) {
            return !isset($var[ 'opt' ]);
        });
    }

    /**
     * Retourne le tableau contenant toutes les configurations.
     *
     * @return array Les configurations.
     */
    public function buildFull()
    {
        return $this->builder;
    }

    /**
     * Retourne la liste des champs incrémentaux.
     *
     * @return int|null
     */
    public function getIncrement()
    {
        return $this->increment;
    }

    /**
     * Enregistre la suppression d'une colonne.
     *
     * @param string $name Nom de la colonne.
     *
     * @return $this
     */
    public function dropColumn($name)
    {
        $this->builder[ $name ][ 'opt' ] = 'drop';

        return $this;
    }

    /**
     * Enregistre le renommage d'une colonne.
     *
     * @param string $from Nom de la colonne.
     * @param string $to   Nouveau nom de la colonne.
     *
     * @return $this
     */
    public function renameColumn($from, $to)
    {
        $this->builder[ $from ] = [ 'opt' => 'rename', 'to' => $to ];

        return $this;
    }

    /**
     * Enregistre la modification du champ précédent.
     *
     * @return $this
     */
    public function modify()
    {
        $this->checkPreviousBuild('modify');
        $key = key($this->builder);

        $this->builder[ $key ][ 'opt' ] = 'modify';

        return $this;
    }

    /**
     * Retourne le champs courant.
     * Déclenche une exception si le champ courant n'existe pas ou
     * si le champ courant est une opération.
     *
     * @param string $opt Nom de l'opération réalisé.
     *
     * @throws ColumnsNotFoundException
     * @return array                    Paramètres du champ.
     */
    protected function checkPreviousBuild($opt)
    {
        $str     = 'No column selected for ' . $opt . '.';
        if (!($current = end($this->builder))) {
            throw new ColumnsNotFoundException($str);
        }
        if (isset($current[ 'opt' ])) {
            throw new ColumnsNotFoundException($str);
        }

        return $current;
    }
}
