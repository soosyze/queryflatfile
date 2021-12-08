<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\Exception\TableBuilder\TableBuilderException;
use Queryflatfile\Field\BoolType;
use Queryflatfile\Field\CharType;
use Queryflatfile\Field\DateTimeType;
use Queryflatfile\Field\DateType;
use Queryflatfile\Field\FloatType;
use Queryflatfile\Field\IncrementType;
use Queryflatfile\Field\IntType;
use Queryflatfile\Field\StringType;
use Queryflatfile\Field\TextType;

/**
 * Pattern fluent pour la création et configuration des types de données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @phpstan-import-type TableToArray from Table
 */
class TableBuilder
{
    /**
     * @var Table
     */
    protected $table;

    public function __construct(string $name)
    {
        $this->table = new Table($name);
    }

    /**
     * Enregistre un champ de type `boolean`, true ou false.
     * http://php.net/manual/fr/language.types.boolean.php
     *
     * @param string $name Nom du champ.
     *
     * @return Field
     */
    public function boolean(string $name): Field
    {
        $this->table->addField(new BoolType($name));

        return $this->table->getField($name);
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
     * @return Field
     */
    public function char(string $name, int $length = 1): Field
    {
        $this->table->addField(new CharType($name, $length));

        return $this->table->getField($name);
    }

    /**
     * Enregistre un champ de type `date` sous le format Y-m-d.
     *
     * @param string $name Nom du champ.
     *
     * @return Field
     */
    public function date(string $name): Field
    {
        $this->table->addField(new DateType($name));

        return $this->table->getField($name);
    }

    /**
     * Enregistre un champ de type `datetime`, sous le format Y-m-d H:i:s.
     *
     * @param string $name Nom du champ.
     *
     * @return Field
     */
    public function datetime(string $name): Field
    {
        $this->table->addField(new DateTimeType($name));

        return $this->table->getField($name);
    }

    /**
     * Enregistre un champ de type `float`, nombre à virgule flottant.
     * http://php.net/manual/fr/language.types.float.php
     * Valeur d'insertion autorisé : 1, '1', 1.0, '1.0' (enregistrera 1.0).
     *
     * @param string $name Nom du champ.
     *
     * @return Field
     */
    public function float(string $name): Field
    {
        $this->table->addField(new FloatType($name));

        return $this->table->getField($name);
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
     * @return Field
     */
    public function increments(string $name): Field
    {
        if ($this->table->getIncrement() !== null) {
            throw new TableBuilderException('Only one incremental column is allowed per table.');
        }

        $this->table->addField(new IncrementType($name));

        return $this->table->getField($name);
    }

    /**
     * Enregistre un champ de type `integer`, entier signié.
     * http://php.net/manual/fr/language.types.integer.php
     * Valeur d'insertion autorisé : 1, '1', 1.1, '1.1' (enregistrera 1)
     *
     * @param string $name Nom du champ.
     *
     * @return IntType
     */
    public function integer(string $name): IntType
    {
        $this->table->addField(new IntType($name));
        /** @var IntType */
        return $this->table->getField($name);
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
     * @return Field
     */
    public function string(string $name, int $length = 255): Field
    {
        $this->table->addField(new StringType($name, $length));

        return $this->table->getField($name);
    }

    /**
     * Enregistre un champ de type `text` sans limite de taille.
     * http://php.net/language.types.string
     *
     * @param string $name Nom du champ.
     *
     * @return Field
     */
    public function text(string $name): Field
    {
        $this->table->addField(new TextType($name));

        return $this->table->getField($name);
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * Créer une table à partir d'un tableau de données.
     *
     * @param string $table Nom de la table.
     * @param array  $data  Donnaées pour créer une table.
     *
     * @phpstan-param TableToArray $data
     *
     * @throws TableBuilderException
     *
     * @return Table
     */
    public static function createTableFromArray(string $table, array $data): Table
    {
        $tableBuilder = new self($table);
        foreach ($data[ 'fields' ] as $name => $value) {
            switch ($value[ 'type' ]) {
                case BoolType::TYPE:
                case DateType::TYPE:
                case DateTimeType::TYPE:
                case FloatType::TYPE:
                case IncrementType::TYPE:
                case TextType::TYPE:
                    $field = $tableBuilder->{$value[ 'type' ]}($name);

                    break;
                case CharType::TYPE:
                case StringType::TYPE:
                    $field = $tableBuilder->{$value[ 'type' ]}($name, $value[ 'length' ] ?? 0);

                    break;
                case IntType::TYPE:
                    $field = $tableBuilder->{$value[ 'type' ]}($name);

                    if (isset($value[ 'unsigned' ])) {
                        $field->unsigned();
                    }

                    break;
                default:
                    throw new TableBuilderException(sprintf('Type %s not supported.', $value[ 'type' ]));
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
        }
        $tableBuilder->table->setIncrement($data[ 'increments' ] ?? null);

        return $tableBuilder->table;
    }
}
