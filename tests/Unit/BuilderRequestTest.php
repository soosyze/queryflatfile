<?php

namespace Soosyze\Queryflatfile\Tests\Unit;

use Soosyze\Queryflatfile\Schema;
use Soosyze\Queryflatfile\Tests\Resources\BuilderRequest;
use Soosyze\Queryflatfile\Tests\Unit\Helpers\DriverMock;

class BuilderRequestTest extends \PHPUnit\Framework\TestCase
{
    use DriverMock;

    protected Schema $bdd;

    protected BuilderRequest $request;

    protected function setUp(): void
    {
        $this->bdd = (new Schema)
            ->setConfig('builder', 'schema', $this->getDriverMock())
            ->setPathRoot(dirname(__DIR__) . '/Fixtures/');

        $this->request  = new BuilderRequest($this->bdd);
    }

    public function testOrderByCustom(): void
    {
        $data = $this->request
            ->orderByFirstname()
            ->select('firstname')
            ->from('user');

        self::assertEquals(
            'SELECT firstname FROM user ORDER BY firstname ASC;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'firstname' => null ],
                [ 'firstname' => 'Eva' ],
                [ 'firstname' => 'Jean' ],
                [ 'firstname' => 'Manon' ],
                [ 'firstname' => 'Marie' ],
                [ 'firstname' => 'Mathieu' ],
                [ 'firstname' => 'Pierre' ]
            ],
            $data->fetchAll()
        );
    }
}
