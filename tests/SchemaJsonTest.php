<?php

namespace Queryflatfile\Test;

use Queryflatfile\Driver\Json;
use Queryflatfile\Request;
use Queryflatfile\Schema;
use Queryflatfile\TableAlter;
use Queryflatfile\TableBuilder;

class SchemaJsonTest extends \PHPUnit\Framework\TestCase
{
    const DATA_DIR = 'data2';

    /**
     * @var Schema
     */
    protected $bdd;

    /**
     * @var Request
     */
    protected $request;

    protected function setUp()
    {
        $this->bdd = (new Schema)
            ->setConfig(self::DATA_DIR, 'schema', new Json());

        $this->request = new Request($this->bdd);

        $this->bdd->createTableIfNotExists('test', static function (TableBuilder $table) {
            $table->increments('id')
                ->string('name')
                ->string('firstname');
        });

        $this->request->insertInto('test', [ 'name', 'firstname' ])
            ->values([ 'NOEL', 'Mathieu' ])
            ->values([ 'DUPOND', 'Jean' ])
            ->values([ 'MARTIN', 'Manon' ])
            ->execute();

        $this->bdd->createTable('test_second', static function (TableBuilder $table) {
            $table->integer('value_i')
                ->string('value_s');
        });

        $this->request->insertInto('test_second', [ 'value_i', 'value_s' ])
            ->values([ 10, 'value1' ])
            ->values([ 20, 'value2' ])
            ->values([ 30, 'value3' ])
            ->execute();
    }

