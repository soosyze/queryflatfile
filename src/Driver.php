<?php

/**
 * Class Driver | src/Driver.php
 * 
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * 
 */

namespace Queryflatfile;

/**
 * Implementation partiel Queryflatfile\DriverInterface
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
abstract class Driver implements DriverInterface
{

    /**
     * {@inheritDoc}
     */
    abstract function create( $path, $fileName, array $data = [] );

    /**
     * {@inheritDoc}
     */
    abstract function read( $path, $fileName );

    /**
     * {@inheritDoc}
     */
    abstract function save( $path, $fileName, array $data );

    /**
     * {@inheritDoc}
     */
    public function delete( $path, $fileName )
    {
        return unlink($this->getFile($path, $fileName));
    }

    /**
     * {@inheritDoc}
     */
    public function has( $path, $fileName )
    {
        return file_exists($this->getFile($path, $fileName));
    }

    /**
     * {@inheritDoc}
     */
    abstract function getExtension();

    /**
     * Concatène le chemin, le nom du fichier et l'extension.
     * 
     * @param string $path chemin de la table
     * @param string $fileName nom du fichier
     * 
     * @return string le chemin complet du fichier
     */
    public function getFile( $path, $fileName )
    {
        $DS   = DIRECTORY_SEPARATOR;
        $file = $path . $DS . $fileName . '.' . $this->getExtension();
        return str_replace('\\', $DS, $file);
    }

    /**
     * Déclenche une exception si le fichier passé en paramètre d'existe pas.
     * 
     * @param string $file le chemin complet du fichier
     * 
     * @throws Exception\Driver\FileNotFoundException
     */
    protected function isExist( $file )
    {
        if( !file_exists($file) )
        {
            throw new Exception\Driver\FileNotFoundException('The ' . htmlspecialchars($file) . ' file is missing.');
        }
    }

    /**
     * Déclenche une exception si le fichier passé en paramètre n'a pas le droit d'écriture.
     * 
     * @param string $file le chemin complet du fichier
     * 
     * @throws Exception\Driver\FileNotWritableException
     */
    protected function isWrite( $file )
    {
        if( !is_writable($file) )
        {
            throw new Exception\Driver\FileNotWritableException('The ' . htmlspecialchars($file) . ' file is not writable.');
        }
    }

    /**
     * Déclenche une exception si le fichier passé en paramètre n'a pas le droit d'être lu.
     * 
     * @param string $file le chemin complet du fichier
     * 
     * @throws Exception\Driver\FileNotReadableException
     */
    protected function isRead( $file )
    {
        if( !is_readable($file) )
        {
            throw new Exception\Driver\FileNotReadableException('The ' . htmlspecialchars($file) . ' file is not readable.');
        }
    }
}