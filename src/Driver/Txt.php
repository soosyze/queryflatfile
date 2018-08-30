<?php

/**
 * Queryflatfile
 *
 * @package Queryflatfile
 * @author  Mathieu NOËL <mathieu@soosyze.com>
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile\Driver;

/**
 * Implémentation de Queryflatfile\DriverInterface par l'héritage de Queryflatfile\Driver
 * Manipule des données sérialisées dans des fichiers texte.
 *
 * @author  Mathieu NOËL
 */
class Txt extends \Queryflatfile\Driver
{

    /**
     * {@inheritDoc}
     */
    public function create($path, $fileName, array $data = [])
    {
        $file = $this->getFile($path, $fileName);

        if (!file_exists($path)) {
            mkdir($path, 0775);
        }
        if (!file_exists($file)) {
            $fichier = fopen($file, 'w+');
            fwrite($fichier, serialize($data));

            return fclose($fichier);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function read($path, $fileName)
    {
        $file = $this->getFile($path, $fileName);

        $this->isExist($file);
        $this->isRead($file);

        $data = file_get_contents($file);

        return unserialize($data);
    }

    /**
     * {@inheritDoc}
     */
    public function save($path, $fileName, array $data)
    {
        $file = $this->getFile($path, $fileName);

        $this->isExist($file);
        $this->isWrite($file);

        $fp = fopen($file, 'w');
        fwrite($fp, serialize($data));

        return fclose($fp);
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension()
    {
        return 'txt';
    }
}
