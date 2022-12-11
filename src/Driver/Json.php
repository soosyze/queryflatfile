<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile\Driver;

/**
 * Manipule des données dans des fichiers de type JSON
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class Json extends \Queryflatfile\Driver
{
    /**
     * {@inheritDoc}
     */
    public function create($path, $fileName, array $data = [])
    {
        $this->checkExtension();
        $file = $this->getFile($path, $fileName);

        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }
        if (!file_exists($file)) {
            $fichier = fopen($file, 'w+');
            fwrite($fichier, json_encode($data, JSON_UNESCAPED_UNICODE));

            return fclose($fichier);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function read($path, $fileName)
    {
        $this->checkExtension();
        $file = $this->getFile($path, $fileName);

        $this->isExist($file);
        $this->isRead($file);

        $json = file_get_contents($file);

        return json_decode($json, true, 512, JSON_UNESCAPED_UNICODE);
    }

    /**
     * {@inheritDoc}
     */
    public function save($path, $fileName, array $data)
    {
        $this->checkExtension();
        $file = $this->getFile($path, $fileName);

        $this->isExist($file);
        $this->isWrite($file);

        $fp = fopen($file, 'w');
        fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE));

        return fclose($fp);
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension()
    {
        return 'json';
    }

    /**
     * Déclanche une exception si le l'extension du fichier n'est pas chargée.
     *
     * @codeCoverageIgnore has
     *
     * @throws Exception\Driver\ExtensionNotLoadedException
     */
    private function checkExtension()
    {
        if (!extension_loaded('json')) {
            throw new Exception\Driver\ExtensionNotLoadedException('The json extension is not loaded.');
        }
    }
}
