<?php

namespace Soosyze\Queryflatfile\Tests\unit;

use Soosyze\Queryflatfile\Driver\Json;
use Soosyze\Queryflatfile\Exception\Query\TableNotFoundException;
use Soosyze\Queryflatfile\Request;
use Soosyze\Queryflatfile\Schema;
use Soosyze\Queryflatfile\TableAlter;
use Soosyze\Queryflatfile\TableBuilder;

class SchemaJsonTest extends \PHPUnit\Framework\TestCase
{
    private const DATA_DIR = __DIR__ . '/data2/';

    protected Schema $bdd;

    protected Request $request;

    protected function setUp(): void
    {
        $this->bdd = (new Schema)
            ->setConfig(self::DATA_DIR, 'schema', new Json());

        $this->request = new Request($this->bdd);

        $this->bdd->createTableIfNotExists('test', static function (TableBuilder $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('firstname');
        });

        $this->request->insertInto('test', [ 'name', 'firstname' ])
            ->values([ 'NOEL', 'Mathieu' ])
            ->values([ 'DUPOND', 'Jean' ])
            ->values([ 'MARTIN', 'Manon' ])
            ->execute();

        $this->bdd->createTable('test_second', static function (TableBuilder $table): void {
            $table->integer('value_i');
            $table->string('value_s');
        });

        $this->request->insertInto('test_second', [ 'value_i', 'value_s' ])
            ->values([ 10, 'value1' ])
            ->values([ 20, 'value2' ])
            ->values([ 30, 'value3' ])
            ->execute();
    }

