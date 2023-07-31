<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile;

/**
 * Interface de manipulation de fichier de données.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
interface DriverInterface
{
    /**
     * Créer un fichier si celui-ci n'existe pas et enregistre des données
     * Les données DOIVENT conserver leur type.
     *
     * @param string $path     Chemin du fichier.
     * @param string $fileName Nom du fichier SANS l'extension.
     * @param array  $data     Tableau associatif à enregistrer.
     *
     * @throws Exceptions\Driver\ExtensionNotLoadedException Si l'extension n'est pas chargée.
     *
     * @return bool TRUE si tous ce passe bien sinon FALSE.
     */
    public function create(string $path, string $fileName, array $data = []): bool;

    /**
     * Lit un fichier et DOIT retourner son contenu sous forme de tableau associatif
     * quelle que soit sa profondeur. Les données DOIVENT conserver leur type.
     *
     * @param string $path     Chemin du fichier.
     * @param string $fileName Nom du fichier SANS l'extension.
     *
     * @throws Exceptions\Driver\ExtensionNotLoadedException Si l'extension n'est pas chargée.
     * @throws Exceptions\Driver\FileNotFoundException       Si le fichier est introuvable.
     * @throws Exceptions\Driver\FileNotReadableException    Si le fichier n'a pas les droits suffisant pour être lu.
     *
     * @return array les données du fichier
     */
    public function read(string $path, string $fileName): array;

    /**
     * Enregistre des données dans le fichier.
     * Les données DOIVENT conserver leur type.
     *
     * @param string $path     Chemin du fichier.
     * @param string $fileName Nom du fichier SANS l'extension.
     * @param array  $data     Tableau associatif à enregistrer.
     *
     * @throws Exceptions\Driver\ExtensionNotLoadedException Si l'extension n'est pas chargée.
     * @throws Exceptions\Driver\FileNotFoundException       Si le fichier est introuvable.
     * @throws Exceptions\Driver\FileNotWritableException    Si le fichier n'a pas les droits suffisant pour être écrit.
     *
     * @return bool TRUE si tous ce passe bien sinon FALSE.
     */
    public function save(string $path, string $fileName, array $data): bool;

    /**
     * Supprime un fichier.
     *
     * @param string $path     Chemin du fichier.
     * @param string $fileName Nom du fichier SANS l'extension.
     *
     * @return bool TRUE si tous ce passe bien sinon FALSE.
     */
    public function delete(string $path, string $fileName): bool;

    /**
     * Si le fichier existe.
     *
     * @param string $path     Chemin du fichier.
     * @param string $fileName Nom du fichier SANS l'extension.
     */
    public function has(string $path, string $fileName): bool;

    /**
     * Renseigne le nom de l'extension de fichier utilisé par le driver
     * au reste des composant.
     *
     * @return string Nom de l'extension SANS le point en préfix.
     */
    public function getExtension(): string;
}
