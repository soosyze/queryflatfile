<?php

namespace Queryflatfile\Test;

use Queryflatfile\Request;
use Queryflatfile\Schema;
use Queryflatfile\TableBuilder;

class SchemaJsonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Schema
     */
    protected $bdd;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->bdd     = new Schema();
        $this->bdd->setConfig('tests/data2', 'schema', new \Queryflatfile\Driver\Json());
        $this->request = new Request($this->bdd);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testCreateTable()
    {
        $this->bdd->createTable('test', function (TableBuilder $table) {
            $table->increments('id')
                ->string('name')
                ->string('firstname');
        });

        $this->request->insertInto('test', [ 'name', 'firstname' ])
            ->values([ 'NOEL', 'Mathieu' ])
            ->values([ 'DUPOND', 'Jean' ])
            ->values([ 'MARTIN', 'Manon' ])
            ->execute();

        $this->bdd->createTable('test_void');

        $this->assertFileExists('tests/data2/test.' . $this->bdd->getExtension());
    }

    public function testGetSchema()
    {
        $this->assertArraySubset(
            $this->bdd->getSchema(),
            [
            'test'      => [
                'table'      => 'test',
                'fields'     => [
                    'id'        => [
                        'type' => 'increments'
                    ],
                    'name'      => [
                        'type'   => 'string',
                        'length' => 255
                    ],
                    'firstname' => [
                        'type'   => 'string',
                        'length' => 255
                    ]
                ],
                'increments' => 3
            ],
            'test_void' => [
                'table'      => 'test_void',
                'path'       => 'tests/data2',
                'fields'     => null,
                'increments' => null
            ]
        ]
        );
    }

    public function testGetSchemaTable()
    {
        $this->assertArraySubset(
            $this->bdd->getSchemaTable('test'),
            [
            'table'      => 'test',
            'fields'     => [
                'id'        => [
                    'type' => 'increments'
                ],
                'name'      => [
                    'type'   => 'string',
                    'length' => 255
                ],
                'firstname' => [
                    'type'   => 'string',
                    'length' => 255
                ]
            ],
            'increments' => 3
        ]
        );
    }

    public function testHasTable()
    {
        $this->assertTrue($this->bdd->hasTable('test'));
        $this->assertFalse($this->bdd->hasTable('error'));
    }

    public function testHasColumns()
    {
        $this->assertTrue($this->bdd->hasColumn('test', 'id'));
        $this->assertFalse($this->bdd->hasColumn('test', 'error'));
        $this->assertFalse($this->bdd->hasColumn('error', 'id'));
        $this->assertFalse($this->bdd->hasColumn('error', 'error'));
    }

    /**
     * @expectedException \Exception
     */
    public function testSetIncrementsableNotFoundException()
    {
        $this->bdd->setIncrements('error', 1);
    }

    /**
     * @expectedException \Exception
     */
    public function testSetIncrementsException()
    {
        $this->bdd->setIncrements('test_void', 1);
    }

    public function testAlterTableAdd()
    {
        $this->bdd->alterTable('test', function (TableBuilder $table) {
            $table->string('addTest')->nullable();
        });

        $data = $this->bdd->read('tests/data2', 'test');

        $this->assertArraySubset(
            $this->bdd->getSchemaTable('test')[ 'fields' ],
            [
            'id'        => [
                'type' => 'increments'
            ],
            'name'      => [
                'type'   => 'string',
                'length' => 255
            ],
            'firstname' => [
                'type'   => 'string',
                'length' => 255
            ],
            'addTest'   => [
                'type'     => 'string',
                'length'   => 255,
                'nullable' => true
            ]
        ]
        );
        $this->assertArraySubset($data, [
            [ 'id' => 1, 'name' => 'NOEL', 'firstname' => 'Mathieu', 'addTest' => null ],
            [ 'id' => 2, 'name' => 'DUPOND', 'firstname' => 'Jean', 'addTest' => null ],
            [ 'id' => 3, 'name' => 'MARTIN', 'firstname' => 'Manon', 'addTest' => null ]
        ]);
    }

    public function testAlterTableRename()
    {
        $this->bdd->alterTable('test', function (TableBuilder $table) {
            $table->renameColumn('name', '_name');
        });

        $this->assertArraySubset(
            $this->bdd->getSchemaTable('test')[ 'fields' ],
            [
            'id'        => [
                'type' => 'increments'
            ],
            '_name'     => [
                'type'   => 'string',
                'length' => 255
            ],
            'firstname' => [
                'type'   => 'string',
                'length' => 255
            ],
            'addTest'   => [
                'type'     => 'string',
                'length'   => 255,
                'nullable' => true
            ]
        ]
        );
        $this->assertArraySubset($this->bdd->read('tests/data2', 'test'), [
            [ 'id' => 1, '_name' => 'NOEL', 'firstname' => 'Mathieu', 'addTest' => null ],
            [ 'id' => 2, '_name' => 'DUPOND', 'firstname' => 'Jean', 'addTest' => null ],
            [ 'id' => 3, '_name' => 'MARTIN', 'firstname' => 'Manon', 'addTest' => null ]
        ]);
    }

    public function testAlterTableModify()
    {
        $this->bdd->alterTable('test', function (TableBuilder $table) {
            $table->integer('firstname')->valueDefault(1)->modify();
        });

        $this->assertArraySubset(
            $this->bdd->getSchemaTable('test')[ 'fields' ],
            [
            'id'        => [
                'type' => 'increments'
            ],
            '_name'     => [
                'type'   => 'string',
                'length' => 255
            ],
            'firstname' => [
                'type'    => 'integer',
                'default' => 1
            ],
            'addTest'   => [
                'type'     => 'string',
                'length'   => 255,
                'nullable' => true
            ]
        ]
        );

        $this->assertArraySubset($this->bdd->read('tests/data2', 'test'), [
            [ 'id' => 1, '_name' => 'NOEL', 'firstname' => 1, 'addTest' => null ],
            [ 'id' => 2, '_name' => 'DUPOND', 'firstname' => 1, 'addTest' => null ],
            [ 'id' => 3, '_name' => 'MARTIN', 'firstname' => 1, 'addTest' => null ]
        ]);
    }

    public function testAlterTableDrop()
    {
        $this->bdd->alterTable('test', function (TableBuilder $table) {
            $table->dropColumn('firstname');
        });

        $this->assertArraySubset(
            $this->bdd->getSchemaTable('test')[ 'fields' ],
            [
            'id'      => [
                'type' => 'increments'
            ],
            '_name'   => [
                'type'   => 'string',
                'length' => 255
            ],
            'addTest' => [
                'type'     => 'string',
                'length'   => 255,
                'nullable' => true
            ]
        ]
        );

        $this->assertArraySubset($this->bdd->read('tests/data2', 'test'), [
            [ 'id' => 1, '_name' => 'NOEL', 'addTest' => null ],
            [ 'id' => 2, '_name' => 'DUPOND', 'addTest' => null ],
            [ 'id' => 3, '_name' => 'MARTIN', 'addTest' => null ]
        ]);
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableException()
    {
        $this->bdd->alterTable('error', function () {
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableAddException()
    {
        $this->bdd->alterTable('test', function (TableBuilder $table) {
            $table->string('id');
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableModifyException()
    {
        $this->bdd->alterTable('test', function (TableBuilder $table) {
            $table->string('error')->modify();
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableRenameException()
    {
        $this->bdd->alterTable('test', function (TableBuilder $table) {
            $table->renameColumn('error', 'error');
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableDropException()
    {
        $this->bdd->alterTable('test', function (TableBuilder $table) {
            $table->dropColumn('error');
        });
    }
    
    /**
     * @expectedException \Exception
     */
    public function testAlterTableAddIncrementsException()
    {
        $this->bdd->alterTable('test', function (TableBuilder $table) {
            $table->increments('error');
        });
    }

    public function testTruncateTable()
    {
        $output = $this->bdd->truncateTable('test');

        $this->assertArraySubset(
            $this->bdd->getSchemaTable('test'),
            [
            'table'      => 'test',
            'path'       => 'tests/data2',
            'fields'     => [
                'id'      => [
                    'type' => 'increments'
                ],
                'addTest' => [
                    'type'     => 'string',
                    'length'   => 255,
                    'nullable' => true
                ],
                '_name'   => [
                    'type'   => 'string',
                    'length' => 255
                ]
            ],
            'increments' => 0
        ]
        );
        $this->assertArraySubset($this->bdd->read('tests/data2', 'test'), []);
        $this->assertTrue($output);
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

        $this->assertTrue($output);
        $this->assertFileNotExists('test/data2/test.json');
    }

    /**
     * @expectedException \Exception
     */
    public function testDropTableException()
    {
        $this->bdd->dropTable('test');
    }

    public function testDropTableIfExists()
    {
        $output = $this->bdd->dropTableIfExists('test');

        $this->assertFalse($output);
    }

    public function testDropSchema()
    {
        $this->bdd->dropSchema();
        $this->assertFileNotExists('test/data2/schema.json');
    }
}
