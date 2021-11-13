<?php

namespace Queryflatfile\Tests\unit;

use Queryflatfile\TableAlter;

class TableAlterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TableAlter
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new TableAlter;
    }

    public function testDrop(): void
    {
        $this->object->dropColumn('0');

        self::assertEquals($this->object->build(), [
            '0' => [ 'opt' => TableAlter::OPT_DROP ]
        ]);
    }

    public function testRename(): void
    {
        $this->object->renameColumn('0', '1');

        self::assertEquals($this->object->build(), [
           '0' => [ 'opt' => TableAlter::OPT_RENAME, 'to' => '1' ]
        ]);
    }

    public function testModify(): void
    {
        $this->object->char('0')->modify();

        self::assertEquals($this->object->build(), [
            '0' => [ 'type' => 'char', 'length' => 1, 'opt' => TableAlter::OPT_MODIFY ]
        ]);
    }

    public function testDropException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No column selected for value default.');
        $this->object->dropColumn('0')->valueDefault('test');
    }

    public function testRenameException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No column selected for value default.');
        $this->object->renameColumn('0', '1')->valueDefault('test');
    }

    public function testModifyException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No column selected for value default.');
        $this->object->char('0')->modify()->valueDefault('test');
    }
}
