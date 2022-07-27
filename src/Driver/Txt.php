<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Driver;

/**
 * Manipule des données sérialisées dans des fichiers texte.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class Txt extends \Soosyze\Queryflatfile\Driver
{
    /**
     * {@inheritDoc}
     */
    public function checkExtension(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension(): string
    {
        return 'txt';
    }

    /**
     * {@inheritDoc}
     */
    public function serializeData(array $data): string
    {
        return serialize($data);
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeData(string $data): array
    {
        $dataUnserialize = unserialize($data);
        if (!is_array($dataUnserialize)) {
            throw new \Exception('An error occurred in deserializing the data.');
        }

        return $dataUnserialize;
    }
}
