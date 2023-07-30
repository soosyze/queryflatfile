<?php

namespace Soosyze\Queryflatfile\Tests\Unit;

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
            ['fields' => []],
            $this->object->getTable()->toArray()
        );
    }

    public function testRename(): void
    {
        $this->object->renameColumn('0', '1');

        self::assertEquals(
            ['fields' => []],
            $this->object->getTable()->toArray()
        );
    }

    public function testModify(): void
    {
        $this->object->char('0')->modify();

        self::assertEquals(
            [
                'fields' => [
                    '0' => [ 'type' => 'char' ]
                ],
            ],
            $this->object->getTable()->toArray()
        );
    }
}
