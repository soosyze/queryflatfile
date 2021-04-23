<?php

declare(strict_types=1);

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
        return unserialize($data);
    }
}
