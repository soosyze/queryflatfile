<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

use Soosyze\Queryflatfile\Exception\Query\BadFunctionException;
use Soosyze\Queryflatfile\Exception\TableBuilder\ColumnsNotFoundException;

/**
 * Ensemble des fonctions nécessaires à une requête.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>

 * @phpstan-import-type RowValues from Schema
 * @phpstan-import-type RowData from Schema
 * @phpstan-import-type TableData from Schema
 */
interface RequestInterface
{
    /**
     * La valeur par défaut de LIMIT.
     */
    public const ALL = 0;

    /**
     * Valeur pour un join gauche.
     */
    public const JOIN_LEFT = 'left';

    /**
     * Valeur pour un join droit.
     */
    public const JOIN_RIGHT = 'right';

    public function __toString(): string;

    /**
     * Enregistre les champs sélectionnées par la requête.
     * En cas d'absence de selection, la requêtes retournera toutes les champs.
     *
     * @param string ...$columnNames Liste ou tableau des noms des colonnes.
     */
    public function select(string ...$columnNames): self;

    /**
     * @return string[]
     */
    public function getColumnNames(): array;

    /**
     * Enregistre le nom de la source des données principale de la requête.
     *
     * @param string $tableName Nom de la table.
     */
    public function from(string $tableName): self;

    /**
     * Enregistre une jointure gauche.
     *
     * @param string          $tableName Nom de la table à joindre.
     * @param string|\Closure $column    Nom de la colonne d'une des tables précédentes ou un group de condition
     *                                   ou une closure pour affiner les conditions.
     * @param string          $operator  Opérateur logique ou null pour une closure.
     * @param string          $value     Colonne de la table jointe (au format nom_table.colonne)
     */
    public function leftJoin(string $tableName, string|\Closure $column, string $operator = '', string $value = ''): self;

    /**
     * Enregistre une jointure droite.
     *
     * @param string          $tableName Nom de la table à joindre
     * @param string|\Closure $column    Nom de la colonne d'une des tables précédentes ou un group de condition
     *                                   ou une closure pour affiner les conditions.
     * @param string          $operator  Opérateur logique ou null pour une closure.
     * @param string          $value     Colonne de la table jointe (au format nom_table.colonne)
     */
    public function rightJoin(string $tableName, string|\Closure $column, string $operator = '', string $value = ''): self;

    /**
     * Enregistre une limitation et un décalage au retour de la requête.
     *
     * @param int $limit  Nombre de résultat maximum à retourner.
     * @param int $offset Décalage sur le jeu de résultat.
     */
    public function limit(int $limit, int $offset = 0): self;

    /**
     * Enregistre un trie des résultats de la requête.
     *
     * @param string $columnName Nom de la colonne à trier.
     * @param int    $order      Ordre du trie (SORT_ASC|SORT_DESC).
     */
    public function orderBy(string $columnName, int $order = SORT_ASC): self;

    /**
     * Enregistre l'action d'insertion de données.
     * Cette fonction doit-être suivie la fonction values().
     *
     * @param string   $tableName   Nom de la table.
     * @param string[] $columnNames Liste des champs par ordre d'insertion dans
     *                              la fonction values().
     */
    public function insertInto(string $tableName, array $columnNames): self;

    /**
     * Cette fonction doit suivre la fonction insertInto().
     * Les valeurs doivent suivre le même ordre que les clés précédemment enregistrées.
     *
     * @param array $rowValues Valeurs des champs.
     *
     * @phpstan-param RowValues $rowValues
     */
    public function values(array $rowValues): self;

    /**
     * Enregistre l'action de modification de données.
     *
     * @param string $tableName Nom de la table.
     * @param array  $row       key=>value des données à modifier.
     *
     * @phpstan-param RowData $row
     */
    public function update(string $tableName, array $row): self;

    /**
     * Enregistre l'action de suppression des données.
     */
    public function delete(): self;

    /**
     * Enregistre une union 'simple' entre 2 ensembles.
     * Le résultat de l'union ne possède pas de doublon de ligne.
     *
     * @param RequestInterface $request Seconde requête.
     */
    public function union(RequestInterface $request): self;

    /**
     * Enregistre une union all entre 2 ensembles.
     * Les doublons de lignes figure dans le resultat de l'union.
     */
    public function unionAll(RequestInterface $request): self;

    /**
     * Retourne tous les résultats de la requête.
     *
     * @return array les données
     *
     * @phpstan-return TableData
     */
    public function fetchAll(): array;

    /**
     * Retourne le premier résultat de la requête.
     *
     * @return array Résultat de la requête.
     *
     * @phpstan-return ?RowData
     */
    public function fetch(): ?array;

    /**
     * Retourne les résultats de la requête sous forme de tableau simple,
     * composé uniquement du champ passé en paramètre ou du premier champ sélectionné.
     *
     * @param string      $name Nom du champ.
     * @param string|null $key  Clé des valeurs de la liste
     *
     * @throws ColumnsNotFoundException
     *
     * @return array<null|scalar> Liste du champ passé en paramètre.
     */
    public function lists(string $name, ?string $key = null): array;

    /**
     * Lance l'exécution d'une requête de création, modification ou suppression.
     *
     * @throws BadFunctionException
     */
    public function execute(): void;
}
