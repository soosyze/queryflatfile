<?php

namespace Queryflatfile\Test;

use Queryflatfile\TableBuilder;

class TableBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TableBuilder
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new TableBuilder;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testIncrements()
    {
        $this->object->increments('id');

        $this->assertArraySubset($this->object->build(), [
            'id' => [ 'type' => 'increments' ]
        ]);
    }

    /**
     * @expectedException \Exception
     */
    public function testIncrementsException()
    {
        $this->object->increments('id')
            ->increments('error');
    }

    public function testChar()
    {
        $this->object->char('id')
            ->char('id2', 2);

        $this->assertArraySubset($this->object->build(), [
            'id'  => [ 'type' => 'char', 'length' => 1 ],
            'id2' => [ 'type' => 'char', 'length' => 2 ]
        ]);
    }

    /**
     * @expectedException \Exception
     */
    public function testCharException()
    {
        $this->object->char('id2', -1);
    }

    public function testText()
    {
        $this->object->text('id');

        $this->assertArraySubset($this->object->build(), [
            'id' => [ 'type' => 'text' ]
        ]);
    }

    public function testString()
    {
        $this->object->string('id')
            ->string('id2', 256);

        $this->assertArraySubset($this->object->build(), [
            'id'  => [ 'type' => 'string', 'length' => 255 ],
            'id2' => [ 'type' => 'string', 'length' => 256 ],
        ]);
    }

    /**
     * @expectedException \Exception
     */
    public function testStringException()
    {
        $this->object->string('id', -1);
    }

    public function testInteger()
    {
        $this->object->integer('id');

        $this->assertArraySubset($this->object->build(), [
            'id' => [ 'type' => 'integer' ]
        ]);
    }

    public function testFloat()
    {
        $this->object->float('id');

        $this->assertArraySubset($this->object->build(), [
            'id' => [ 'type' => 'float' ]
        ]);
    }

    public function testBoolean()
    {
        $this->object->boolean('id');

        $this->assertArraySubset($this->object->build(), [
            'id' => [ 'type' => 'boolean' ]
        ]);
    }

    public function testDate()
    {
        $this->object->date('id');

        $this->assertArraySubset($this->object->build(), [
            'id' => [ 'type' => 'date' ]
        ]);
    }

    public function testDatetime()
    {
        $this->object->datetime('id');

        $this->assertArraySubset($this->object->build(), [
            'id' => [ 'type' => 'datetime' ]
        ]);
    }

    public function testNullable()
    {
        $this->object
            ->increments('0')->nullable()
            ->char('1')->nullable()
            ->text('2')->nullable()
            ->string('3')->nullable()
            ->integer('4')->nullable()
            ->float('5')->nullable()
            ->boolean('6')->nullable()
            ->date('7')->nullable()
            ->datetime('8')->nullable();

        $this->assertArraySubset($this->object->build(), [
            '0' => [ 'type' => 'increments', 'nullable' => true ],
            '1' => [ 'type' => 'char', 'length' => 1, 'nullable' => true ],
            '2' => [ 'type' => 'text', 'nullable' => true ],
            '3' => [ 'type' => 'string', 'length' => 255, 'nullable' => true ],
            '4' => [ 'type' => 'integer', 'nullable' => true ],
            '5' => [ 'type' => 'float', 'nullable' => true ],
            '6' => [ 'type' => 'boolean', 'nullable' => true ],
            '7' => [ 'type' => 'date', 'nullable' => true ],
            '8' => [ 'type' => 'datetime', 'nullable' => true ],
        ]);
    }

    /**
     * @expectedException \Exception
     */
    public function testNullableException()
    {
        $this->object->nullable();
    }

    public function testUnsigned()
    {
        $this->object->integer('id')->unsigned();

        $this->assertArraySubset($this->object->build(), [
            'id' => [ 'type' => 'integer', 'unsigned' => true ]
        ]);
    }

    /**
     * @expectedException \Exception
     */
    public function testUnsignedException()
    {
        $this->object->unsigned();
    }

    /**
     * @expectedException \Exception
     */
    public function testUnsignedTypeException()
    {
        $this->object->string('id')->unsigned();
    }

    public function testComment()
    {
        $this->object->increments('id')->comment('identifiant');

        $this->assertArraySubset($this->object->build(), [
            'id' => [ 'type' => 'increments', '_comment' => 'identifiant' ]
        ]);
    }

    /**
     * @expectedException \Exception
     */
    public function testCommentException()
    {
        $this->object->comment('identifiant');
    }

    public function testValueDefault()
    {
        $this->object->increments('0')
            ->char('1')->valueDefault('a')
            ->text('2')->valueDefault('test')
            ->string('3')->valueDefault('test')
            ->integer('4')->valueDefault(1)
            ->float('5')->valueDefault(1.1)
            ->boolean('6')->valueDefault(true)
            ->date('7')->valueDefault('2017-11-26')
            ->date('7.1')->valueDefault('current_date')
            ->datetime('8')->valueDefault('2017-11-26 22:00:00')
            ->datetime('8.1')->valueDefault('current_datetime');

        $this->assertArraySubset($this->object->build(), [
            '0'   => [ 'type' => 'increments' ],
            '1'   => [ 'type' => 'char', 'length' => 1, 'default' => 'a' ],
            '2'   => [ 'type' => 'text', 'default' => 'test' ],
            '3'   => [ 'type' => 'string', 'length' => 255, 'default' => 'test' ],
            '4'   => [ 'type' => 'integer', 'default' => 1 ],
            '5'   => [ 'type' => 'float', 'default' => 1.1 ],
            '6'   => [ 'type' => 'boolean', 'default' => true ],
            '7'   => [ 'type' => 'date', 'default' => '2017-11-26' ],
            '7.1' => [ 'type' => 'date', 'default' => 'current_date' ],
            '8'   => [ 'type' => 'datetime', 'default' => '2017-11-26 22:00:00' ],
            '8.1' => [ 'type' => 'datetime', 'default' => 'current_datetime' ],
        ]);
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultException()
    {
        $this->object->valueDefault('1');
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultIncrementException()
    {
        $this->object->increments('0')->valueDefault(2);
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultCharException()
    {
        $this->object->char('0')->valueDefault(1);
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultCharLenghtException()
    {
        $this->object->char('0')->valueDefault('error');
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultTextException()
    {
        $this->object->text('0')->valueDefault(1);
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultStringException()
    {
        $this->object->string('0')->valueDefault(1);
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultIntegerException()
    {
        $this->object->integer('0')->valueDefault('error');
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultFloatException()
    {
        $this->object->float('0')->valueDefault('error');
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultBoolException()
    {
        $this->object->boolean('0')->valueDefault('1');
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultDateException()
    {
        $this->object->date('0')->valueDefault('1');
    }

    /**
     * @expectedException \Exception
     */
    public function testValueDefaultDatetimesException()
    {
        $this->object->datetime('0')->valueDefault('1');
    }

    /**
     * @expectedException \Exception
     */
    public function testCheckValueException()
    {
        TableBuilder::checkValue('testName', 'error', 'testValue');
    }

    public function testDrop()
    {
        $this->object->dropColumn('0');

        $this->assertArraySubset($this->object->build(), [
            [ 'opt' => 'drop', 'name' => '0' ]
        ]);
    }

    public function testRename()
    {
        $this->object->renameColumn('0', '1');

        $this->assertArraySubset($this->object->build(), [
            [ 'opt' => 'rename', 'name' => '0', 'to' => '1' ]
        ]);
    }

    public function testModify()
    {
        $this->object->char('0')->modify();

        $this->assertArraySubset($this->object->build(), [
            '0' => [ 'type' => 'char', 'length' => 1, 'opt' => 'modify' ]
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
