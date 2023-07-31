<?php

declare(strict_types=1);

/**
 * Queryflatfile

 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Drivers;

use Soosyze\Queryflatfile\Exceptions\Driver\ExtensionNotLoadedException;

/**
 * Manipule des données sérialisées avec l'extension igbinary
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class Igbinary extends \Soosyze\Queryflatfile\Driver
{
    /**
     * {@inheritDoc}
     */
    public function checkExtension(): void
    {
        if (!extension_loaded('igbinary')) {
            throw new ExtensionNotLoadedException('The igbinary extension is not loaded.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension(): string
    {
        return 'ig';
    }

    /**
     * {@inheritDoc}
     */
    public function serializeData(array $data): string
    {
        $serializeData = igbinary_serialize($data);
        if (!is_string($serializeData)) {
            throw new \Exception('An error occurred in serializing the data.');
        }

        return $serializeData;
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeData(string $data): array
    {
        $dataUnserialize = igbinary_unserialize($data);
        if (!is_array($dataUnserialize)) {
            throw new \Exception('An error occurred in deserializing the data.');
        }

        return $dataUnserialize;
    }
}
