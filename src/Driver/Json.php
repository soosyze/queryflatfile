<?php

declare(strict_types=1);

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
        $encode = json_encode($data, JSON_UNESCAPED_UNICODE);

        return $encode === false
            ? '{}'
            :$encode;
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeData(string $data): array
    {
        return json_decode($data, true, 512, JSON_UNESCAPED_UNICODE);
    }
}
