<?php

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile\Exception;

/**
 * Exception générale.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
class Exception extends \Exception
{
    /**
     * Les balises autorisées.
     *
     * @var string
     */
    protected $balise = '<b><cite><code><em><i><span><sub><sup><strong><u>';

    public function __construct($message = '', $code = 0, $previous = null)
    {
        $msgEntities = htmlentities($message, ENT_QUOTES, 'UTF-8');
        $msgDecode   = htmlspecialchars_decode($msgEntities);
        $msgTags     = strip_tags($msgDecode, $this->balise);
        parent::__construct($msgTags, $code, $previous);
    }
}
