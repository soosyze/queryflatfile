<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

/**
 * Ensemble des fonctions nécessaires à une requête.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
interface RequestInterface
{
    /**
     * La valeur par défaut de LIMIT.
     */
    const ALL = 0;

    /**
     * La valeur pour une union simple.
     */
    const UNION_SIMPLE = 'simple';

    /**
     * La valeur pour une union totale.
     */
    const UNION_ALL = 'all';

    /**
     * Valeur pour un join gauche.
     */
    const JOIN_LEFT = 'left';

    /**
     * Valeur pour un join droit.
     */
    const JOIN_RIGHT = 'right';

    /**
     * Enregistre les champs sélectionnées par la requête.
     * En cas d'absence de selection, la requêtes retournera toutes les champs.
     *
     * @param string[] $columns Liste ou tableau des noms des colonnes.
     *
     * @return $this
     */
    public function select();

    /**
     * Enregistre le nom de la source des données principale de la requête.
     *
     * @param string $from Nom de la table.
     *
     * @return $this
     */
    public function from($from);

    /**
     * Enregistre une jointure gauche.
     *
     * @param string          $table    Nom de la table à joindre.
     * @param string|\Closure $column   Nom de la colonne d'une des tables précédentes
     *                                  ou une closure pour affiner les conditions.
     * @param string|null     $operator Opérateur logique ou null pour une closure.
     * @param string|null     $value    Valeur
     *                                  ou une colonne de la table jointe (au format nom_table.colonne)
     *                                  ou null pour une closure.
     *
     * @return $this
     */
    public function leftJoin($table, $column, $operator = null, $value = null);

    /**
     * Enregistre une jointure droite.
     *
     * @param string          $table    Nom de la table à joindre
     * @param string|\Closure $column   Nom de la colonne d'une des tables précédentes
     *                                  ou une closure pour affiner les conditions.
     * @param string|null     $operator Opérateur logique ou null pour une closure.
     * @param string|null     $value    Valeur
     *                                  ou une colonne de la table jointe (au format nom_table.colonne)
     *                                  ou null pour une closure.
     *
     * @return $this
     */
    public function rightJoin($table, $column, $operator = null, $value = null);

    /**
     * Enregistre une limitation et un décalage au retour de la requête.
     *
     * @param int $limit  Nombre de résultat maximum à retourner.
     * @param int $offset Décalage sur le jeu de résultat.
     *
     * @return $this
     */
    public function limit($limit, $offset = 0);

    /**
     * Enregistre un trie des résultats de la requête.
     *
     * @param string $columns Colonnes à trier.
     * @param int    $order   Ordre du trie (SORT_ASC|SORT_DESC).
     *
     * @return $this
     */
    public function orderBy($columns, $order = SORT_ASC);

    /**
     * Enregistre l'action d'insertion de données.
     * Cette fonction doit-être suivie la fonction values().
     *
     * @param string $table   Nom de la table.
     * @param array  $columns Liste des champs par ordre d'insertion dans
     *                        la fonction values().
     *
     * @return $this
     */
    public function insertInto($table, array $columns);

    /**
     * Cette fonction doit suivre la fonction insertInto().
     * Les valeurs doivent suivre le même ordre que les clés précédemment enregistrées.
     *
     * @param array $columns Valeurs des champs.
     *
     * @return $this
     */
    public function values(array $columns);

    /**
     * Enregistre l'action de modification de données.
     *
     * @param string $table   Nom de la table.
     * @param array  $columns key=>value des données à modifier.
     *
     * @return $this
     */
    public function update($table, array $columns);

    /**
     * Enregistre l'action de suppression des données.
     *
     * @return $this
     */
    public function delete();

    /**
     * Enregistre une union 'simple' entre 2 ensembles.
     * Le résultat de l'union ne possède pas de doublon de ligne.
     *
     * @param \Queryflatfile\Request $request Seconde requête.
     * @param string                 $type    (simple|all) Type d'union.
     *
     * @return $this
     */
    public function union(RequestInterface $request, $type = self::UNION_SIMPLE);

    /**
     * Enregistre une union all entre 2 ensembles.
     * Les doublons de lignes figure dans le resultat de l'union.
     *
     * @param \Queryflatfile\Request $request
     *
     * @return $this
     */
    public function unionAll(RequestInterface $request);

    /**
     * Retourne tous les résultats de la requête.
     *
     * @return array les données
     */
    public function fetchAll();

    /**
     * Retourne le premier résultat de la requête.
     *
     * @return array Résultat de la requête.
     */
    public function fetch();

    /**
     * Retourne les résultats de la requête sous forme de tableau simple,
     * composé uniquement du champ passé en paramètre ou du premier champ sélectionné.
     *
     * @param string      $name Nom du champ.
     * @param string|null $key  Clé des valeurs de la liste
     *
     * @throws ColumnsNotFoundException
     * @return array                    Liste du champ passé en paramètre.
     */
    public function lists($name, $key = null);

    /**
     * Lance l'exécution d'une requête de création, modification ou suppression.
     *
     * @throws BadFunctionException
     * @return void
     */
    public function execute();
}
