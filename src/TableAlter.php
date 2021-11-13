<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\Exception\TableBuilder\ColumnsNotFoundException;

/**
 * Pattern fluent pour la création et configuration des types de données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class TableAlter extends TableBuilder
{
    public const OPT_DROP = 'drop';

    public const OPT_MODIFY = 'modify';

    public const OPT_RENAME = 'rename';

    /**
     * Enregistre la suppression d'une colonne.
     *
     * @param string $name Nom de la colonne.
     *
     * @return $this
     */
    public function dropColumn(string $name): self
    {
        $this->builder[ $name ][ 'opt' ] = self::OPT_DROP;

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
    public function renameColumn(string $from, string $to): self
    {
        $this->builder[ $from ] = [ 'opt' => self::OPT_RENAME, 'to' => $to ];

        return $this;
    }

    /**
     * Enregistre la modification du champ précédent.
     *
     * @return $this
     */
    public function modify(): self
    {
        $this->checkPreviousBuild('modify');
        $key = key($this->builder);

        $this->builder[ $key ][ 'opt' ] = self::OPT_MODIFY;

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
     *
     * @return array Paramètres du champ.
     */
    protected function checkPreviousBuild(string $opt): array
    {
        $current = parent::checkPreviousBuild($opt);
        if (isset($current[ 'opt' ])) {
            throw new ColumnsNotFoundException(
                sprintf('No column selected for %s.', $opt)
            );
        }

        return $current;
    }
}
