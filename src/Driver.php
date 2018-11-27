<?php

/**
 * Queryflatfile
 *
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

/**
 * Implementation partiel Queryflatfile\DriverInterface.
 *
 * @author Mathieu NOËL
 */
abstract class Driver implements DriverInterface
{
    /**
     * {@inheritDoc}
     */
    abstract public function create($path, $fileName, array $data = []);

    /**
     * {@inheritDoc}
     */
    abstract public function read($path, $fileName);

    /**
     * {@inheritDoc}
     */
    abstract public function save($path, $fileName, array $data);

    /**
     * {@inheritDoc}
     */
    public function delete($path, $fileName)
    {
        return unlink($this->getFile($path, $fileName));
    }

    /**
     * {@inheritDoc}
     */
    public function has($path, $fileName)
    {
        return file_exists($this->getFile($path, $fileName));
    }

    /**
     * {@inheritDoc}
     */
    abstract public function getExtension();

    /**
     * Concatène le chemin, le nom du fichier et l'extension.
     *
     * @param string $path     Chemin de la table.
     * @param string $fileName Nom du fichier.
     *
     * @return string Chemin complet du fichier.
     */
    public function getFile($path, $fileName)
    {
        $DS   = DIRECTORY_SEPARATOR;
        $file = $path . $DS . $fileName . '.' . $this->getExtension();

        return str_replace('\\', $DS, $file);
    }

    /**
     * Déclenche une exception si le fichier passé en paramètre d'existe pas.
     *
     * @param string $file Chemin complet du fichier.
     *
     * @throws Exception\Driver\FileNotFoundException
     */
    protected function isExist($file)
    {
        if (!file_exists($file)) {
            throw new Exception\Driver\FileNotFoundException('The ' . htmlspecialchars($file) . ' file is missing.');
        }
    }

    /**
     * Déclenche une exception si le fichier passé en paramètre n'a pas le droit d'écriture.
     *
     * @codeCoverageIgnore has
     *
     * @param string $file Chemin complet du fichier.
     *
     * @throws Exception\Driver\FileNotWritableException
     */
    protected function isWrite($file)
    {
        if (!is_writable($file)) {
            throw new Exception\Driver\FileNotWritableException('The ' . htmlspecialchars($file) . ' file is not writable.');
        }
    }

    /**
     * Déclenche une exception si le fichier passé en paramètre n'a pas le droit d'être lu.
     *
     * @codeCoverageIgnore has
     *
     * @param string $file Chemin complet du fichier.
     *
     * @throws Exception\Driver\FileNotReadableException
     */
    protected function isRead($file)
    {
        if (!is_readable($file)) {
            throw new Exception\Driver\FileNotReadableException('The ' . htmlspecialchars($file) . ' file is not readable.');
        }
    }
}
