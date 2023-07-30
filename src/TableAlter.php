<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

use Soosyze\Queryflatfile\Command\DropCommand;
use Soosyze\Queryflatfile\Command\RenameCommand;

/**
 * Pattern fluent pour la création et configuration des types de données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class TableAlter extends TableBuilder
{
    /**
     * Enregistre la suppression d'une colonne.
     *
     * @param string $name Nom de la colonne.
     */
    public function dropColumn(string $name): void
    {
        $this->table->addCommand(new DropCommand($name));
    }

    /**
     * Enregistre le renommage d'une colonne.
     *
     * @param string $from Nom de la colonne.
     * @param string $to   Nouveau nom de la colonne.
     */
    public function renameColumn(string $from, string $to): void
    {
        $this->table->addCommand(new RenameCommand($from, $to));
    }
}
