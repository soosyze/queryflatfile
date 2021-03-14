<?php

/**
 * Queryflatfile

 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile\Driver;

use Queryflatfile\Exception\Driver\ExtensionNotLoadedException;

/**
 * Manipule des données sérialisées avec l'extension igbinary
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class Igbinary extends \Queryflatfile\Driver
{
    /**
     * {@inheritDoc}
     */
    public function checkExtension()
    {
        if (!extension_loaded('igbinary')) {
            throw new ExtensionNotLoadedException('The igbinary extension is not loaded.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension()
    {
        return 'ig';
    }

    /**
     * {@inheritDoc}
     */
    public function serializeData(array $data)
    {
        return igbinary_serialize($data);
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeData($data)
    {
        return igbinary_unserialize($data);
    }
}
