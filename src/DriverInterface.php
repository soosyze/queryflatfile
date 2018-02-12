<?php

/**
 * Class DriverInterface | src/DriverInterface.php
 * 
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * 
 */

namespace Queryflatfile;

/**
 * Interface de manipulation de fichier de données.
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
interface DriverInterface
{

    /**
     * Créer un fichier si celui-ci n'existe pas et enregistre des données.
     * (Les données DOIVENT conserver leur type)
     * 
     * @param string $path le chemin du fichier
     * @param string $fileName le nom du fichier SANS l'extension
     * @param array $data les données sous forme de (clés=>valeur) à enregistrer
     * 
     * @throws Exception\Driver\ExtensionNotLoadedException si l'extension n'est pas chargée
     * 
     * @return boolean TRUE si tous ce passe bien sinon FALSE
     */
    public function create( $path, $fileName, array $data = [] );

    /**
     * Lit un fichier et DOIT retourner son contenu sous forme de tableau associatif 
     * quelle que soit sa profondeur. (Les données DOIVENT conserver leur type)
     * 
     * @param string $path le chemin du fichier
     * @param string $fileName le nom du fichier SANS l'extension
     * 
     * @throws Exception\Driver\ExtensionNotLoadedException si l'extension n'est pas chargée
     * @throws Exception\Driver\FileNotFoundException si le fichier est introuvable
     * @throws Exception\Driver\FileNotReadableException si le fichier n'a pas les droits suffisant pour être lu
     * 
     * @return array les données du fichier
     */
    public function read( $path, $fileName );

    /**
     * Enregistre des données dans le fichier.
     * (Les données DOIVENT conserver leur type)
     * 
     * @param string $path le chemin du fichier
     * @param string $fileName le nom du fichier SANS l'extension
     * @param array $data sous forme de (clés=>valeur) à enregistrer
     * 
     * @throws Exception\Driver\ExtensionNotLoadedException si l'extension n'est pas chargée
     * @throws Exception\Driver\FileNotFoundException si le fichier est introuvable
     * @throws Exception\Driver\FileNotWritableException si le fichier n'a pas les droits suffisant pour être écrit
     * 
     * @return boolean TRUE si tous ce passe bien sinon FALSE
     */
    public function save( $path, $fileName, array $data );

    /**
     * Supprime un fichier.
     * 
     * @param string $path le chemin du fichier
     * @param string $fileName le nom du fichier SANS l'extension
     * 
     * @return boolean TRUE si tous ce passe bien sinon FALSE
     */
    public function delete( $path, $fileName );

    /**
     * Si le fichier existe.
     * 
     * @param string $path le chemin du fichier
     * @param string $fileName le nom du fichier SANS l'extension
     * 
     * @return boolean
     */
    public function has( $path, $fileName );

    /**
     * Renseigne le nom de l'extension de fichier utilisé par le driver
     * au reste des composant.
     * 
     * @return string le nom de l'extension SANS le point en préfix.
     */
    public function getExtension();
}