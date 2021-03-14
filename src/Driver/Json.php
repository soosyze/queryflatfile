<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile\Driver;

use Queryflatfile\Exception\Driver\ExtensionNotLoadedException;

/**
 * Manipule des données dans des fichiers de type JSON
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class Json extends \Queryflatfile\Driver
{
    /**
     * {@inheritDoc}
     */
    public function checkExtension()
    {
        if (!extension_loaded('json')) {
            throw new ExtensionNotLoadedException('The json extension is not loaded.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension()
    {
        return 'json';
    }

    /**
     * {@inheritDoc}
     */
    public function serializeData(array $data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeData($data)
    {
        return json_decode($data, true, 512, JSON_UNESCAPED_UNICODE);
    }
}
