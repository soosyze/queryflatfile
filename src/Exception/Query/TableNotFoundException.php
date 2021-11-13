<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile\Exception\Query;

/**
 * Exception levée lorsqu'une table absente du schéma.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class TableNotFoundException extends QueryException
{
    public function __construct(
        string $tableName = '',
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct(
            $tableName === ''
                ? 'Table is missing.'
                : sprintf('The %s table is missing.', $tableName),
            $code,
            $previous
        );
    }
}
