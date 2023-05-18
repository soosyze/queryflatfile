<?php

namespace Soosyze\Queryflatfile\Tests\unit;

use Soosyze\Queryflatfile\Exception\Query\ColumnsNotFoundException;
use Soosyze\Queryflatfile\Exception\Query\TableNotFoundException;
use Soosyze\Queryflatfile\Request;
use Soosyze\Queryflatfile\Schema;
use Soosyze\Queryflatfile\TableBuilder;

/**
 * Description of RequestExecuteTest
 *
 * @author mnoel
 */
class RequestExecuteTest extends \PHPUnit\Framework\TestCase
{
    private const ROOT = __DIR__ . '/data/';

    protected Schema $bdd;

    protected Request $request;

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
            ->setConfig('data', 'schema', new \Soosyze\Queryflatfile\Driver\Json())
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
        $request1 = $this->request
            ->insertInto('user', [ 'id', 'name', 'firstname' ])
            ->values([ 0, 'NOEL', 'Mathieu' ])
            ->values([ 1, 'DUPOND', 'Jean' ]);

        self::assertEquals(
            'INSERT INTO user (id, name, firstname) VALUES' . PHP_EOL .
            '(0, \'NOEL\', \'Mathieu\'),' . PHP_EOL .
            '(1, \'DUPOND\', \'Jean\');',
            (string) $request1
        );
        $request1->execute();

        $request2 = $this->request
            ->insertInto('user', [ 'name', 'firstname' ])
            ->values([ 'MARTIN', 'Manon' ])
            ->values([ null, 'Marie' ])
            ->values([ 'DUPOND', 'Pierre' ]);

        self::assertEquals(
            'INSERT INTO user (name, firstname) VALUES' . PHP_EOL .
            '(\'MARTIN\', \'Manon\'),' . PHP_EOL .
            '(null, \'Marie\'),' . PHP_EOL .
            '(\'DUPOND\', \'Pierre\');',
            (string) $request2
        );
        $request2->execute();

        $request3 = $this->request
            ->insertInto('user', [ 'id', 'name', 'firstname' ])
            ->values([ 5, 'MEYER', 'Eva' ])
            ->values([ 6, 'ROBERT', null ]);

        self::assertEquals(
            'INSERT INTO user (id, name, firstname) VALUES' . PHP_EOL .
            '(5, \'MEYER\', \'Eva\'),' . PHP_EOL .
            '(6, \'ROBERT\', null);',
            (string) $request3
        );
        $request3->execute();

        $request4 = $this->request
            ->insertInto('role', [ 'id_role', 'labelle' ])
            ->values([ 0, 'Admin' ])
            ->values([ 1, 'Author' ])
            ->values([ 2, 'User' ]);

        self::assertEquals(
            'INSERT INTO role (id_role, labelle) VALUES' . PHP_EOL .
            '(0, \'Admin\'),' . PHP_EOL .
            '(1, \'Author\'),' . PHP_EOL .
            '(2, \'User\');',
            (string) $request4
        );
        $request4->execute();

        $request5 = $this->request->insertInto('user_role', [ 'id_user', 'id_role' ])
            ->values([ 0, 0 ])
            ->values([ 1, 0 ])
            ->values([ 2, 1 ])
            ->values([ 3, 1 ])
            ->values([ 4, 2 ])
            ->values([ 5, 2 ]);

        self::assertEquals(
            'INSERT INTO user_role (id_user, id_role) VALUES' . PHP_EOL .
            '(0, 0),' . PHP_EOL .
            '(1, 0),' . PHP_EOL .
            '(2, 1),' . PHP_EOL .
            '(3, 1),' . PHP_EOL .
            '(4, 2),' . PHP_EOL .
            '(5, 2);',
            (string) $request5
        );
        $request5->execute();

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
        $request = $this->request
            ->update('user', [ 'name' => 'PETIT', 'firstname' => 'Mathieu' ])
            ->where('id', '=', 0);

        self::assertEquals(
            'UPDATE user SET name = \'PETIT\', firstname = \'Mathieu\' WHERE id = 0;',
            (string) $request
        );
        $request->execute();

        $data = $this->request
            ->from('user')
            ->where('id', '=', 0)
            ->fetch();

        self::assertEquals([ 'id' => 0, 'name' => 'PETIT', 'firstname' => 'Mathieu' ], $data);
    }

    public function testUpdateDataFull(): void
    {
        $request = $this->request
            ->update('user', [ 'name' => 'PETIT' ]);

        self::assertEquals(
            'UPDATE user SET name = \'PETIT\';',
            (string) $request
        );
        $request->execute();

        $data = $this->request
            ->from('user')
            ->where('id', '=', 0)
            ->fetch();

        self::assertEquals([ 'id' => 0, 'name' => 'PETIT', 'firstname' => 'Mathieu' ], $data);
    }

    public function testDeleteData(): void
    {
        $request = $this->request
            ->from('user')
            ->delete()
            ->between('id', 1, 4);

        self::assertEquals(
            'DELETE user WHERE id BETWEEN 1 AND 4;',
            (string) $request
        );
        $request->execute();

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
