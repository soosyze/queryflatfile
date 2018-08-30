<?php

namespace Queryflatfile\Test;

use Queryflatfile\Schema;
use Queryflatfile\Request;
use Queryflatfile\TableBuilder;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Schema
     */
    protected $bdd;

    /**
     *
     * @var Request
     */
    protected $request;

    /**
     *
     * @var Request
     */
    protected $request2;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->bdd = new Schema();
        $this->bdd->setConfig('tests/data', 'schema', new \Queryflatfile\Driver\Json());

        $this->request  = new Request($this->bdd);
        $this->request2 = new Request($this->bdd);
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
        $this->bdd->createTable('user', function (TableBuilder $table) {
            $table->increments('id')
                ->string('name')
                ->string('firstname')->nullable();
        });
        $this->bdd->createTable('user_role', function (TableBuilder $table) {
            $table->integer('id_user')
                ->integer('id_role');
        });
        $this->bdd->createTable('role', function (TableBuilder $table) {
            $table->increments('id')
                ->string('labelle');
        });

        $this->assertFileExists('tests/data/user.' . $this->bdd->getExtension());
        $this->assertFileExists('tests/data/user_role.' . $this->bdd->getExtension());
        $this->assertFileExists('tests/data/role.' . $this->bdd->getExtension());
    }

    /**
     * @expectedException Exception
     */
    public function testCreateTableException()
    {
        $this->bdd->createTable('user');
    }

    public function testCreateTableIfNotExists()
    {
        $this->bdd->createTableIfNotExists('user');

        $this->assertFileExists('tests/data/user.' . $this->bdd->getExtension());
    }

    public function testInsertInto()
    {
        $this->request->insertInto('user', [ 'id', 'name', 'firstname' ])
            ->values([ 0, 'NOEL', 'Mathieu' ])
            ->values([ 1, 'DUPOND', 'Jean' ])
            ->execute();

        $this->request->insertInto('user', [ 'name', 'firstname' ])
            ->values([ 'MARTIN', 'Manon' ])
            ->values([ 'PETIT', 'Marie' ])
            ->values([ 'DUPOND', 'Pierre' ])
            ->execute();

        $this->request->insertInto('user', [ 'id', 'name', 'firstname' ])
            ->values([ 5, 'MEYER', 'Eva' ])
            ->values([ 6, 'ROBERT', null ])
            ->execute();

        $this->request->insertInto('role', [ 'id', 'labelle' ])
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

        $this->assertFileExists('tests/data/user.' . $this->bdd->getExtension());
    }

    public function testCreateTableIfNotExistsData()
    {
        $this->bdd->createTableIfNotExists('user', function (TableBuilder $table) {
        });

        $this->assertFileExists('tests/data/user.' . $this->bdd->getExtension());
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\TableNotFoundException
     */
    public function testInsertIntoExceptionTable()
    {
        $this->request->insertInto('foo', [ 'id', 'name', 'firstname' ])->execute();
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testInsertIntoExceptionColumn()
    {
        $this->request->insertInto('user', [])->values([ 0, 'NOEL' ])->execute();
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testInsertIntoExceptionValue()
    {
        $this->request->insertInto('user', [ 'id', 'name', 'firstname' ])
            ->values([ 0, 'NOEL' ])
            ->execute();
    }

    public function testSelect()
    {
        $data1 = $this->request->select([ 'firstname' ])->from('user')->fetch();
        $data2 = $this->request->select('firstname')->from('user')->fetch();

        $this->assertArraySubset($data1, [ 'firstname' => 'Mathieu' ]);
        $this->assertArraySubset($data2, [ 'firstname' => 'Mathieu' ]);
    }

    public function testLists()
    {
        $data1 = $this->request->select('firstname')->from('user')->lists();
        $data2 = $this->request->select('firstname', 'name')->from('user')->lists();
        $data3 = $this->request->from('user')->lists('firstname');

        $assert = [ 'Mathieu', 'Jean', 'Manon', 'Marie', 'Pierre', 'Eva', null ];

        $this->assertArraySubset($data1, $assert);
        $this->assertArraySubset($data2, $assert);
        $this->assertArraySubset($data3, $assert);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testListsException()
    {
        $this->request->from('user')->lists();
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testSelectExceptionValue()
    {
        $this->request->select([ 'foo' ])->from('user')->fetch();
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\TableNotFoundException
     */
    public function testSelectExceptionFrom()
    {
        $this->request->select([ 'firstname' ])->from('foo')->fetch();
    }

    public function testSelectAlternative()
    {
        $data1 = $this->request->select()->from('user')->fetch();
        $data2 = $this->request->from('user')->fetch();

        $this->assertArraySubset($data1, [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]);
        $this->assertArraySubset($data2, [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\TableNotFoundException
     */
    public function testSelectAlternativeExceptionFrom()
    {
        $this->request->from('foo')->fetch();
    }

    public function testWhereEquals()
    {
        $data1 = $this->request->select('name')
            ->from('user')
            ->where('id', '=', '1')
            ->fetch();

        $data2 = $this->request->select('name')
            ->from('user')
            ->where('id', '===', '1')
            ->fetch();

        $data3 = $this->request->select('name')
            ->from('user')
            ->where('id', '=', 1)
            ->fetch();

        $data4 = $this->request->select('name')
            ->from('user')
            ->where('id', '===', 1)
            ->fetch();

        $this->assertArraySubset($data1, []);
        $this->assertArraySubset($data2, []);
        $this->assertArraySubset($data3, [ 'name' => 'DUPOND' ]);
        $this->assertArraySubset($data4, [ 'name' => 'DUPOND' ]);
    }

    public function testWhereEqualsAlternative()
    {
        $data = $this->request->select([ 'name' ])
            ->from('user')
            ->where('firstname', 'Jean')
            ->fetch();

        $this->assertArraySubset($data, [ 'name' => 'DUPOND' ]);
    }

    public function testWhereNotEqualsNoType()
    {
        $data1 = $this->request->select('firstname')
            ->from('user')
            ->where('id', '<>', '1')
            ->fetchAll();

        $data2 = $this->request->select('firstname')
            ->from('user')
            ->where('id', '<>', 1)
            ->fetchAll();

        $data3 = $this->request->select('firstname')
            ->from('user')
            ->where('id', '!=', '1')
            ->fetchAll();

        $data4 = $this->request->select('firstname')
            ->from('user')
            ->where('id', '!=', 1)
            ->fetchAll();

        $result_notType = [
            [ 'firstname' => 'Mathieu' ],
            [ 'firstname' => 'Manon' ],
            [ 'firstname' => 'Marie' ],
            [ 'firstname' => 'Pierre' ],
            [ 'firstname' => 'Eva' ],
            [ 'firstname' => null ]
        ];

        /* whereNotEquals sans prendre en compte le type */
        $this->assertArraySubset($data1, $result_notType);
        $this->assertArraySubset($data2, $result_notType);
        $this->assertArraySubset($data3, $result_notType);
        $this->assertArraySubset($data4, $result_notType);
    }

    public function testWhereNotEqualsType()
    {
        /* L'identifiant stocké est un integer, pas un string */
        $dataType1 = $this->request->select('firstname')
            ->from('user')
            ->where('id', '!==', '1')
            ->fetchAll();

        $dataType2 = $this->request->select('firstname')
            ->from('user')
            ->where('id', '!==', 1)
            ->fetchAll();

        $this->assertArraySubset($dataType1, [
            [ 'firstname' => 'Mathieu' ],
            [ 'firstname' => 'Jean' ],
            [ 'firstname' => 'Manon' ],
            [ 'firstname' => 'Marie' ],
            [ 'firstname' => 'Pierre' ],
            [ 'firstname' => 'Eva' ],
            [ 'firstname' => null ]
        ]);
        $this->assertArraySubset($dataType2, [
            [ 'firstname' => 'Mathieu' ],
            [ 'firstname' => 'Manon' ],
            [ 'firstname' => 'Marie' ],
            [ 'firstname' => 'Pierre' ],
            [ 'firstname' => 'Eva' ],
            [ 'firstname' => null ]
        ]);
    }

    public function testWhereNot()
    {
        $data = $this->request->select('id', 'firstname')
            ->from('user')
            ->notWhere('id', '>', 1)
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'firstname' => 'Jean' ]
        ]);
    }

    public function testWhereLess()
    {
        $data = $this->request
            ->from('user')
            ->where('id', '<', 1)
            ->fetch();

        $this->assertArraySubset($data, [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]);
    }

    public function testWhereLessOrEquals()
    {
        $data = $this->request
            ->from('user')
            ->where('id', '<=', 1)
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ]
        ]);
    }

    public function testWhereGreater()
    {
        $data = $this->request
            ->from('user')
            ->where('id', '>', 5)
            ->fetch();

        $this->assertArraySubset($data, [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]);
    }

    public function testWhereGreaterOrEquals()
    {
        $data = $this->request
            ->from('user')
            ->where('id', '>=', 5)
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testWhereEqualsExceptionColumn()
    {
        $this->request->select([ 'name' ])
            ->from('user')
            ->where('foo', '=', 'Jean')
            ->fetch();
    }

    public function testWhereBetween()
    {
        $data = $this->request
            ->from('user')
            ->between('id', 1, 2)
            ->fetch();

        $this->assertArraySubset($data, [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testWhereBetweenExceptionColumn()
    {
        $this->request
            ->from('user')
            ->between('foo', 0, 2)
            ->fetch();
    }

    public function testWhereIn()
    {
        $data = $this->request
            ->from('user')
            ->in('id', [ 0, 1 ])
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ]
        ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testWhereInExceptionColumn()
    {
        $this->request
            ->from('user')
            ->in('foo', [ 0, 1 ])
            ->fetchAll();
    }

    public function testWhereIsNull()
    {
        $data = $this->request
            ->from('user')
            ->isNull('firstname')
            ->fetch();

        $this->assertArraySubset($data, [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testWhereIsNullExceptionColumn()
    {
        $this->request
            ->from('user')
            ->isNull('foo')
            ->fetch();
    }

    public function testWhereLike()
    {
        $data1 = $this->request->from('user')->where('name', 'like', 'DUP%')->fetchAll();
        $data2 = $this->request->from('user')->where('name', 'like', '%OND')->fetchAll();
        $data3 = $this->request->from('user')->where('name', 'like', '%TI%')->fetchAll();

        $this->assertArraySubset($data1, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
        $this->assertArraySubset($data2, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
        $this->assertArraySubset($data3, [
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 3, 'name' => 'PETIT', 'firstname' => 'Marie' ]
        ]);
    }

    public function testWhereIlike()
    {
        $data1 = $this->request->from('user')->where('name', 'ilike', 'Dup%')->fetchAll();
        $data2 = $this->request->from('user')->where('name', 'ilike', '%OnD')->fetchAll();
        $data3 = $this->request->from('user')->where('name', 'ilike', '%ti%')->fetchAll();

        $this->assertArraySubset($data1, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
        $this->assertArraySubset($data2, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
        $this->assertArraySubset($data3, [
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 3, 'name' => 'PETIT', 'firstname' => 'Marie' ]
        ]);
    }

    public function testWhereNotLike()
    {
        $data1 = $this->request->from('user')->where('name', 'not like', 'DUP%')->fetch();
        $data2 = $this->request->from('user')->where('name', 'not like', '%OND')->fetch();
        $data3 = $this->request->from('user')->where('name', 'not like', '%E%')->fetchAll();

        $this->assertArraySubset($data1, [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]);
        $this->assertArraySubset($data2, [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]);
        $this->assertArraySubset($data3, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
    }

    public function testWhereNotIlike()
    {
        $data1 = $this->request->from('user')->where('name', 'not ilike', 'D%')->fetch();
        $data2 = $this->request->from('user')->where('name', 'not ilike', '%D')->fetch();
        $data3 = $this->request->from('user')->where('name', 'not ilike', '%E%')->fetchAll();

        $this->assertArraySubset($data1, [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]);
        $this->assertArraySubset($data2, [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]);
        $this->assertArraySubset($data3, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
    }

    public function testWhereRegex()
    {
        $data = $this->request
            ->from('user')
            ->regex('name', '/^D/')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ] ]);
    }

    public function testWhereOrRegex()
    {
        $data = $this->request
            ->from('user')
            ->regex('name', '/^D/')
            ->orRegex('name', '/^N/')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ] ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testWhereRegexExceptionColumns()
    {
        $this->request
            ->from('user')
            ->regex('foo', '/^D/')
            ->fetch();
    }

    public function testAndWhere()
    {
        $data = $this->request
            ->from('user')
            ->where('name', 'DUPOND')
            ->where('firstname', 'Pierre')
            ->fetch();

        $this->assertArraySubset($data, [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]);
    }

    public function testOrWhere()
    {
        $data = $this->request
            ->from('user')
            ->where('name', 'DUPOND')
            ->orWhere('firstname', 'Mathieu')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
    }

    public function testWhereAndGroup()
    {
        $data = $this->request
            ->from('user')
            ->where('id', '>=', 2)
            ->where(function ($query) {
                $query->where('name', 'DUPOND')
                ->orWhere('firstname', 'Eva');
            })
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ]
        ]);
    }

    public function testWhereOrGroup()
    {
        $data = $this->request
            ->from('user')
            ->where('name', 'DUPOND')
            ->orWhere(function ($query) {
                $query->where('firstname', 'Eva')
                ->orWhere('firstname', 'Mathieu');
            })
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ]
        ]);
    }

    public function testLimit()
    {
        $data = $this->request
            ->from('user')
            ->limit(1)
            ->fetchAll();

        $this->assertArraySubset($data, [ [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ] ]);
    }

    public function testLimitOffset()
    {
        $data = $this->request
            ->from('user')
            ->limit(1, 1)
            ->fetchAll();

        $this->assertArraySubset($data, [ [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ] ]);
    }

    public function testOrderByAsc()
    {
        $data = $this->request->select([ 'firstname' ])
            ->from('user')
            ->orderBy('firstname')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'firstname' => null ],
            [ 'firstname' => 'Eva' ],
            [ 'firstname' => 'Jean' ],
            [ 'firstname' => 'Manon' ],
            [ 'firstname' => 'Marie' ],
            [ 'firstname' => 'Mathieu' ],
            [ 'firstname' => 'Pierre' ]
        ]);
    }

    public function testOrderByDesc()
    {
        $data = $this->request->select([ 'firstname' ])
            ->from('user')
            ->orderBy('firstname', 'desc')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'firstname' => 'Pierre' ],
            [ 'firstname' => 'Mathieu' ],
            [ 'firstname' => 'Marie' ],
            [ 'firstname' => 'Manon' ],
            [ 'firstname' => 'Jean' ],
            [ 'firstname' => 'Eva' ],
            [ 'firstname' => null ]
        ]);
    }

    public function testOrderByMultipleAsc()
    {
        $data = $this->request->select([ 'name', 'firstname' ])
            ->from('user')
            ->orderBy('name', 'desc')
            ->orderBy('firstname')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'name' => 'ROBERT', 'firstname' => null ],
            [ 'name' => 'PETIT', 'firstname' => 'Marie' ],
            [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
    }

    public function testOrderByMultipleDesc()
    {
        $data = $this->request->select([ 'name', 'firstname' ])
            ->from('user')
            ->orderBy('name', 'desc')
            ->orderBy('firstname', 'desc')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'name' => 'ROBERT', 'firstname' => null ],
            [ 'name' => 'PETIT', 'firstname' => 'Marie' ],
            [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Jean', ]
        ]);
    }

    public function testRightJoin()
    {
        $data = $this->request->select('id', 'name', 'firstname', 'labelle')
            ->from('user')
            ->rightJoin('user_role', 'id', '=', 'user_role.id_user')
            ->rightJoin('role', 'id_role', '=', 'role.id')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu', 'labelle' => 'Admin' ],
            [ 'id' => 0, 'name' => 'DUPOND', 'firstname' => 'Jean', 'labelle' => 'Admin' ],
            [ 'id' => 1, 'name' => 'MARTIN', 'firstname' => 'Manon', 'labelle' => 'Author' ],
            [ 'id' => 1, 'name' => 'PETIT', 'firstname' => 'Marie', 'labelle' => 'Author' ],
            [ 'id' => 2, 'name' => 'DUPOND', 'firstname' => 'Pierre', 'labelle' => 'User' ],
            [ 'id' => 2, 'name' => 'MEYER', 'firstname' => 'Eva', 'labelle' => 'User' ]
        ]);
    }

    public function testLeftJoin()
    {
        $data = $this->request->select('id', 'name', 'firstname', 'labelle')
            ->from('user')
            ->leftJoin('user_role', 'id', '=', 'user_role.id_user')
            ->leftJoin('role', 'id_role', '=', 'role.id')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu', 'labelle' => 'Admin' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean', 'labelle' => 'Admin' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon', 'labelle' => 'Author' ],
            [ 'id' => 3, 'name' => 'PETIT', 'firstname' => 'Marie', 'labelle' => 'Author' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre', 'labelle' => 'User' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva', 'labelle' => 'User' ],
            /* Pas de corespondance pour ROBERT donc le reste des colonnes en null */
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null, 'labelle' => null ],
        ]);
    }

    public function testLeftJoinWhere()
    {
        $data = $this->request->select('id', 'name', 'firstname')
            ->from('user')
            ->leftJoin('user_role', 'id', '=', 'user_role.id_user')
            ->leftJoin('role', 'id_role', '=', 'role.id')
            ->where('labelle', 'Admin')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
        ]);
    }

    public function testLeftJoinGroup()
    {
        $data = $this->request->select('id', 'name', 'firstname')
            ->from('user')
            ->leftJoin('user_role', function ($query) {
                $query->where('id', '=', 'user_role.id_user');
            })
            ->leftJoin('role', 'id_role', '=', 'role.id')
            ->where('labelle', 'Admin')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
        ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testLeftJoinExceptionColumn()
    {
        $this->request->select([ 'id', 'name', 'firstname' ])
            ->from('user')
            ->leftJoin('user_role', 'foo', '==', 'user_role.id_user')
            ->leftJoin('role', 'id_role', '==', 'role.id')
            ->where('labelle', 'Admin')
            ->fetch();
    }

    public function testUnion()
    {
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->request2
            ->select('name')
            ->from('user')
            ->union($union)
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'name' => 'NOEL' ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MARTIN' ],
            [ 'name' => 'PETIT' ],
            [ 'name' => 'MEYER' ],
            [ 'name' => 'ROBERT' ]
        ]);
    }

    public function testUnionMultiple()
    {
        $union = $this->request
            ->select('name', 'firstname')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->request2
            ->select('name', 'firstname')
            ->from('user')
            ->union($union)
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'name' => 'PETIT', 'firstname' => 'Marie' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testUnionMultipleException()
    {
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $this->request2
            ->select('name', 'firstname')
            ->from('user')
            ->union($union)
            ->fetchAll();
    }

    public function testUnionAll()
    {
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->request2
            ->select('name')
            ->from('user')
            ->unionAll($union)
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'name' => 'NOEL' ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MARTIN' ],
            [ 'name' => 'PETIT' ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MEYER' ],
            [ 'name' => 'ROBERT' ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MARTIN' ],
            [ 'name' => 'PETIT' ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MEYER' ]
        ]);
    }

    public function testUnionAllMultiple()
    {
        $union = $this->request
            ->select('name', 'firstname')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->request2
            ->select('name', 'firstname')
            ->from('user')
            ->unionAll($union)
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'name' => 'PETIT', 'firstname' => 'Marie' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'name' => 'ROBERT', 'firstname' => null ],
            [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'name' => 'PETIT', 'firstname' => 'Marie' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'name' => 'MEYER', 'firstname' => 'Eva' ]
        ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\ColumnsNotFoundException
     */
    public function testUnionAllMultipleException()
    {
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $this->request2
            ->select('name', 'firstname')
            ->from('user')
            ->unionAll($union)
            ->fetchAll();
    }

    public function testUnionList()
    {
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->request2
            ->select('name')
            ->from('user')
            ->union($union)
            ->lists();

        $this->assertArraySubset($data, [
            'NOEL',
            'DUPOND',
            'MARTIN',
            'PETIT',
            'MEYER',
            'ROBERT'
        ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Query\BadFunctionException
     */
    public function testExecuteException()
    {
        $this->request
            ->from('user')
            ->where('id', '=', 0)
            ->execute();
    }

    public function testUpdateData()
    {
        $this->request->update('user', [ 'name' => 'PETIT' ])
            ->where('id', '=', 0)
            ->execute();

        $data = $this->request
            ->from('user')
            ->where('id', '=', 0)
            ->fetch();

        $this->assertArraySubset($data, [ 'id' => 0, 'name' => 'PETIT', 'firstname' => 'Mathieu' ]);
    }

    public function testUpdateDataFull()
    {
        $this->request->update('user', [ 'name' => 'PETIT' ])
            ->execute();

        $data = $this->request
            ->from('user')
            ->where('id', '=', 0)
            ->fetch();

        $this->assertArraySubset($data, [ 'id' => 0, 'name' => 'PETIT', 'firstname' => 'Mathieu' ]);
    }

    public function testDeleteData()
    {
        $this->request->from('user')
            ->delete()
            ->between('id', 1, 4)
            ->execute();

        $data = $this->request
            ->from('user')
            ->fetchAll();

        $this->assertArraySubset($data, [
            [ 'id' => 0, 'name' => 'PETIT', 'firstname' => 'Mathieu' ],
            [ 'id' => 5, 'name' => 'PETIT', 'firstname' => 'Eva' ],
            [ 'id' => 6, 'name' => 'PETIT', 'firstname' => null ]
        ]);
    }

    public function testDropTable()
    {
        $this->bdd->dropTable('user_role');

        $this->assertFileNotExists('test/data/user_role.json');
    }

    public function testDropSchema()
    {
        $this->bdd->dropSchema();
        $this->assertFileNotExists('test/data/schema.json');
    }
}
