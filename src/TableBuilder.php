<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

use Soosyze\Queryflatfile\Enums\FieldType;
use Soosyze\Queryflatfile\Exception\TableBuilder\TableBuilderException;
use Soosyze\Queryflatfile\Fields\BoolType;
use Soosyze\Queryflatfile\Fields\CharType;
use Soosyze\Queryflatfile\Fields\DateTimeType;
use Soosyze\Queryflatfile\Fields\DateType;
use Soosyze\Queryflatfile\Fields\FloatType;
use Soosyze\Queryflatfile\Fields\IncrementType;
use Soosyze\Queryflatfile\Fields\IntType;
use Soosyze\Queryflatfile\Fields\StringType;
use Soosyze\Queryflatfile\Fields\TextType;

/**
 * Pattern fluent pour la création et configuration des types de données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @phpstan-import-type TableToArray from Table
 */
class TableBuilder
{
    protected Table $table;

    public function __construct(string $name)
    {
        $this->table = new Table($name);
    }

    /**
     * Enregistre un champ de type `boolean`, true ou false.
     * http://php.net/manual/fr/language.types.boolean.php
     *
     * @param string $name Nom du champ.
     */
    public function boolean(string $name): Field
    {
        return $this->table->getFieldOrPut(new BoolType($name));
    }

    /**
     * Enregistre un champ de type `char` avec une limite de taille par défaut de un caractère.
     * http://php.net/language.types.string
     *
     * @param string $name Nom du champ
     *
     * @throws TableBuilderException
     */
    public function char(string $name): Field
    {
        return $this->table->getFieldOrPut(new CharType($name));
    }

    /**
     * Enregistre un champ de type `date` sous le format Y-m-d.
     *
     * @param string $name Nom du champ.
     */
    public function date(string $name): Field
    {
        return $this->table->getFieldOrPut(new DateType($name));
    }

    /**
     * Enregistre un champ de type `datetime`, sous le format Y-m-d H:i:s.
     *
     * @param string $name Nom du champ.
     */
    public function datetime(string $name): Field
    {
        return $this->table->getFieldOrPut(new DateTimeType($name));
    }

    /**
     * Enregistre un champ de type `float`, nombre à virgule flottant.
     * http://php.net/manual/fr/language.types.float.php
     * Valeur d'insertion autorisé : 1, '1', 1.0, '1.0' (enregistrera 1.0).
     *
     * @param string $name Nom du champ.
     */
    public function float(string $name): Field
    {
        return $this->table->getFieldOrPut(new FloatType($name));
    }

    /**
     * Enregistre un champ de type `increments`, entier positif qui s'incrémente automatiquement.
     * Un seul champ increments est autorisé par table.
     * http://php.net/manual/fr/language.types.integer.php
     *
     * @param string $name nom du champ
     */
    public function increments(string $name): Field
    {
        return $this->table->getFieldOrPut(new IncrementType($name));
    }

    /**
     * Enregistre un champ de type `integer`, entier signié.
     * http://php.net/manual/fr/language.types.integer.php
     * Valeur d'insertion autorisé : 1, '1', 1.1, '1.1' (enregistrera 1)
     *
     * @param string $name Nom du champ.
     */
    public function integer(string $name): IntType
    {
        $field = $this->table->getFieldOrPut(new IntType($name));

        if (!$field instanceof IntType) {
            throw new \Exception('Type is invalid');
        }

        return $field;
    }

    /**
     * Enregistre un champ de type `string` avec une limite de taille  par défaut de 255 caractères.
     * http://php.net/language.types.string
     *
     * @param string $name   Nom du champ.
     * @param int    $length Longueur maximum de la chaine.
     *
     * @throws TableBuilderException
     */
    public function string(string $name, int $length = 255): Field
    {
        return $this->table->getFieldOrPut(new StringType($name, $length));
    }

    /**
     * Enregistre un champ de type `text` sans limite de taille.
     * http://php.net/language.types.string
     *
     * @param string $name Nom du champ.
     */
    public function text(string $name): Field
    {
        return $this->table->getFieldOrPut(new TextType($name));
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * Créer une table à partir d'un tableau de données.
     *
     * @param string $table Nom de la table.
     * @param array  $data  Données pour créer une table.
     *
     * @phpstan-param TableToArray $data
     *
     * @throws TableBuilderException
     */
    public static function createTableFromArray(string $table, array $data): Table
    {
        $tableBuilder = new self($table);
        foreach ($data['fields'] as $name => $value) {
            $field = match (FieldType::tryFrom($value['type'])) {
                FieldType::Boolean => new BoolType($name),
                FieldType::Char => new CharType($name),
                FieldType::Date => new DateType($name),
                FieldType::DateTime => new DateTimeType($name),
                FieldType::Float => new FloatType($name),
                FieldType::Increment => new IncrementType($name),
                FieldType::Int => new IntType($name),
                FieldType::String => new StringType($name, $value['length'] ?? 255),
                FieldType::Text => new TextType($name),
                default => throw new TableBuilderException("Type {$value['type']} not supported.")
            };

            if (isset($value['unsigned']) && $field instanceof IntType) {
                $field->unsigned();
            }
            if (isset($value[ 'nullable' ])) {
                $field->nullable();
            }
            if (isset($value[ 'default' ])) {
                $field->valueDefault($value[ 'default' ]);
            }
            if (isset($value[ '_comment' ])) {
                $field->comment($value[ '_comment' ]);
            }
            $tableBuilder->table->addField($field);
        }
        $tableBuilder->table->setIncrement($data[ 'increments' ] ?? null);

        return $tableBuilder->table;
    }
}
