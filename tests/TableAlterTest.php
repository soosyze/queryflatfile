<?php

namespace Queryflatfile\Test;

use Queryflatfile\TableAlter;

class TableAlterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TableAlter
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new TableAlter;
    }

    public function testDrop()
    {
        $this->object->dropColumn('0');

        self::assertArraySubset($this->object->build(), [
            [ 'opt' => TableAlter::OPT_DROP, 'name' => '0' ]
        ]);
    }

    public function testRename()
    {
        $this->object->renameColumn('0', '1');

        self::assertArraySubset($this->object->build(), [
            [ 'opt' => TableAlter::OPT_RENAME, 'name' => '0', 'to' => '1' ]
        ]);
    }

    public function testModify()
    {
        $this->object->char('0')->modify();

        self::assertArraySubset($this->object->build(), [
            '0' => [ 'type' => 'char', 'length' => 1, 'opt' => TableAlter::OPT_MODIFY ]
        ]);
    }

    /**
     * @expectedException \Exception
     */
    public function testDropException()
    {
        $this->object->dropColumn('0')->valueDefault('test');
    }

    /**
     * @expectedException \Exception
     */
    public function testRenameException()
    {
        $this->object->renameColumn('0', '1')->valueDefault('test');
    }

    /**
     * @expectedException \Exception
     */
    public function testModifyException()
    {
        $this->object->char('0')->modify()->valueDefault('test');
    }
}