    protected function tearDown(): void
    {
        if (!file_exists(self::DATA_DIR)) {
            return;
        }
        $dir = new \DirectoryIterator(self::DATA_DIR);
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->getRealPath() === false) {
                continue;
            }
            unlink($fileInfo->getRealPath());
        }
        if (file_exists(self::DATA_DIR)) {
            rmdir(self::DATA_DIR);
        }
    }

    public function testGetSchema(): void
    {
        $schema = [];
        foreach ($this->bdd->getSchema() as $name => $table) {
            $schema[$name] = $table->toArray();
        }

        self::assertEquals(
            [
                'test'        => [
                    'fields'     => [
                        'id'        => [ 'type' => 'increments' ],
                        'name'      => [ 'type' => 'string', 'length' => 255 ],
                        'firstname' => [ 'type' => 'string', 'length' => 255 ]
                    ],
                    'increments' => 3
                ],
                'test_second' => [
                    'fields'     => [
                        'value_i' => [ 'type' => 'integer' ],
                        'value_s' => [ 'type' => 'string', 'length' => 255 ]
                    ],
                    'increments' => null
                ]
            ],
            $schema
        );
    }

    public function testGetSchemaTable(): void
    {
        self::assertEquals(
            [
                'fields'     => [
                    'id'        => [ 'type' => 'increments' ],
                    'name'      => [ 'type' => 'string', 'length' => 255 ],
                    'firstname' => [ 'type' => 'string', 'length' => 255 ]
                ],
                'increments' => 3
            ],
            $this->bdd->getTableSchema('test')->toArray()
        );
    }

    public function testHasTable(): void
    {
        self::assertTrue($this->bdd->hasTable('test'));
        self::assertFalse($this->bdd->hasTable('error'));
    }

    public function testHasColumns(): void
    {
        self::assertTrue($this->bdd->hasColumn('test', 'id'));
        self::assertFalse($this->bdd->hasColumn('test', 'error'));
        self::assertFalse($this->bdd->hasColumn('error', 'id'));
        self::assertFalse($this->bdd->hasColumn('error', 'error'));
    }

    public function testSetIncrementsTableNotFoundException(): void
    {
        $this->expectException(TableNotFoundException::class);
        $this->expectExceptionMessage('The error table is missing.');
        $this->bdd->setIncrement('error', 1);
    }

    public function testSetIncrementsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Table test_second does not have an incremental value.');
        $this->bdd->setIncrement('test_second', 1);
    }

    public function testAlterTableAdd(): void
    {
        $this->bdd->alterTable('test', static function (TableAlter $table): void {
            $table->string('field_s_default')->valueDefault('foo');
            $table->string('field_s_null')->nullable();
            $table->string('field_s');
        });

        self::assertEquals(
            [
                'fields'     => [
                    'id'              => [ 'type' => 'increments' ],
                    'name'            => [ 'type' => 'string', 'length' => 255 ],
                    'firstname'       => [ 'type' => 'string', 'length' => 255 ],
                    'field_s_default' => [ 'type' => 'string', 'length' => 255, 'default' => 'foo' ],
                    'field_s_null'    => [ 'type' => 'string', 'length' => 255, 'nullable' => true ],
                    'field_s'         => [ 'type' => 'string', 'length' => 255 ]
                ],
                'increments' => 3
            ],
            $this->bdd->getTableSchema('test')->toArray()
        );
        self::assertEquals(
            [
                [
                    'id'              => 1,
                    'name'            => 'NOEL',
                    'firstname'       => 'Mathieu',
                    'field_s_default' => 'foo',
                    'field_s_null'    => null,
                    'field_s'         => ''
                ], [
                    'id'              => 2,
                    'name'            => 'DUPOND',
                    'firstname'       => 'Jean',
                    'field_s_default' => 'foo',
                    'field_s_null'    => null,
                    'field_s'         => ''
                ], [
                    'id'              => 3,
                    'name'            => 'MARTIN',
                    'firstname'       => 'Manon',
                    'field_s_default' => 'foo',
                    'field_s_null'    => null,
                    'field_s'         => ''
                ]
            ],
            $this->bdd->read('test')
        );
    }

    public function testAlterTableAddIncrement(): void
    {
        $this->bdd->alterTable('test_second', static function (TableAlter $table): void {
            $table->increments('id');
        });

        self::assertEquals(
            [
                'fields'     => [
                    'id'      => [ 'type' => 'increments' ],
                    'value_i' => [ 'type' => 'integer' ],
                    'value_s' => [ 'type' => 'string', 'length' => 255 ]
                ],
                'increments' => 3
            ],
            $this->bdd->getTableSchema('test_second')->toArray()
        );
        self::assertEquals(
            [
                [ 'id' => 1, 'value_i' => 10, 'value_s' => 'value1' ],
                [ 'id' => 2, 'value_i' => 20, 'value_s' => 'value2' ],
                [ 'id' => 3, 'value_i' => 30, 'value_s' => 'value3' ]
            ],
            $this->bdd->read('test_second')
        );

        $this->request->insertInto('test_second', [ 'value_i', 'value_s' ])
            ->values([ 40, 'value4' ])
            ->execute();

        self::assertEquals(
            [
                [ 'id' => 1, 'value_i' => 10, 'value_s' => 'value1' ],
                [ 'id' => 2, 'value_i' => 20, 'value_s' => 'value2' ],
                [ 'id' => 3, 'value_i' => 30, 'value_s' => 'value3' ],
                [ 'id' => 4, 'value_i' => 40, 'value_s' => 'value4' ]
            ],
            $this->bdd->read('test_second')
        );
    }

    public function testAlterTableRename(): void
    {
        $this->bdd->alterTable('test', static function (TableAlter $table): void {
            $table->renameColumn('name', '_name');
        });

        self::assertEquals(
            [
                'fields'     => [
                    'id'        => [ 'type' => 'increments' ],
                    '_name'     => [ 'type' => 'string', 'length' => 255 ],
                    'firstname' => [ 'type' => 'string', 'length' => 255 ]
                ],
                'increments' => 3
            ],
            $this->bdd->getTableSchema('test')->toArray()
        );
        self::assertEquals(
            [
                [
                    'id'        => 1,
                    '_name'     => 'NOEL',
                    'firstname' => 'Mathieu'
                ], [
                    'id'        => 2,
                    '_name'     => 'DUPOND',
                    'firstname' => 'Jean'
                ], [
                    'id'        => 3,
                    '_name'     => 'MARTIN',
                    'firstname' => 'Manon'
                ]
            ],
            $this->bdd->read('test')
        );
    }

    public function testAlterTableModify(): void
    {
        $this->bdd->alterTable('test_second', static function (TableAlter $table): void {
            $table->string('value_s')->nullable()->modify();
            $table->float('value_i')->valueDefault(1.0)->modify();
        });

        self::assertEquals(
            [
                'fields'     => [
                    'value_i' => [ 'type' => 'float', 'default' => 1.0 ],
                    'value_s' => [ 'type' => 'string', 'length' => 255, 'nullable' => true ]
                ],
                'increments' => null
            ],
            $this->bdd->getTableSchema('test_second')->toArray()
        );
        self::assertEquals(
            [
                [ 'value_i' => 1, 'value_s' => 'value1' ],
                [ 'value_i' => 1, 'value_s' => 'value2' ],
                [ 'value_i' => 1, 'value_s' => 'value3' ]
            ],
            $this->bdd->read('test_second')
        );
    }

    public function testAlterTableModifyIncrement(): void
    {
        $this->bdd->alterTable('test_second', static function (TableAlter $table): void {
            $table->increments('value_i')->modify();
        });

        self::assertEquals(
            [
                'fields'     => [
                    'value_i' => [ 'type' => 'increments' ],
                    'value_s' => [ 'type' => 'string', 'length' => 255 ]
                ],
                'increments' => 0
            ],
            $this->bdd->getTableSchema('test_second')->toArray()
        );
        self::assertEquals(
            [
                [ 'value_i' => 10, 'value_s' => 'value1' ],
                [ 'value_i' => 20, 'value_s' => 'value2' ],
                [ 'value_i' => 30, 'value_s' => 'value3' ]
            ],
            $this->bdd->read('test_second')
        );
    }

    public function testAlterTableDrop(): void
    {
        $this->bdd->alterTable('test', static function (TableAlter $table): void {
            $table->dropColumn('id');
            $table->dropColumn('firstname');
        });

        self::assertEquals(
            [
                'fields'     => [
                    'name' => [ 'type' => 'string', 'length' => 255 ]
                ],
                'increments' => null
            ],
            $this->bdd->getTableSchema('test')->toArray()
        );
        self::assertEquals(
            [
                [ 'name' => 'NOEL' ],
                [ 'name' => 'DUPOND' ],
                [ 'name' => 'MARTIN' ]
            ],
            $this->bdd->read('test')
        );
    }

    public function testAlterTableException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The error table is missing.');
        $this->bdd->alterTable('error', static function (): void {
        });
    }

    public function testAlterTableAddException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('id field does not exists in test table.');
        $this->bdd->alterTable('test', static function (TableAlter $table): void {
            $table->string('id');
        });
    }

    public function testAlterTableModifyException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('error field does not exists in test table.');
        $this->bdd->alterTable('test', static function (TableAlter $table): void {
            $table->string('error')->modify();
        });
    }

    public function testAlterTableModifyTypeIntegerException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The value_s column type string can not be changed with the integer type.');
        $this->bdd->alterTable('test_second', static function (TableAlter $table): void {
            $table->integer('value_s')->modify();
        });
    }

    public function testAlterTableModifyTypeStringException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The value_i column type integer can not be changed with the string type.');
        $this->bdd->alterTable('test_second', static function (TableAlter $table): void {
            $table->string('value_i')->modify();
        });
    }

    public function testAlterTableModifyColumnsValueException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The test table can not have multiple incremental values.');
        $this->bdd->alterTable('test', static function (TableAlter $table): void {
            $table->increments('name')->modify();
        });
    }

    public function testAlterTableRenameColumnsNotFoundException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('error field does not exists in test table.');
        $this->bdd->alterTable('test', static function (TableAlter $table): void {
            $table->renameColumn('error', 'error');
        });
    }

    public function testAlterTableRenameException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('name field does exists in test table.');
        $this->bdd->alterTable('test', static function (TableAlter $table): void {
            $table->renameColumn('name', 'id');
        });
    }

    public function testAlterTableDropException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('error field does not exists in test table.');
        $this->bdd->alterTable('test', static function (TableAlter $table): void {
            $table->dropColumn('error');
        });
    }

    public function testAlterTableAddIncrementsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The test table can not have multiple incremental values.');
        $this->bdd->alterTable('test', static function (TableAlter $table): void {
            $table->increments('error');
        });
    }

    public function testTruncateTable(): void
    {
        $output = $this->bdd->truncateTable('test');

        self::assertEquals(
            [
                'fields'     => [
                    'id'        => [ 'type' => 'increments' ],
                    'name'      => [ 'type' => 'string', 'length' => 255 ],
                    'firstname' => [ 'type' => 'string', 'length' => 255 ]
                ],
                'increments' => 0
            ],
            $this->bdd->getTableSchema('test')->toArray()
        );
        self::assertEquals([], $this->bdd->read('test'));
        self::assertTrue($output);
    }

    public function testTruncateTableException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The error table is missing.');
        $this->bdd->truncateTable('error');
    }

    public function testDropTable(): void
    {
        $output = $this->bdd->dropTable('test');

        self::assertTrue($output);
        self::assertFileNotExists(self::DATA_DIR . 'test.json');
    }

    public function testDropTableException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The test table is missing.');
        $this->bdd->dropTable('test');
        $this->bdd->dropTable('test');
    }

    public function testDropTableIfExists(): void
    {
        $this->bdd->dropTable('test');
        $output = $this->bdd->dropTableIfExists('test');

        self::assertFalse($output);
    }

    public function testDropSchema(): void
    {
        $this->bdd->dropSchema();
        self::assertFileNotExists(self::DATA_DIR . 'schema.json');
    }
}