    protected function tearDown()
    {
        if (!file_exists(self::DATA_DIR)) {
            return;
        }
        $dir = new \DirectoryIterator(self::DATA_DIR);
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            unlink($fileInfo->getRealPath());
        }
        if (file_exists(self::DATA_DIR)) {
            rmdir(self::DATA_DIR);
        }
    }

    public function testGetSchema()
    {
        self::assertArraySubset($this->bdd->getSchema(), [
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
        ]);
    }

    public function testGetSchemaTable()
    {
        self::assertArraySubset($this->bdd->getSchemaTable('test'), [
            'fields'     => [
                'id'        => [ 'type' => 'increments' ],
                'name'      => [ 'type' => 'string', 'length' => 255 ],
                'firstname' => [ 'type' => 'string', 'length' => 255 ]
            ],
            'increments' => 3
        ]);
    }

    public function testHasTable()
    {
        self::assertTrue($this->bdd->hasTable('test'));
        self::assertFalse($this->bdd->hasTable('error'));
    }

    public function testHasColumns()
    {
        self::assertTrue($this->bdd->hasColumn('test', 'id'));
        self::assertFalse($this->bdd->hasColumn('test', 'error'));
        self::assertFalse($this->bdd->hasColumn('error', 'id'));
        self::assertFalse($this->bdd->hasColumn('error', 'error'));
    }

    /**
     * @expectedException \Exception
     */
    public function testSetIncrementsableNotFoundException()
    {
        $this->bdd->setIncrement('error', 1);
    }

    /**
     * @expectedException \Exception
     */
    public function testSetIncrementsException()
    {
        $this->bdd->setIncrement('test_void', 1);
    }

    public function testAlterTableAdd()
    {
        $this->bdd->alterTable('test', static function (TableAlter $table) {
            $table
                ->string('field_s_default')->valueDefault('foo')
                ->string('field_s_null')->nullable()
                ->string('field_s');
        });

        self::assertArraySubset($this->bdd->getSchemaTable('test')[ 'fields' ], [
            'id'              => [ 'type' => 'increments' ],
            'name'            => [ 'type' => 'string', 'length' => 255 ],
            'firstname'       => [ 'type' => 'string', 'length' => 255 ],
            'field_s_default' => [ 'type' => 'string', 'length' => 255, 'default' => 'foo' ],
            'field_s_null'    => [ 'type' => 'string', 'length' => 255, 'nullable' => true ],
            'field_s'         => [ 'type' => 'string', 'length' => 255 ]
        ]);
        self::assertArraySubset($this->bdd->read('test'), [
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
        ]);
    }

    public function testAlterTableAddIncrement()
    {
        $this->bdd->alterTable('test_second', static function (TableAlter $table) {
            $table->increments('id');
        });

        self::assertArraySubset($this->bdd->getSchemaTable('test_second'), [
            'fields'     => [
                'id'      => [ 'type' => 'increments' ],
                'value_i' => [ 'type' => 'integer' ],
                'value_s' => [ 'type' => 'string', 'length' => 255 ]
            ],
            'increments' => 4
        ]);
        self::assertArraySubset($this->bdd->read('test_second'), [
            [ 'id' => 1, 'value_i' => 10, 'value_s' => 'value1' ],
            [ 'id' => 2, 'value_i' => 20, 'value_s' => 'value2' ],
            [ 'id' => 3, 'value_i' => 30, 'value_s' => 'value3' ]
        ]);
    }

    public function testAlterTableRename()
    {
        $this->bdd->alterTable('test', static function (TableAlter $table) {
            $table->renameColumn('name', '_name');
        });

        self::assertArraySubset($this->bdd->getSchemaTable('test')[ 'fields' ], [
            'id'        => [ 'type' => 'increments' ],
            '_name'     => [ 'type' => 'string', 'length' => 255 ],
            'firstname' => [ 'type' => 'string', 'length' => 255 ]
        ]);
        self::assertArraySubset($this->bdd->read('test'), [
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
        ]);
    }

    public function testAlterTableModify()
    {
        $this->bdd->alterTable('test_second', static function (TableAlter $table) {
            $table->float('value_i')->valueDefault(1.0)->modify();
        });

        self::assertArraySubset($this->bdd->getSchemaTable('test_second')[ 'fields' ], [
            'value_i' => [ 'type' => 'float', 'default' => 1.0 ],
            'value_s' => [ 'type' => 'string', 'length' => 255 ]
        ]);
        self::assertArraySubset($this->bdd->read('test_second'), [
            [ 'id' => 1, 'value_i' => 1.0, 'value_s' => 'value1' ],
            [ 'id' => 2, 'value_i' => 1.0, 'value_s' => 'value2' ],
            [ 'id' => 3, 'value_i' => 1.0, 'value_s' => 'value3' ]
        ]);
    }

    public function testAlterTableModifyIncrement()
    {
        $this->bdd->alterTable('test_second', static function (TableAlter $table) {
            $table->increments('value_i')->modify();
        });

        self::assertArraySubset($this->bdd->getSchemaTable('test_second'), [
            'fields'     => [
                'value_i' => [ 'type' => 'increments' ],
                'value_s' => [ 'type' => 'string', 'length' => 255 ]
            ],
            'increments' => 0
        ]);
        self::assertArraySubset($this->bdd->read('test_second'), [
            [ 'value_i' => 10, 'value_s' => 'value1' ],
            [ 'value_i' => 20, 'value_s' => 'value2' ],
            [ 'value_i' => 30, 'value_s' => 'value3' ]
        ]);
    }

    public function testAlterTableDrop()
    {
        $this->bdd->alterTable('test', static function (TableAlter $table) {
            $table->dropColumn('id')
                ->dropColumn('firstname');
        });

        self::assertArraySubset($this->bdd->getSchemaTable('test'), [
            'fields'     => [
                'name' => [ 'type' => 'string', 'length' => 255 ]
            ],
            'increments' => null
        ]);
        self::assertArraySubset($this->bdd->read('test'), [
            [ 'name' => 'NOEL' ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MARTIN' ]
        ]);
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableException()
    {
        $this->bdd->alterTable('error', static function () {
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableAddException()
    {
        $this->bdd->alterTable('test', static function (TableAlter $table) {
            $table->string('id');
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableModifyException()
    {
        $this->bdd->alterTable('test', static function (TableAlter $table) {
            $table->string('error')->modify();
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableModifyTypeIntegerException()
    {
        $this->bdd->alterTable('test_second', static function (TableAlter $table) {
            $table->integer('value_s')->modify();
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableModifyTypeStringException()
    {
        $this->bdd->alterTable('test_second', static function (TableAlter $table) {
            $table->string('value_i')->modify();
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableModifyColumnsValueException()
    {
        $this->bdd->alterTable('test', static function (TableAlter $table) {
            $table->increments('name')->modify();
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableRenameColumnsNotFoundException()
    {
        $this->bdd->alterTable('test', static function (TableAlter $table) {
            $table->renameColumn('error', 'error');
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableRenameException()
    {
        $this->bdd->alterTable('test', static function (TableAlter $table) {
            $table->renameColumn('name', 'id');
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableDropException()
    {
        $this->bdd->alterTable('test', static function (TableAlter $table) {
            $table->dropColumn('error');
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableAddIncrementsException()
    {
        $this->bdd->alterTable('test', static function (TableAlter $table) {
            $table->increments('error');
        });
    }

    public function testTruncateTable()
    {
        $output = $this->bdd->truncateTable('test');

        self::assertArraySubset($this->bdd->getSchemaTable('test'), [
            'fields'     => [
                'id'        => [ 'type' => 'increments' ],
                'name'      => [ 'type' => 'string', 'length' => 255 ],
                'firstname' => [ 'type' => 'string', 'length' => 255 ]
            ],
            'increments' => 0
        ]);
        self::assertArraySubset($this->bdd->read('test'), []);
        self::assertTrue($output);
    }

    /**
     * @expectedException \Exception
     */
    public function testTruncateTableException()
    {
        $this->bdd->truncateTable('error');
    }

    public function testDropTable()
    {
        $output = $this->bdd->dropTable('test');

        self::assertTrue($output);
        self::assertFileNotExists(__DIR__ . '/data2/test.json');
    }

    /**
     * @expectedException \Exception
     */
    public function testDropTableException()
    {
        $this->bdd->dropTable('test');
        $this->bdd->dropTable('test');
    }

    public function testDropTableIfExists()
    {
        $this->bdd->dropTable('test');
        $output = $this->bdd->dropTableIfExists('test');

        self::assertFalse($output);
    }

    public function testDropSchema()
    {
        $this->bdd->dropSchema();
        self::assertFileNotExists(__DIR__ . '/data2/schema.json');
    }
}
