<?php

declare(strict_types=1);

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
        return igbinary_serialize($data);
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeData(string $data): array
    {
        return igbinary_unserialize($data);
    }
}
