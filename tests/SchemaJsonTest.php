<?php

namespace Queryflatfile\Test;

use Queryflatfile\Driver\Json;
use Queryflatfile\Request;
use Queryflatfile\Schema;
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

    public static function tearDownAfterClass()
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

    protected function setUp()
    {
        $this->bdd = (new Schema)
            ->setConfig(self::DATA_DIR, 'schema', new Json());

        $this->request = new Request($this->bdd);
    }

    public function testCreateTable()
    {
        $this->bdd->createTable('test', static function (TableBuilder $table) {
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

        self::assertFileExists('data2/test.' . $this->bdd->getExtension());
    }

    public function testGetSchema()
    {
        self::assertArraySubset($this->bdd->getSchema(), [
            'test'      => [
                'fields'     => [
                    'id'        => [ 'type' => 'increments' ],
                    'name'      => [ 'type' => 'string', 'length' => 255 ],
                    'firstname' => [ 'type' => 'string', 'length' => 255 ]
                ],
                'increments' => 3
            ],
            'test_void' => [ 'fields' => null, 'increments' => null ]
        ]);
    }

    public function testGetSchemaTable()
    {
        self::assertArraySubset(
            $this->bdd->getSchemaTable('test'),
            [
            'fields'     => [
                'id'        => [ 'type' => 'increments' ],
                'name'      => [ 'type' => 'string', 'length' => 255 ],
                'firstname' => [ 'type' => 'string', 'length' => 255 ]
            ],
            'increments' => 3
        ]
        );
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
        $this->bdd->alterTable('test', static function (TableBuilder $table) {
            $table->string('addTest')->nullable();
        });
        $data = $this->bdd->read('test');

        self::assertArraySubset($this->bdd->getSchemaTable('test')[ 'fields' ], [
            'id'        => [ 'type' => 'increments' ],
            'name'      => [ 'type' => 'string', 'length' => 255 ],
            'firstname' => [ 'type' => 'string', 'length' => 255 ],
            'addTest'   => [ 'type' => 'string', 'length' => 255, 'nullable' => true ]
        ]);
        self::assertArraySubset($data, [
            [ 'id' => 1, 'name' => 'NOEL', 'firstname' => 'Mathieu', 'addTest' => null ],
            [ 'id' => 2, 'name' => 'DUPOND', 'firstname' => 'Jean', 'addTest' => null ],
            [ 'id' => 3, 'name' => 'MARTIN', 'firstname' => 'Manon', 'addTest' => null ]
        ]);
    }

    public function testAlterTableRename()
    {
        $this->bdd->alterTable('test', static function (TableBuilder $table) {
            $table->renameColumn('name', '_name');
        });

        self::assertArraySubset($this->bdd->getSchemaTable('test')[ 'fields' ], [
            'id'        => [ 'type' => 'increments' ],
            '_name'     => [ 'type' => 'string', 'length' => 255 ],
            'firstname' => [ 'type' => 'string', 'length' => 255 ],
            'addTest'   => [ 'type' => 'string', 'length' => 255, 'nullable' => true ]
        ]);
        self::assertArraySubset($this->bdd->read('test'), [
            [ 'id' => 1, '_name' => 'NOEL', 'firstname' => 'Mathieu', 'addTest' => null ],
            [ 'id' => 2, '_name' => 'DUPOND', 'firstname' => 'Jean', 'addTest' => null ],
            [ 'id' => 3, '_name' => 'MARTIN', 'firstname' => 'Manon', 'addTest' => null ]
        ]);
    }

    public function testAlterTableModify()
    {
        $this->bdd->alterTable('test', static function (TableBuilder $table) {
            $table->integer('firstname')->valueDefault(1)->modify();
        });

        self::assertArraySubset($this->bdd->getSchemaTable('test')[ 'fields' ], [
            'id'        => [ 'type' => 'increments' ],
            '_name'     => [ 'type' => 'string', 'length' => 255 ],
            'firstname' => [ 'type' => 'integer', 'default' => 1 ],
            'addTest'   => [ 'type' => 'string', 'length' => 255, 'nullable' => true ]
        ]);
        self::assertArraySubset($this->bdd->read('test'), [
            [ 'id' => 1, '_name' => 'NOEL', 'firstname' => 1, 'addTest' => null ],
            [ 'id' => 2, '_name' => 'DUPOND', 'firstname' => 1, 'addTest' => null ],
            [ 'id' => 3, '_name' => 'MARTIN', 'firstname' => 1, 'addTest' => null ]
        ]);
    }

    public function testAlterTableDrop()
    {
        $this->bdd->alterTable('test', static function (TableBuilder $table) {
            $table->dropColumn('firstname');
        });

        self::assertArraySubset($this->bdd->getSchemaTable('test')[ 'fields' ], [
            'id'      => [ 'type' => 'increments' ],
            '_name'   => [ 'type' => 'string', 'length' => 255 ],
            'addTest' => [ 'type' => 'string', 'length' => 255, 'nullable' => true ]
        ]);
        self::assertArraySubset($this->bdd->read('test'), [
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
        $this->bdd->alterTable('error', static function () {
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableAddException()
    {
        $this->bdd->alterTable('test', static function (TableBuilder $table) {
            $table->string('id');
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableModifyException()
    {
        $this->bdd->alterTable('test', static function (TableBuilder $table) {
            $table->string('error')->modify();
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableRenameException()
    {
        $this->bdd->alterTable('test', static function (TableBuilder $table) {
            $table->renameColumn('error', 'error');
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableDropException()
    {
        $this->bdd->alterTable('test', static function (TableBuilder $table) {
            $table->dropColumn('error');
        });
    }

    /**
     * @expectedException \Exception
     */
    public function testAlterTableAddIncrementsException()
    {
        $this->bdd->alterTable('test', static function (TableBuilder $table) {
            $table->increments('error');
        });
    }

    public function testTruncateTable()
    {
        $output = $this->bdd->truncateTable('test');

        self::assertArraySubset($this->bdd->getSchemaTable('test'), [
            'fields'     => [
                'id'      => [ 'type' => 'increments' ],
                'addTest' => [ 'type' => 'string', 'length' => 255, 'nullable' => true ],
                '_name'   => [ 'type' => 'string', 'length' => 255 ]
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
    }

    public function testDropTableIfExists()
    {
        $output = $this->bdd->dropTableIfExists('test');

        self::assertFalse($output);
    }

    public function testDropSchema()
    {
        $this->bdd->dropSchema();
        self::assertFileNotExists(__DIR__ . '/data2/schema.json');
    }
}
