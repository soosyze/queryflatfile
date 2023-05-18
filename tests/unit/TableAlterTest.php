<?php

namespace Soosyze\Queryflatfile\Tests\unit;

use Soosyze\Queryflatfile\Field;
use Soosyze\Queryflatfile\TableAlter;

class TableAlterTest extends \PHPUnit\Framework\TestCase
{
    protected TableAlter $object;

    protected function setUp(): void
    {
        $this->object = new TableAlter('test');
    }

    public function testDrop(): void
    {
        $this->object->dropColumn('0');

        self::assertEquals(
            [
                'fields'     => [
                    '0' => [ 'type' => '', 'opt' => Field::OPT_DROP ]
                ],
                'increments' => null
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testRename(): void
    {
        $this->object->renameColumn('0', '1');

        self::assertEquals(
            [
                'fields'     => [
                    '0' => [ 'type' => '', 'opt' => Field::OPT_RENAME, 'to' => '1' ]
                ],
                'increments' => null
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testModify(): void
    {
        $this->object->char('0')->modify();

        self::assertEquals(
            [
                'fields'     => [
                    '0' => [ 'type' => 'char', 'length' => 1 ]
                ],
                'increments' => null
            ],
            $this->object->getTable()->toArray()
        );
    }
}
