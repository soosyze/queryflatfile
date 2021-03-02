<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile\Driver;

/**
 * Manipule des données sérialisées dans des fichiers texte.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
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
            mkdir($path, 0755);
        }
        if (!file_exists($file)) {
            $handle = fopen($file, 'w+');
            fwrite($handle, serialize($data));

            return fclose($handle);
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

        $handle = fopen($file, 'w');
        fwrite($handle, serialize($data));

        return fclose($handle);
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension()
    {
        return 'txt';
    }
}
