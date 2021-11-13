<?php

namespace Queryflatfile\Tests\unit;

use Queryflatfile\TableBuilder;

class TableBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TableBuilder
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new TableBuilder;
    }

    public function testIncrements(): void
    {
        $this->object->increments('id');

        self::assertEquals($this->object->build(), [
            'id' => [ 'type' => 'increments' ]
        ]);
    }

    public function testIncrementsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Only one incremental column is allowed per table.'
        );
        $this->object
            ->increments('id')
            ->increments('error');
    }

    public function testChar(): void
    {
        $this->object
            ->char('id')
            ->char('id2', 2);

        self::assertEquals($this->object->build(), [
            'id'  => [ 'type' => 'char', 'length' => 1 ],
            'id2' => [ 'type' => 'char', 'length' => 2 ]
        ]);
    }

    public function testCharException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The length passed in parameter is not of numeric type.'
        );
        $this->object->char('id2', -1);
    }

    public function testText(): void
    {
        $this->object->text('id');

        self::assertEquals($this->object->build(), [
            'id' => [ 'type' => 'text' ]
        ]);
    }

    public function testString(): void
    {
        $this->object
            ->string('id')
            ->string('id2', 256);

        self::assertEquals($this->object->build(), [
            'id'  => [ 'type' => 'string', 'length' => 255 ],
            'id2' => [ 'type' => 'string', 'length' => 256 ],
        ]);
    }

    public function testStringException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The length passed in parameter is not of numeric type.'
        );
        $this->object->string('id', -1);
    }

    public function testInteger(): void
    {
        $this->object->integer('id');

        self::assertEquals($this->object->build(), [
            'id' => [ 'type' => 'integer' ]
        ]);
    }

    public function testFloat(): void
    {
        $this->object->float('id');

        self::assertEquals($this->object->build(), [
            'id' => [ 'type' => 'float' ]
        ]);
    }

    public function testBoolean(): void
    {
        $this->object->boolean('id');

        self::assertEquals($this->object->build(), [
            'id' => [ 'type' => 'boolean' ]
        ]);
    }

    public function testDate(): void
    {
        $this->object->date('id');

        self::assertEquals($this->object->build(), [
            'id' => [ 'type' => 'date' ]
        ]);
    }

    public function testDatetime(): void
    {
        $this->object->datetime('id');

        self::assertEquals($this->object->build(), [
            'id' => [ 'type' => 'datetime' ]
        ]);
    }

    public function testNullable(): void
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

        self::assertEquals($this->object->build(), [
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

    public function testNullableException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No column selected for nullable.');
        $this->object->nullable();
    }

    public function testUnsigned(): void
    {
        $this->object->integer('id')->unsigned();

        self::assertEquals($this->object->build(), [
            'id' => [ 'type' => 'integer', 'unsigned' => true ]
        ]);
    }

    public function testUnsignedException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No column selected for unsigned.');
        $this->object->unsigned();
    }

    public function testUnsignedTypeException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Impossiblie of unsigned type string only integer.'
        );
        $this->object->string('id')->unsigned();
    }

    public function testComment(): void
    {
        $this->object->increments('id')->comment('identifiant');

        self::assertEquals($this->object->build(), [
            'id' => [ 'type' => 'increments', '_comment' => 'identifiant' ]
        ]);
    }

    public function testCommentException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No column selected for comment.');
        $this->object->comment('identifiant');
    }

    public function testValueDefault(): void
    {
        $this->object
            ->increments('0')
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

        self::assertEquals($this->object->build(), [
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

    public function testValueDefaultException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No column selected for value default.');
        $this->object->valueDefault('1');
    }

    public function testValueDefaultIncrementException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'An incremental type column can not have a default value.'
        );
        $this->object->increments('0')->valueDefault(2);
    }

    public function testValueDefaultCharException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The default value (1) for column 0 does not correspond to type char.'
        );
        $this->object->char('0')->valueDefault(1);
    }

    public function testValueDefaultCharLenghtException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The default value is larger than the specified size.'
        );
        $this->object->char('0')->valueDefault('error');
    }

    public function testValueDefaultTextException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The default value (1) for column 0 does not correspond to type text.'
        );
        $this->object->text('0')->valueDefault(1);
    }

    public function testValueDefaultStringException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The default value (1) for column 0 does not correspond to type string.'
        );
        $this->object->string('0')->valueDefault(1);
    }

    public function testValueDefaultIntegerException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The default value (error) for column 0 does not correspond to type integer.'
        );
        $this->object->integer('0')->valueDefault('error');
    }

    public function testValueDefaultFloatException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The default value (error) for column 0 does not correspond to type float.'
        );
        $this->object->float('0')->valueDefault('error');
    }

    public function testValueDefaultBoolException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The default value (1) for column 0 does not correspond to type boolean.'
        );
        $this->object->boolean('0')->valueDefault('1');
    }

    public function testValueDefaultDateException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The default value (1) for column 0 does not correspond to type date.'
        );
        $this->object->date('0')->valueDefault('1');
    }

    public function testValueDefaultDatetimesException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The default value (1) for column 0 does not correspond to type datetime.'
        );
        $this->object->datetime('0')->valueDefault('1');
    }

    public function testCheckValueException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Type error not supported');
        TableBuilder::filterValue('testName', 'error', 'testValue');
    }
}
