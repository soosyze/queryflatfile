<?php

declare(strict_types=1);

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
    public const CURRENT_DATE_DEFAULT = 'current_date';

    public const CURRENT_DATETIME_DEFAULT = 'current_datetime';

    public const TYPE_BOOL = 'boolean';

    public const TYPE_CHAR = 'char';

    public const TYPE_DATE = 'date';

    public const TYPE_DATETIME = 'datetime';

    public const TYPE_FLOAT = 'float';

    public const TYPE_INCREMENT = 'increments';

    public const TYPE_INT = 'integer';

    public const TYPE_STRING = 'string';

    public const TYPE_TEXT = 'text';

    /**
     * Les champs et leurs paramètres.
     *
     * @var array
     */
    protected $builder = [];

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
    public function boolean(string $name): self
    {
        $this->builder[ $name ][ 'type' ] = self::TYPE_BOOL;

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
    public function char(string $name, int $length = 1): self
    {
        if ($length < 0) {
            throw new TableBuilderException('The length passed in parameter is not of numeric type.');
        }
        $this->builder[ $name ] = [ 'type' => self::TYPE_CHAR, 'length' => $length ];

        return $this;
    }

    /**
     * Enregistre un champ de type `date` sous le format Y-m-d.
     *
     * @param string $name Nom du champ.
     *
     * @return $this
     */
    public function date(string $name): self
    {
        $this->builder[ $name ][ 'type' ] = self::TYPE_DATE;

        return $this;
    }

    /**
     * Enregistre un champ de type `datetime`, sous le format Y-m-d H:i:s.
     *
     * @param string $name Nom du champ.
     *
     * @return $this
     */
    public function datetime(string $name): self
    {
        $this->builder[ $name ][ 'type' ] = self::TYPE_DATETIME;

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
    public function float(string $name): self
    {
        $this->builder[ $name ][ 'type' ] = self::TYPE_FLOAT;

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
    public function increments(string $name): self
    {
        if ($this->increment !== null) {
            throw new TableBuilderException('Only one incremental column is allowed per table.');
        }

        $this->builder[ $name ][ 'type' ] = self::TYPE_INCREMENT;
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
    public function integer(string $name): self
    {
        $this->builder[ $name ][ 'type' ] = self::TYPE_INT;

        return $this;
    }

    /**
     * Enregistre un champ de type `string` avec une limite de taille  par défaut de 255 caractères.
     * http://php.net/language.types.string
     *
     * @param string $name   Nom du champ.
     * @param int    $length Longueur maximum de la chaine.
     *
     * @throws TableBuilderException
     *
     * @return $this
     */
    public function string(string $name, int $length = 255): self
    {
        if ($length < 0) {
            throw new TableBuilderException('The length passed in parameter is not of numeric type.');
        }
        $this->builder[ $name ] = [ 'type' => self::TYPE_STRING, 'length' => $length ];

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
    public function text(string $name): self
    {
        $this->builder[ $name ][ 'type' ] = self::TYPE_TEXT;

        return $this;
    }

    /**
     * Enregistre un commentaire sur le dernier champ appelé.
     *
     * @param string $comment Commentaire du champ précédent.
     *
     * @return $this
     */
    public function comment(string $comment): self
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
    public function nullable(): self
    {
        $this->checkPreviousBuild('nullable');
        $this->builder[ key($this->builder) ][ 'nullable' ] = true;

        return $this;
    }

    /**
     * Enregistre le champ précédent (uniquement de type integer) comme étant non signié.
     *
     * @throws ColumnsValueException
     *
     * @return $this
     */
    public function unsigned(): self
    {
        $current = $this->checkPreviousBuild('unsigned');
        if ($current[ 'type' ] !== self::TYPE_INT) {
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
     * @throws TableBuilderException
     *
     * @return $this
     */
    public function valueDefault($value): self
    {
        $current = $this->checkPreviousBuild('value default');
        $type    = $current[ 'type' ];

        if ($type === self::TYPE_INCREMENT) {
            throw new TableBuilderException('An incremental type column can not have a default value.');
        }

        $name = (string) key($this->builder);

        $this->builder[ $name ][ 'default' ] = self::filterValue($name, $type, $value, $current);

        return $this;
    }

    /**
     * Retourne la valeur s'il correspond au type déclaré.
     * Sinon déclenche une exception.
     *
     * @param string $name  Nom du champ.
     * @param string $type  Type de donnée (string|text|int|float|bool|char|date|datetime).
     * @param mixed  $value Valeur à tester.
     * @param array  $args  Arguments de tests optionnels (length).
     *
     * @throws ColumnsValueException
     *
     * @return mixed
     */
    public static function filterValue(string $name, string $type, $value, array $args = [])
    {
        $error = 'The default value (' . $value . ') for column ' . $name . ' does not correspond to type ' . $type . '.';

        switch (strtolower($type)) {
            case self::TYPE_STRING:
            case self::TYPE_CHAR:
                if (!\is_string($value)) {
                    throw new ColumnsValueException($error);
                }
                if (!isset($args[ 'length' ]) || strlen($value) > $args[ 'length' ]) {
                    throw new ColumnsValueException('The default value is larger than the specified size.');
                }

                break;
            case self::TYPE_TEXT:
                if (!\is_string($value)) {
                    throw new ColumnsValueException($error);
                }

                break;
            case self::TYPE_INT:
            case self::TYPE_INCREMENT:
                if (!\is_int($value)) {
                    throw new ColumnsValueException($error);
                }

                return (int) $value;
            case self::TYPE_FLOAT:
                if (!\is_float($value)) {
                    throw new ColumnsValueException($error);
                }

                return (float) $value;
            case self::TYPE_BOOL:
                if (!\is_bool($value)) {
                    throw new ColumnsValueException($error);
                }

                break;
            case self::TYPE_DATE:
                if (strtolower($value) === self::CURRENT_DATE_DEFAULT) {
                    return self::CURRENT_DATE_DEFAULT;
                }
                if (($timestamp = strtotime($value))) {
                    return date('Y-m-d', $timestamp);
                }

                throw new ColumnsValueException($error);
            case self::TYPE_DATETIME:
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
     * Retourne le tableau contenant les configurations
     *
     * @return array Les configurations.
     */
    public function build(): array
    {
        return $this->builder;
    }

    /**
     * Retourne la liste des champs incrémentaux.
     *
     * @return int|null
     */
    public function getIncrement(): ?int
    {
        return $this->increment;
    }

    /**
     * Retour le schéma de la table.
     *
     * @return array
     */
    public function getTableSchema(): array
    {
        return [
            'fields'     => $this->builder,
            'increments' => $this->increment
        ];
    }

    /**
     * Retourne le champs courant.
     * Déclenche une exception si le champ courant n'existe pas ou
     * si le champ courant est une opération.
     *
     * @param string $opt Nom de l'opération réalisé.
     *
     * @throws ColumnsNotFoundException
     *
     * @return array Paramètres du champ.
     */
    protected function checkPreviousBuild(string $opt): array
    {
        if (!($current = end($this->builder))) {
            throw new ColumnsNotFoundException("No column selected for $opt.");
        }

        return $current;
    }
}
