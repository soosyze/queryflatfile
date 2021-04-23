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
 * Manipule des données sérialisées avec l'extension msgpack
 *
 * @see https://msgpack.org/
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class MsgPack extends \Queryflatfile\Driver
{
    /**
     * {@inheritDoc}
     */
    public function checkExtension(): void
    {
        if (!extension_loaded('msgpack')) {
            throw new ExtensionNotLoadedException('The msgpack extension is not loaded.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension(): string
    {
        return 'msg';
    }

    /**
     * {@inheritDoc}
     */
    public function serializeData(array $data): string
    {
        return msgpack_pack($data);
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeData(string $data): array
    {
        return msgpack_unpack($data);
    }
}
