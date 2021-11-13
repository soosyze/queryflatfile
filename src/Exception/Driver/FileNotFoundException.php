<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile\Exception\Driver;

/**
 * Exception levée lorsque le fichier de stockage est absent.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class FileNotFoundException extends DriverException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct(str_replace('\\', '/', $message), $code, $previous);
    }
}
