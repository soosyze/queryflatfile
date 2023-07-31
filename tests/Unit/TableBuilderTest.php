<?php

namespace Soosyze\Queryflatfile\Tests\Unit;

use Soosyze\Queryflatfile\Enums\FieldType;
use Soosyze\Queryflatfile\Exception\TableBuilder\ColumnsValueException;
use Soosyze\Queryflatfile\Field;
use Soosyze\Queryflatfile\TableBuilder;

class TableBuilderTest extends \PHPUnit\Framework\TestCase
{
    protected TableBuilder $object;

    protected function setUp(): void
    {
        $this->object = new TableBuilder('test');
    }

    public function testIncrements(): void
    {
        $this->object->increments('id');

        self::assertEquals(
            [
                'fields'     => [
                    'id' => [ 'type' => 'increments' ]
                ],
                'increments' => 0
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testIncrementsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Only one incremental column is allowed per table.'
        );
        $this->object->increments('id');
        $this->object->increments('error');
    }

    public function testChar(): void
    {
        $this->object->char('id');
        $this->object->char('id2');

        self::assertEquals(
            [
                'fields'     => [
                    'id'  => [ 'type' => 'char' ],
                    'id2' => [ 'type' => 'char' ]
                ],
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testText(): void
    {
        $this->object->text('id');

        self::assertEquals(
            [
                'fields'     => [
                    'id' => [ 'type' => 'text' ]
                ],
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testString(): void
    {
        $this->object->string('id');
        $this->object->string('id2', 256);

        self::assertEquals(
            [
                'fields'     => [
                    'id'  => [ 'type' => 'string', 'length' => 255 ],
                    'id2' => [ 'type' => 'string', 'length' => 256 ],
                ],
            ],
            $this->object->getTable()->toArray()
        );
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

        self::assertEquals(
            [
                'fields'     => [
                    'id' => [ 'type' => 'integer' ]
                ],
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testFloat(): void
    {
        $this->object->float('id');

        self::assertEquals(
            [
                'fields'     => [
                    'id' => [ 'type' => 'float' ]
                ],
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testBoolean(): void
    {
        $this->object->boolean('id');

        self::assertEquals(
            [
                'fields'     => [
                    'id' => [ 'type' => 'boolean' ]
                ],
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testDate(): void
    {
        $this->object->date('id');

        self::assertEquals(
            [
                'fields'     => [
                    'id' => [ 'type' => 'date' ]
                ],
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testDatetime(): void
    {
        $this->object->datetime('id');

        self::assertEquals(
            [
                'fields'     => [
                    'id' => [ 'type' => 'datetime' ]
                ],
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testNullable(): void
    {
        $this->object->increments('0')->nullable();
        $this->object->char('1')->nullable();
        $this->object->text('2')->nullable();
        $this->object->string('3')->nullable();
        $this->object->integer('4')->nullable();
        $this->object->float('5')->nullable();
        $this->object->boolean('6')->nullable();
        $this->object->date('7')->nullable();
        $this->object->datetime('8')->nullable();

        self::assertEquals(
            [
                'fields'     => [
                    '0' => [ 'type' => 'increments', 'nullable' => true ],
                    '1' => [ 'type' => 'char', 'nullable' => true ],
                    '2' => [ 'type' => 'text', 'nullable' => true ],
                    '3' => [ 'type' => 'string', 'length' => 255, 'nullable' => true ],
                    '4' => [ 'type' => 'integer', 'nullable' => true ],
                    '5' => [ 'type' => 'float', 'nullable' => true ],
                    '6' => [ 'type' => 'boolean', 'nullable' => true ],
                    '7' => [ 'type' => 'date', 'nullable' => true ],
                    '8' => [ 'type' => 'datetime', 'nullable' => true ],
                ],
                'increments' => 0
            ],
            $this->object->getTable()->toArray()
        );
        self::assertEquals(null, $this->object->getTable()->getField('7')->getValueDefault());
        self::assertEquals(null, $this->object->getTable()->getField('8')->getValueDefault());
    }

    public function testDateNullableException(): void
    {
        $this->object->date('7');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            '7 not nullable or not default.'
        );
        self::assertEquals(null, $this->object->getTable()->getField('7')->getValueDefault());
    }

    public function testDatetimeNullableException(): void
    {
        $this->object->datetime('8');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            '8 not nullable or not default.'
        );
        self::assertEquals(null, $this->object->getTable()->getField('8')->getValueDefault());
    }

    public function testUnsigned(): void
    {
        $this->object->integer('id')->unsigned();

        self::assertEquals(
            [
                'fields'     => [
                    'id' => [ 'type' => 'integer', 'unsigned' => true ]
                ],
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testComment(): void
    {
        $this->object->increments('id')->comment('identifiant');

        self::assertEquals(
            [
                'fields'     => [
                    'id' => [ 'type' => 'increments', '_comment' => 'identifiant' ]
                ],
                'increments' => 0
            ],
            $this->object->getTable()->toArray()
        );
    }

    public function testValueDefault(): void
    {
        $this->object->increments('0');
        $this->object->char('1')->valueDefault('a');
        $this->object->text('2')->valueDefault('test');
        $this->object->string('3')->valueDefault('test');
        $this->object->integer('4')->valueDefault(1);
        $this->object->float('5')->valueDefault(1.1);
        $this->object->boolean('6')->valueDefault(true);
        $this->object->date('7')->valueDefault('2017-11-26');
        $this->object->date('7.1')->valueDefault('current_date');
        $this->object->datetime('8')->valueDefault('2017-11-26 22:00:00');
        $this->object->datetime('8.1')->valueDefault('current_datetime');

        self::assertEquals(
            [
                'fields'     => [
                    '0'   => [ 'type' => 'increments' ],
                    '1'   => [ 'type' => 'char', 'default' => 'a' ],
                    '2'   => [ 'type' => 'text', 'default' => 'test' ],
                    '3'   => [ 'type' => 'string', 'length' => 255, 'default' => 'test' ],
                    '4'   => [ 'type' => 'integer', 'default' => 1 ],
                    '5'   => [ 'type' => 'float', 'default' => 1.1 ],
                    '6'   => [ 'type' => 'boolean', 'default' => true ],
                    '7'   => [ 'type' => 'date', 'default' => '2017-11-26' ],
                    '7.1' => [ 'type' => 'date', 'default' => 'current_date' ],
                    '8'   => [ 'type' => 'datetime', 'default' => '2017-11-26 22:00:00' ],
                    '8.1' => [ 'type' => 'datetime', 'default' => 'current_datetime' ],
                ],
                'increments' => 0
            ],
            $this->object->getTable()->toArray()
        );
        self::assertEquals('2017-11-26', $this->object->getTable()->getField('7')->getValueDefault());
        self::assertEquals(date('Y-m-d', time()), $this->object->getTable()->getField('7.1')->getValueDefault());
        self::assertEquals('2017-11-26 22:00:00', $this->object->getTable()->getField('8')->getValueDefault());
        self::assertEquals(date('Y-m-d H:i:s', time()), $this->object->getTable()->getField('8.1')->getValueDefault());
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     *
     * @dataProvider getValueDefaultExceptionProvider
     */
    public function testValueDefaulException(
        FieldType $fieldType,
        mixed $valueDefault,
        string $exceptionClass,
        string $exceptionMessage
    ): void {
        $tableBuilder = new TableBuilder('test');

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        $tableBuilder->{$fieldType->value}('0')->valueDefault($valueDefault);
    }

    public static function getValueDefaultExceptionProvider(): \Generator
    {
        yield [
            FieldType::Boolean, 1,
            \InvalidArgumentException::class, 'The value of the 0 field must be of type boolean: integer given.'
        ];
        yield [
            FieldType::Char, 1,
            \InvalidArgumentException::class, 'The value of the 0 field must be of type string: integer given.'
        ];
        yield [
            FieldType::Char, 'error',
            \LengthException::class, 'The value of the 0 field must be less than or equal to 1 characters: 5 given'
        ];
        yield [
            FieldType::Date, 1,
            \InvalidArgumentException::class, 'The value of the 0 field must be of type string: integer given.'
        ];
        yield [
            FieldType::Date, '1',
            ColumnsValueException::class, 'The value of the 0 field must be a valid date: 1 given'
        ];
        yield [
            FieldType::DateTime, 1,
            \InvalidArgumentException::class, 'The value of the 0 field must be of type string: integer given.'
        ];
        yield [
            FieldType::DateTime, '1',
            ColumnsValueException::class, 'The value of the 0 field must be a valid date: 1 given'
        ];
        yield [
            FieldType::Float, '1',
            \InvalidArgumentException::class, 'The value of the 0 field must be of type float: string given.'
        ];
        yield [
            FieldType::Increment, 2,
            \Exception::class, 'An incremental type column can not have a default value.'
        ];
        yield [
            FieldType::Int, '1',
            \InvalidArgumentException::class, 'The value of the 0 field must be of type integer: string given.'
        ];
        yield [
            FieldType::String, 1,
            \InvalidArgumentException::class, 'The value of the 0 field must be of type string: integer given.'
        ];
        yield [
            FieldType::String, str_repeat('0', 256),
            \LengthException::class, 'The value of the 0 field must be less than or equal to 255 characters: 256 given'
        ];
        yield [
            FieldType::Text, 1,
            \InvalidArgumentException::class, 'The value of the 0 field must be of type string: integer given.'
        ];
    }

    public function testCreateTableFromArray(): void
    {
        $this->object->increments('field_0');
        $this->object->char('field_1')->valueDefault('a');
        $this->object->text('field_2')->valueDefault('test');
        $this->object->string('field_3')->valueDefault('test');
        $this->object->integer('field_4')->unsigned()->valueDefault(1);
        $this->object->float('field_5')->valueDefault(1.1);
        $this->object->boolean('field_6')->valueDefault(true);
        $this->object->date('field_7')->valueDefault('2017-11-26');
        $this->object->date('field_7.1')->valueDefault('current_date');
        $this->object->datetime('field_8')->valueDefault('2017-11-26 22:00:00');
        $this->object->datetime('field_8.1')->valueDefault('current_datetime');

        $expected = [
            'test' => [
                'fields'     => [
                    'field_0'   => [ 'type' => 'increments' ],
                    'field_1'   => [ 'type' => 'char', 'default' => 'a' ],
                    'field_2'   => [ 'type' => 'text', 'default' => 'test' ],
                    'field_3'   => [ 'type' => 'string', 'length' => 255, 'default' => 'test' ],
                    'field_4'   => [ 'type' => 'integer', 'default' => 1, 'unsigned' => true ],
                    'field_5'   => [ 'type' => 'float', 'default' => 1.1 ],
                    'field_6'   => [ 'type' => 'boolean', 'default' => true ],
                    'field_7'   => [ 'type' => 'date', 'default' => '2017-11-26' ],
                    'field_7.1' => [ 'type' => 'date', 'default' => 'current_date' ],
                    'field_8'   => [ 'type' => 'datetime', 'default' => '2017-11-26 22:00:00' ],
                    'field_8.1' => [ 'type' => 'datetime', 'default' => 'current_datetime' ],
                ],
                'increments' => 0
            ]
        ];

        self::assertEquals(
            $this->object->getTable(),
            TableBuilder::createTableFromArray('test', $expected[ 'test' ])
        );
    }

    public function testCreateTableFromArrayException(): void
    {
        $data = [
            'fields' => [
                'field_0' => [ 'type' => 'error' ]
            ]
        ];
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Type error not supported.'
        );
        TableBuilder::createTableFromArray('test', $data);
    }
}
