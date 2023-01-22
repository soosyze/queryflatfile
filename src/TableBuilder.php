<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

use Soosyze\Queryflatfile\Exception\TableBuilder\TableBuilderException;
use Soosyze\Queryflatfile\Field\BoolType;
use Soosyze\Queryflatfile\Field\CharType;
use Soosyze\Queryflatfile\Field\DateTimeType;
use Soosyze\Queryflatfile\Field\DateType;
use Soosyze\Queryflatfile\Field\FloatType;
use Soosyze\Queryflatfile\Field\IncrementType;
use Soosyze\Queryflatfile\Field\IntType;
use Soosyze\Queryflatfile\Field\StringType;
use Soosyze\Queryflatfile\Field\TextType;

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
     */
    public function boolean(string $name): Field
    {
        return $this->table->getFieldOrPut(new BoolType($name));
    }

    /**
     * Enregistre un champ de type `char` avec une limite de taille par défaut de un caractère.
     * http://php.net/language.types.string
     *
     * @param string $name   Nom du champ
     * @param int    $length longueur maximum de la chaine.
     *
     * @throws TableBuilderException
     */
    public function char(string $name, int $length = 1): Field
    {
        return $this->table->getFieldOrPut(new CharType($name, $length));
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
     *
     * @throws TableBuilderException
     */
    public function increments(string $name): Field
    {
        if ($this->table->getIncrement() !== null) {
            throw new TableBuilderException('Only one incremental column is allowed per table.');
        }

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
     * @param array  $data  Donnaées pour créer une table.
     *
     * @phpstan-param TableToArray $data
     *
     * @throws TableBuilderException
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
