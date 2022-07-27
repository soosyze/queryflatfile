<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Driver;

use Soosyze\Queryflatfile\Exception\Driver\ExtensionNotLoadedException;

/**
 * Manipule des données dans des fichiers de type JSON
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class Json extends \Soosyze\Queryflatfile\Driver
{
    /**
     * {@inheritDoc}
     */
    public function checkExtension(): void
    {
        if (!extension_loaded('json')) {
            throw new ExtensionNotLoadedException('The json extension is not loaded.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension(): string
    {
        return 'json';
    }

    /**
     * {@inheritDoc}
     */
    public function serializeData(array $data): string
    {
        $serializeData = json_encode($data, JSON_UNESCAPED_UNICODE);
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
        $dataUnserialize = json_decode($data, true, 512, JSON_UNESCAPED_UNICODE);
        if (!is_array($dataUnserialize)) {
            throw new \Exception('An error occurred in deserializing the data.');
        }

        return $dataUnserialize;
    }
}
