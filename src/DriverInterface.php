<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

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
     * @throws Exception\Driver\ExtensionNotLoadedException Si l'extension n'est pas chargée.
     *
     * @return bool TRUE si tous ce passe bien sinon FALSE.
     */
    public function create($path, $fileName, array $data = []);

    /**
     * Lit un fichier et DOIT retourner son contenu sous forme de tableau associatif
     * quelle que soit sa profondeur. Les données DOIVENT conserver leur type.
     *
     * @param string $path     Chemin du fichier.
     * @param string $fileName Nom du fichier SANS l'extension.
     *
     * @throws Exception\Driver\ExtensionNotLoadedException Si l'extension n'est pas chargée.
     * @throws Exception\Driver\FileNotFoundException       Si le fichier est introuvable.
     * @throws Exception\Driver\FileNotReadableException    Si le fichier n'a pas les droits suffisant pour être lu.
     *
     * @return array les données du fichier
     */
    public function read($path, $fileName);

    /**
     * Enregistre des données dans le fichier.
     * Les données DOIVENT conserver leur type.
     *
     * @param string $path     Chemin du fichier.
     * @param string $fileName Nom du fichier SANS l'extension.
     * @param array  $data     Tableau associatif à enregistrer.
     *
     * @throws Exception\Driver\ExtensionNotLoadedException Si l'extension n'est pas chargée.
     * @throws Exception\Driver\FileNotFoundException       Si le fichier est introuvable.
     * @throws Exception\Driver\FileNotWritableException    Si le fichier n'a pas les droits suffisant pour être écrit.
     *
     * @return bool TRUE si tous ce passe bien sinon FALSE.
     */
    public function save($path, $fileName, array $data);

    /**
     * Supprime un fichier.
     *
     * @param string $path     Chemin du fichier.
     * @param string $fileName Nom du fichier SANS l'extension.
     *
     * @return bool TRUE si tous ce passe bien sinon FALSE.
     */
    public function delete($path, $fileName);

    /**
     * Si le fichier existe.
     *
     * @param string $path     Chemin du fichier.
     * @param string $fileName Nom du fichier SANS l'extension.
     *
     * @return bool
     */
    public function has($path, $fileName);

    /**
     * Renseigne le nom de l'extension de fichier utilisé par le driver
     * au reste des composant.
     *
     * @return string Nom de l'extension SANS le point en préfix.
     */
    public function getExtension();
}
