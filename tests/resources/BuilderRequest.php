<?php

namespace Soosyze\Queryflatfile\Tests\resources;

use Soosyze\Queryflatfile\Request;
use Soosyze\Queryflatfile\RequestInterface;

class BuilderRequest extends Request implements RequestInterface
{
    public function orderByFirstname(): BuilderRequest
    {
        return $this->orderBy('firstname');
    }
}
