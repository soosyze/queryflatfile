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
final class Txt extends \Queryflatfile\Driver
{
    /**
     * {@inheritDoc}
     */
    public function checkExtension()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension()
    {
        return 'txt';
    }

    /**
     * {@inheritDoc}
     */
    public function serializeData(array $data)
    {
        return serialize($data);
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeData($data)
    {
        return unserialize($data);
    }
}
