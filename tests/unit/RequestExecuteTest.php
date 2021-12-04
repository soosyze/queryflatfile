<?php

namespace Queryflatfile\Tests\unit;

use Queryflatfile\Exception\Query\ColumnsNotFoundException;
use Queryflatfile\Exception\Query\TableNotFoundException;
use Queryflatfile\Request;
use Queryflatfile\Schema;
use Queryflatfile\TableBuilder;

/**
 * Description of RequestExecuteTest
 *
 * @author mnoel
 */
class RequestExecuteTest extends \PHPUnit\Framework\TestCase
{
    private const ROOT = __DIR__ . '/data/';

    /**
     * @var Schema
     */
    protected $bdd;

    /**
     * @var Request
     */
    protected $request;

    public static function tearDownAfterClass(): void
    {
        if (!file_exists(self::ROOT)) {
            return;
        }
        $dir = new \DirectoryIterator(self::ROOT);
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->getRealPath() === false) {
                continue;
            }
            unlink($fileInfo->getRealPath());
        }
        if (file_exists(self::ROOT)) {
            rmdir(self::ROOT);
        }
    }

    protected function setUp(): void
    {
        $this->bdd = (new Schema)
            ->setConfig('data', 'schema', new \Queryflatfile\Driver\Json())
            ->setPathRoot(__DIR__ . '/');

        $this->request = new Request($this->bdd);
    }

    public function testCreateTable(): void
    {
        $this->bdd->createTable('user', static function (TableBuilder $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('firstname')->nullable();
        });
        $this->bdd->createTable('user_role', static function (TableBuilder $table): void {
            $table->integer('id_user');
            $table->integer('id_role');
        });
        $this->bdd->createTable('role', static function (TableBuilder $table): void {
            $table->increments('id_role');
            $table->string('labelle');
        });

        self::assertFileExists(self::ROOT . 'user.' . $this->bdd->getExtension());
        self::assertFileExists(self::ROOT . 'user_role.' . $this->bdd->getExtension());
        self::assertFileExists(self::ROOT . 'role.' . $this->bdd->getExtension());
    }

    public function testCreateTableException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Table user exist.');
        $this->bdd->createTable('user');
    }

    public function testCreateTableIfNotExists(): void
    {
        $this->bdd->createTableIfNotExists('user');

        self::assertFileExists(self::ROOT . 'user.' . $this->bdd->getExtension());
    }

    public function testInsertInto(): void
    {
        $this->request->insertInto('user', [ 'id', 'name', 'firstname' ])
            ->values([ 0, 'NOEL', 'Mathieu' ])
            ->values([ 1, 'DUPOND', 'Jean' ])
            ->execute();

        $this->request->insertInto('user', [ 'name', 'firstname' ])
            ->values([ 'MARTIN', 'Manon' ])
            ->values([ null, 'Marie' ])
            ->values([ 'DUPOND', 'Pierre' ])
            ->execute();

        $this->request->insertInto('user', [ 'id', 'name', 'firstname' ])
            ->values([ 5, 'MEYER', 'Eva' ])
            ->values([ 6, 'ROBERT', null ])
            ->execute();

        $this->request->insertInto('role', [ 'id_role', 'labelle' ])
            ->values([ 0, 'Admin' ])
            ->values([ 1, 'Author' ])
            ->values([ 2, 'User' ])
            ->execute();

        $this->request->insertInto('user_role', [ 'id_user', 'id_role' ])
            ->values([ 0, 0 ])
            ->values([ 1, 0 ])
            ->values([ 2, 1 ])
            ->values([ 3, 1 ])
            ->values([ 4, 2 ])
            ->values([ 5, 2 ])
            ->execute();

        self::assertFileExists(self::ROOT . 'user.' . $this->bdd->getExtension());
    }

    public function testGetIncrement(): void
    {
        self::assertEquals(6, $this->bdd->getIncrement('user'));
        self::assertEquals(2, $this->bdd->getIncrement('role'));
    }

    public function testGetIncrementNoFound(): void
    {
        $this->expectException(TableNotFoundException::class);
        $this->expectExceptionMessage('The error table is missing.');
        $this->bdd->getIncrement('error');
    }

    public function testGetIncrementNoExist(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Table user_role does not have an incremental value.');
        $this->bdd->getIncrement('user_role');
    }

    public function testCreateTableIfNotExistsData(): void
    {
        $this->bdd->createTableIfNotExists('user');

        self::assertFileExists(self::ROOT . 'user.' . $this->bdd->getExtension());
    }

    public function testInsertIntoExceptionTable(): void
    {
        $this->expectException(TableNotFoundException::class);
        $this->expectExceptionMessage('The foo table is missing.');
        $this->request->insertInto('foo', [ 'id', 'name', 'firstname' ])->execute();
    }

    public function testInsertIntoExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage('The number of fields in the selections are different:  != 0, NOEL');
        $this->request->insertInto('user', [])->values([ 0, 'NOEL' ])->execute();
    }

    public function testInsertIntoExceptionValue(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage('The number of fields in the selections are different: id, name, firstname != 0, NOEL');
        $this->request->insertInto('user', [ 'id', 'name', 'firstname' ])
            ->values([ 0, 'NOEL' ])
            ->execute();
    }

    public function testUpdateData(): void
    {
        $this->request
            ->update('user', [ 'name' => 'PETIT' ])
            ->where('id', '=', 0)
            ->execute();

        $data = $this->request
            ->from('user')
            ->where('id', '=', 0)
            ->fetch();

        self::assertEquals([ 'id' => 0, 'name' => 'PETIT', 'firstname' => 'Mathieu' ], $data);
    }

    public function testUpdateDataFull(): void
    {
        $this->request
            ->update('user', [ 'name' => 'PETIT' ])
            ->execute();

        $data = $this->request
            ->from('user')
            ->where('id', '=', 0)
            ->fetch();

        self::assertEquals([ 'id' => 0, 'name' => 'PETIT', 'firstname' => 'Mathieu' ], $data);
    }

    public function testDeleteData(): void
    {
        $this->request->from('user')
            ->delete()
            ->between('id', 1, 4)
            ->execute();

        $data = $this->request
            ->from('user')
            ->fetchAll();

        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'PETIT', 'firstname' => 'Mathieu' ],
                [ 'id' => 5, 'name' => 'PETIT', 'firstname' => 'Eva' ],
                [ 'id' => 6, 'name' => 'PETIT', 'firstname' => null ]
            ],
            $data
        );
    }

    public function testDropTable(): void
    {
        $this->bdd->dropTable('user_role');

        self::assertFileNotExists(self::ROOT . 'user_role.json');
    }

    public function testDropSchema(): void
    {
        $this->bdd->dropSchema();

        self::assertFileNotExists(self::ROOT . 'schema.json');
    }
}
