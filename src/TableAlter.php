<?php

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
    /**
     * Enregistre la suppression d'une colonne.
     *
     * @param string $name Nom de la colonne.
     *
     * @return $this
     */
    public function dropColumn($name)
    {
        $this->builder[ $name ][ 'opt' ] = 'drop';

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
    public function renameColumn($from, $to)
    {
        $this->builder[ $from ] = [ 'opt' => 'rename', 'to' => $to ];

        return $this;
    }

    /**
     * Enregistre la modification du champ précédent.
     *
     * @return $this
     */
    public function modify()
    {
        $this->checkPreviousBuild('modify');
        $key = key($this->builder);

        $this->builder[ $key ][ 'opt' ] = 'modify';

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
    protected function checkPreviousBuild($opt)
    {
        $current = parent::checkPreviousBuild($opt);
        if (isset($current[ 'opt' ])) {
            throw new ColumnsNotFoundException("No column selected for $opt.");
        }

        return $current;
    }
}
