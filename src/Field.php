<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

use Soosyze\Queryflatfile\Enums\FieldType;
use Soosyze\Queryflatfile\Enums\TableExecutionType;
use Soosyze\Queryflatfile\Exceptions\TableBuilder\ColumnsValueException;
use Soosyze\Queryflatfile\Exceptions\TableBuilder\TableBuilderException;

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
 *      type: string,
 *      unsigned?: bool,
 * }
 */
abstract class Field
{
    protected const INVALID_ARGUMENT_MESSAGE = 'The value of the %s field must be of type %s: %s given.';

    protected bool|float|int|string|null $valueDefault = null;

    protected TableExecutionType $opt = TableExecutionType::Create;

    protected ?string $comment = null;

    protected bool $isNullable = false;

    public function __construct(readonly public string $name)
    {
    }

    /**
     * Enregistre un commentaire.
     */
    public function comment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Enregistre le champ comme acceptant la valeur NULL.
     */
    public function nullable(): self
    {
        $this->isNullable = true;

        return $this;
    }

    /**
     * Associe une valeur par défaut au champ.
     *
     * @throws \InvalidArgumentException
     */
    public function valueDefault(mixed $value): self
    {
        $this->valueDefault = $this->tryOrGetValue($value);

        return $this;
    }

    /**
     * Retourne la valeur par defaut.
     *
     * @throws ColumnsValueException
     */
    public function getValueDefault(): bool|float|int|string|null
    {
        if ($this->valueDefault !== null) {
            return $this->valueDefault;
        }

        return $this->isNullable
            ? null
            : throw new ColumnsValueException(
                sprintf('%s not nullable or not default.', $this->name)
            );
    }

    /**
     * Enregistre la modification du champ précédent.
     */
    public function modify(): void
    {
        $this->opt = TableExecutionType::Modify;
    }

    /**
     * Retourne le nom de l'opération du champ.
     */
    public function getExecutionType(): TableExecutionType
    {
        return $this->opt;
    }

    /**
     * Retourne les données du champ.
     *
     * @phpstan-return FieldToArray
     */
    public function toArray(): array
    {
        $data['type'] = $this->getType()->value;

        if ($this->isNullable) {
            $data['nullable'] = $this->isNullable;
        }
        if ($this->comment !== null) {
            $data['_comment'] = $this->comment;
        }
        if ($this->valueDefault !== null) {
            $data['default'] = $this->valueDefault;
        }

        return $data;
    }

    /**
     * Retourne le type du champ.
     */
    abstract public function getType(): FieldType;

    /**
     * Test si la valeur passé en paramètre correspond au type du champ.
     *
     * @throws \InvalidArgumentException
     */
    abstract public function tryOrGetValue(mixed $value): bool|float|int|string|null;
}
