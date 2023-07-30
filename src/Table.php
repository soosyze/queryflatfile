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
 * @phpstan-type TableToArray array{
 *      fields: array<string, array>,
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
     * Les champs et leurs paramètres.
     *
     * @var array<string, Command>
     */
    private array $commands = [];

    /**
     * La valeur du champ incrémentaux.
     */
    private ?int $increment = null;

    public function __construct(public readonly string $name)
    {
    }

    public function addCommand(Command $command): void
    {
        $this->commands[$command->name] = $command;
    }

    /**
     * @return array<string, Command>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Ajoute un nouveau champ.
     */
    public function addField(Field $field): void
    {
        if ($field instanceof IncrementType) {
            $this->increment = is_int($this->increment)
                ? throw new \InvalidArgumentException('Only one incremental column is allowed per table.')
                : 0;
        }

        $this->fields[$field->name] = $field;
    }

    public function getField(string $name): Field
    {
        return $this->fields[$name]
            ?? throw new \InvalidArgumentException('Field does not exist');
    }

    /**
     * Obtenez un champ par clé ou ajoutez-le s'il n'existe pas.
     */
    public function getFieldOrPut(Field $field): Field
    {
        if (isset($this->fields[$field->name])) {
            return $this->fields[$field->name];
        }

        $this->addField($field);

        return $this->fields[$field->name];
    }

    /**
     * @return array<string, Field>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return string[]
     */
    public function getFieldsName(): array
    {
        return array_keys($this->fields);
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
        $this->fields[ $to ] = $this->getField($from);
        unset($this->fields[ $from ]);
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
        $table['fields'] = [];
        foreach ($this->fields as $name => $field) {
            $table['fields'][$name] = $field->toArray();
        }
        if ($this->increment !== null) {
            $table['increments'] = $this->increment;
        }

        return $table;
    }

    public function unsetField(string $name): void
    {
        unset($this->fields[ $name ]);
    }
}
