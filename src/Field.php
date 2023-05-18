<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

use Soosyze\Queryflatfile\Exception\TableBuilder\ColumnsValueException;
use Soosyze\Queryflatfile\Exception\TableBuilder\TableBuilderException;

/**
 * Pattern fluent pour la création et configuration des types de données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @phpstan-type FieldToArray array{
 *      _comment?: string,
 *      default?: null|scalar,
 *      length?: int,
 *      nullable?: bool,
 *      opt?: string,
 *      type: string,
 *      unsigned?: bool,
 * }
 */
abstract class Field
{
    public const OPT_CREATE = 'create';

    public const OPT_DROP = 'drop';

    public const OPT_MODIFY = 'modify';

    public const OPT_RENAME = 'rename';

    public const TYPE = '';

    protected const INVALID_ARGUMENT_MESSAGE = 'The value of the %s field must be of type %s: %s given.';

    /**
     * @var null|scalar
     */
    protected $valueDefault;

    protected string $name;

    protected string $opt = self::OPT_CREATE;

    protected ?string $comment = null;

    protected bool $isNullable = false;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Enregistre un commentaire.
     *
     *
     * @return $this
     */
    public function comment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Enregistre le champ comme acceptant la valeur NULL.
     *
     * @return $this
     */
    public function nullable(): self
    {
        $this->isNullable = true;

        return $this;
    }

    /**
     * Enregistre une valeur par défaut au champ précédent.
     * Lève une exception si la valeur par défaut ne correspond pas au type de valeur passée en paramètre.
     *
     * @param null|scalar $value Valeur à tester.
     *
     * @throws ColumnsValueException
     *
     * @return null|scalar
     */
    abstract public function filterValue($value);

    /**
     * Enregistre une valeur par défaut au champ précédent.
     * Lève une exception si la valeur par défaut ne correspond pas au type de valeur passée en paramètre.
     *
     * @param null|scalar $value Valeur à tester.
     *
     * @throws TableBuilderException
     *
     * @return $this
     */
    public function valueDefault($value)
    {
        $this->valueDefault = $this->filterValue($value);

        return $this;
    }

    /**
     * Retourne la valeur par defaut.
     *
     * @throws ColumnsValueException
     *
     * @return null|scalar Valeur par defaut.
     */
    public function getValueDefault()
    {
        if ($this->valueDefault !== null) {
            return $this->valueDefault;
        }
        if ($this->isNullable) {
            return null;
        }

        throw new ColumnsValueException(
            sprintf('%s not nullable or not default.', $this->name)
        );
    }

    /**
     * Enregistre la modification du champ précédent.
     */
    public function modify(): void
    {
        $this->opt = self::OPT_MODIFY;
    }

    /**
     * Retourne le nom de l'opération du champ.
     */
    public function getOpt(): string
    {
        return $this->opt;
    }

    /**
     * Retourne le nom du champ.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Retourne les données du champ.
     *
     *
     * @phpstan-return FieldToArray
     */
    public function toArray(): array
    {
        $data[ 'type' ] = static::TYPE;

        if ($this->isNullable) {
            $data[ 'nullable' ] = $this->isNullable;
        }
        if ($this->comment !== null) {
            $data[ '_comment' ] = $this->comment;
        }
        if ($this->valueDefault !== null) {
            $data[ 'default' ] = $this->valueDefault;
        }

        return $data;
    }
}
