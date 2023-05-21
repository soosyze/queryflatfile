<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

use Soosyze\Queryflatfile\Field\IncrementType;

/**
 * @author Mathieu NOËL <mathieu@soosyze.com>
 *
 * @phpstan-import-type FieldToArray from Field
 *
 * @phpstan-type TableToArray array{
 *      fields: array<string, FieldToArray>,
 *      increments?: int|null
 * }
 */
final class Table
{
    /**
     * Les champs et leurs paramètres.
     *
     * @var array<string, Field>
     */
    protected array $fields = [];

    /**
     * La valeur des champs incrémentaux.
     */
    private ?int $increment = null;

    public function __construct(protected string $name)
    {
    }

    /**
     * Ajoute un nouveau champ.
     *
     * @param Field $field Champ.
     */
    public function addField(Field $field): void
    {
        if ($field instanceof IncrementType) {
            $this->increment = 0;
        }

        $this->fields[ $field->getName() ] = $field;
    }

    public function getField(string $name): Field
    {
        if (!isset($this->fields[ $name ])) {
            throw new \Exception();
        }

        return $this->fields[ $name ];
    }

    /**
     * Obtenez un champ par clé ou ajoutez-le s'il n'existe pas.
     */
    public function getFieldOrPut(Field $field): Field
    {
        if (isset($this->fields[$field->getName()])) {
            return $this->fields[$field->getName()];
        }

        $this->addField($field);

        return $this->fields[$field->getName()];
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getFieldsName(): array
    {
        return array_keys($this->fields);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIncrement(): ?int
    {
        return $this->increment;
    }

    public function hasField(string $name): bool
    {
        return isset($this->fields[ $name ]);
    }

    public function hasIncrement(): bool
    {
        return $this->increment !== null;
    }

    public function renameField(string $from, string $to): void
    {
        $this->fields[ $to ] = $this->fields[ $from ];
        unset($this->fields[ $from ]);
        $this->fields[ $to ]->setName($to);
    }

    public function setIncrement(?int $increment): void
    {
        $this->increment = $increment;
    }

    /**
     * Retourne les données de la table.
     *
     * @phpstan-return TableToArray
     */
    public function toArray(): array
    {
        $fields = [];
        foreach ($this->fields as $name => $field) {
            $fields[ $name ] = $field->toArray();
        }

        return [
            'fields'     => $fields,
            'increments' => $this->increment
        ];
    }

    public function unsetField(string $name): void
    {
        unset($this->fields[ $name ]);
    }
}
