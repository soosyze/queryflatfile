<?php

declare(strict_types=1);

/**
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Soosyze\Queryflatfile\Driver;

use Soosyze\Queryflatfile\Exception\Driver\ExtensionNotLoadedException;

/**
 * Manipule des données sérialisées avec l'extension msgpack
 *
 * @see https://msgpack.org/
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
final class MsgPack extends \Soosyze\Queryflatfile\Driver
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
        $dataUnserialize = msgpack_unpack($data);
        if (!is_array($dataUnserialize)) {
            throw new \Exception('An error occurred in deserializing the data.');
        }

        return $dataUnserialize;
    }
}
