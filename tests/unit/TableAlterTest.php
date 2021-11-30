<?php

namespace Queryflatfile\Tests\unit;

use Queryflatfile\Field;
use Queryflatfile\TableAlter;

class TableAlterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TableAlter
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new TableAlter('test');
    }

    public function testDrop(): void
    {
        $this->object->dropColumn('0');

        self::assertEquals($this->object->getTable()->toArray(), [
            'fields'     => [
                '0' => [ 'opt' => Field::OPT_DROP ]
            ],
            'increments' => null
        ]);
    }

    public function testRename(): void
    {
        $this->object->renameColumn('0', '1');

        self::assertEquals($this->object->getTable()->toArray(), [
            'fields'     => [
                '0' => [ 'opt' => Field::OPT_RENAME, 'to' => '1' ]
            ],
            'increments' => null
        ]);
    }

    public function testModify(): void
    {
        $this->object->char('0')->modify();

        self::assertEquals($this->object->getTable()->toArray(), [
            'fields'     => [
                '0' => [ 'type' => 'char', 'length' => 1 ]
            ],
            'increments' => null
        ]);
    }
}
