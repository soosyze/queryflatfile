<?php

namespace Queryflatfile\Test;

use PHPUnit\Framework\MockObject\MockObject;
use Queryflatfile\DriverInterface;
use Queryflatfile\Exception\Query\ColumnsNotFoundException;
use Queryflatfile\Exception\Query\OperatorNotFound;
use Queryflatfile\Exception\Query\QueryException;
use Queryflatfile\Exception\Query\TableNotFoundException;
use Queryflatfile\Request;
use Queryflatfile\Schema;

class RequestTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @var Request
     */
    protected $secondRequest;

    protected function setUp(): void
    {
        $this->bdd = (new Schema)
            ->setConfig('data', 'schema', $this->getDriverMock())
            ->setPathRoot(__DIR__ . '/');

        $this->request  = new Request($this->bdd);
        $this->secondRequest = new Request($this->bdd);
    }

    public function testSelect(): void
    {
        $data1 = $this->request->select([ 'firstname' ])->from('user')->fetch();
        $data2 = $this->request->select('firstname')->from('user')->fetch();
        $data3 = $this->request->select()->from('user')->fetch();
        $data4 = $this->request->from('user')->fetch();

        self::assertEquals($data1, [ 'firstname' => 'Mathieu' ]);
        self::assertEquals($data2, [ 'firstname' => 'Mathieu' ]);
        self::assertEquals($data3, [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]);
        self::assertEquals($data4, [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]);
    }

    public function testLists(): void
    {
        $data = $this->request->from('user')->lists('firstname');

        $assert = [ 'Mathieu', 'Jean', 'Manon', 'Marie', 'Pierre', 'Eva', null ];

        self::assertEquals($data, $assert);
    }

    public function testListsKey(): void
    {
        $data = $this->request->from('user')->lists('firstname', 'id');

        self::assertEquals($data, [
            0 => 'Mathieu',
            1 => 'Jean',
            2 => 'Manon',
            3 => 'Marie',
            4 => 'Pierre',
            5 => 'Eva',
            6 => null
        ]);
    }

    public function testListsVoid(): void
    {
        $data = $this->request->from('user')->lists('error');

        self::assertEquals($data, []);
    }

    public function testSelectExceptionValue(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->request->select([ 'foo' ])->from('user')->fetch();
    }

    public function testSelectExceptionFrom(): void
    {
        $this->expectException(TableNotFoundException::class);
        $this->request->select([ 'firstname' ])->from('foo')->fetch();
    }

    public function testFromException(): void
    {
        $this->expectException(TableNotFoundException::class);
        $this->request->select([ 'firstname' ])->fetch();
    }

    public function testSelectAlternativeExceptionFrom(): void
    {
        $this->expectException(TableNotFoundException::class);
        $this->request->from('foo')->fetch();
    }

    /**
     * @param numeric $value
     *
     * @dataProvider whereEqualsProvider
     */
    public function testWhereEquals(string $operator, $value, array $arraySubject): void
    {
        $data = $this->request->select('name')
            ->from('user')
            ->where('id', $operator, $value)
            ->fetch();

        self::assertEquals($data, $arraySubject);
    }

    public function whereEqualsProvider(): \Generator
    {
        yield [ '=', '1', [] ];
        yield [ '===', '1', [] ];
        yield [ '=', 1, [ 'name' => 'DUPOND' ] ];
        yield [ '===', '1', [] ];
        yield [ '==', '1', [ 'name' => 'DUPOND' ] ];
    }

    public function testWhereOperatorException(): void
    {
        $this->expectException(OperatorNotFound::class);
        $this->request->select('name')
            ->from('user')
            ->where('id', 'error', '1')
            ->fetch();
    }

    /**
     * @param numeric $value
     *
     * @dataProvider whereNotEqualsProvider
     */
    public function testWhereNotEqualsNoType(string $operator, $value): void
    {
        $data = $this->request->select('id')
            ->from('user')
            ->where('id', $operator, $value)
            ->fetchAll();

        $arraySubject = [
            [ 'id' => 0 ],
            [ 'id' => 2 ],
            [ 'id' => 3 ],
            [ 'id' => 4 ],
            [ 'id' => 5 ],
            [ 'id' => 6 ]
        ];

        /* whereNotEquals sans prendre en compte le type */
        self::assertEquals($data, $arraySubject);
    }

    public function whereNotEqualsProvider(): \Generator
    {
        yield [ '<>', '1' ];
        yield [ '<>', 1 ];
        yield [ '!=', '1' ];
        yield [ '!=', 1 ];
    }

    public function testWhereNotEqualsType(): void
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

        self::assertEquals($dataType1, [
            [ 'firstname' => 'Mathieu' ],
            [ 'firstname' => 'Jean' ],
            [ 'firstname' => 'Manon' ],
            [ 'firstname' => 'Marie' ],
            [ 'firstname' => 'Pierre' ],
            [ 'firstname' => 'Eva' ],
            [ 'firstname' => null ]
        ]);
        self::assertEquals($dataType2, [
            [ 'firstname' => 'Mathieu' ],
            [ 'firstname' => 'Manon' ],
            [ 'firstname' => 'Marie' ],
            [ 'firstname' => 'Pierre' ],
            [ 'firstname' => 'Eva' ],
            [ 'firstname' => null ]
        ]);
    }

    /**
     * @dataProvider whereLessProvider
     */
    public function testWhereLess(string $operator, array $arraySubject): void
    {
        $data = $this->request
            ->from('user')
            ->where('id', $operator, 1)
            ->fetchAll();

        self::assertEquals($data, $arraySubject);
    }

    public function whereLessProvider(): \Generator
    {
        yield [ '<', [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]
            ]
        ];
        yield [ '<=', [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ]
            ]
        ];
    }

    /**
     * @dataProvider whereGreaterProvider
     */
    public function testWhereGreater(string $operator, array $arraySubject): void
    {
        $data = $this->request
            ->from('user')
            ->where('id', $operator, 5)
            ->fetchAll();

        self::assertEquals($data, $arraySubject);
    }

    public function whereGreaterProvider(): \Generator
    {
        yield [ '>', [
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ]
        ];
        yield [ '>=', [
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ]
        ];
    }

    public function testWhereEqualsExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->request->select([ 'name' ])
            ->from('user')
            ->where('foo', '=', 'Jean')
            ->fetch();
    }

    public function testWhereBetween(): void
    {
        $data = $this->request
            ->from('user')
            ->between('id', 1, 2)
            ->fetch();

        self::assertEquals($data, [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ]);
    }

    public function testWhereNotBetween(): void
    {
        $data = $this->request
            ->from('user')
            ->notBetween('id', 1, 2)
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    public function testWhereOrBetween(): void
    {
        $data = $this->request
            ->from('user')
            ->between('id', 1, 2)
            ->orBetween('id', 5, 6)
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    public function testWhereOrNotBetween(): void
    {
        $data = $this->request
            ->from('user')
            ->between('id', 1, 2)
            ->orNotBetween('id', 5, 6)
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
    }

    public function testWhereBetweenExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->request
            ->from('user')
            ->between('foo', 0, 2)
            ->fetch();
    }

    public function testWhereIn(): void
    {
        $data = $this->request
            ->from('user')
            ->in('id', [ 0, 1 ])
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ]
        ]);
    }

    public function testWhereNotIn(): void
    {
        $data = $this->request
            ->from('user')
            ->notIn('id', [ 0, 1 ])
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    public function testWhereOrIn(): void
    {
        $data = $this->request
            ->from('user')
            ->in('id', [ 0, 1 ])
            ->orIn('id', [ 5, 6 ])
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    public function testWhereOrNotIn(): void
    {
        $data = $this->request
            ->from('user')
            ->in('id', [ 0, 1 ])
            ->orNotIn('id', [ 5, 6 ])
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
        ]);
    }

    public function testWhereInExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->request
            ->from('user')
            ->in('foo', [ 0, 1 ])
            ->fetchAll();
    }

    public function testWhereIsNull(): void
    {
        $data = $this->request
            ->from('user')
            ->isNull('firstname')
            ->fetch();

        self::assertEquals($data, [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]);
    }

    public function testWhereIsNotNull(): void
    {
        $data = $this->request
            ->from('user')
            ->isNotNull('firstname')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
        ]);
    }

    public function testWhereOrIsNull(): void
    {
        $data = $this->request
            ->from('user')
            ->isNull('firstname')
            ->orIsNull('name')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    public function testWhereOrIsNotNull(): void
    {
        $data = $this->request
            ->from('user')
            ->isNull('firstname')
            ->orIsNotNull('name')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    public function testWhereIsNullExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->request
            ->from('user')
            ->isNull('foo')
            ->fetch();
    }

    /**
     * @param numeric $value
     *
     * @dataProvider whereLikeProvider
     */
    public function testWhereLike(string $operator, $value, array $arraySubject): void
    {
        $data = $this->request
            ->select('id', 'name')
            ->from('user')
            ->where('name', $operator, $value)
            ->fetchAll();

        self::assertEquals($data, $arraySubject);
    }

    public function testWhereLikeId(): void
    {
        $data = $this->request
            ->select('id', 'name')
            ->from('user')
            ->where('id', 'like', '1%')
            ->fetch();

        self::assertEquals($data, [ 'id' => 1, 'name' => 'DUPOND' ]);
    }

    public function whereLikeProvider(): \Generator
    {
        // LIKE
        yield [ 'like', 'DUP%', [
                [ 'id' => 1, 'name' => 'DUPOND' ],
                [ 'id' => 4, 'name' => 'DUPOND' ]
            ]
        ];
        yield [ 'like', '%TI%', [
                [ 'id' => 2, 'name' => 'MARTIN' ]
            ]
        ];
        yield [ 'like', 'OND', [] ];
        yield [ 'like', 'OND%', [] ];
        yield [ 'like', '%OND', [
                [ 'id' => 1, 'name' => 'DUPOND'],
                [ 'id' => 4, 'name' => 'DUPOND']
            ]
        ];
        yield [ 'like', '%OND%', [
                [ 'id' => 1, 'name' => 'DUPOND'],
                [ 'id' => 4, 'name' => 'DUPOND']
            ]
        ];

        // ILIKE
        yield [ 'ilike', 'Dup%', [
                [ 'id' => 1, 'name' => 'DUPOND'],
                [ 'id' => 4, 'name' => 'DUPOND']
            ]
        ];
        yield [ 'ilike', '%OnD', [
                [ 'id' => 1, 'name' => 'DUPOND' ],
                [ 'id' => 4, 'name' => 'DUPOND' ]
            ]
        ];
        yield [ 'ilike', '%ti%', [
                [ 'id' => 2, 'name' => 'MARTIN' ]
            ]
        ];

        // NOT LIKE
        yield [ 'not like', 'DUP%', [
                [ 'id' => 0, 'name' => 'NOEL' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 5, 'name' => 'MEYER' ],
                [ 'id' => 6, 'name' => 'ROBERT' ]
            ]
        ];
        yield [ 'not like', '%OND', [
                [ 'id' => 0, 'name' => 'NOEL' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 5, 'name' => 'MEYER' ],
                [ 'id' => 6, 'name' => 'ROBERT' ]
            ]
        ];
        yield [ 'not like', '%E%', [
                [ 'id' => 1, 'name' => 'DUPOND' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 4, 'name' => 'DUPOND' ]
            ]
        ];

        // NOT ILIKE
        yield [ 'not ilike', 'DuP%', [
                [ 'id' => 0, 'name' => 'NOEL' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 5, 'name' => 'MEYER' ],
                [ 'id' => 6, 'name' => 'ROBERT' ]
            ]
        ];
        yield [ 'not ilike', '%D', [
                [ 'id' => 0, 'name' => 'NOEL' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 5, 'name' => 'MEYER' ],
                [ 'id' => 6, 'name' => 'ROBERT' ]
            ]
        ];
        yield [ 'not ilike', '%E%', [
                [ 'id' => 1, 'name' => 'DUPOND' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 4, 'name' => 'DUPOND' ]
            ]
        ];
    }

    public function testWhereRegex(): void
    {
        $data = $this->request
            ->from('user')
            ->regex('name', '/^D/')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
    }

    public function testWhereNotRegex(): void
    {
        $data = $this->request
            ->from('user')
            ->notRegex('name', '/^D/')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    public function testWhereOrNotRegex(): void
    {
        $data = $this->request
            ->from('user')
            ->regex('name', '/^D/')
            ->orNotRegex('firstname', '/^M/')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ]
        ]);
    }

    public function testWhereOrRegex(): void
    {
        $data = $this->request
            ->from('user')
            ->regex('name', '/^D/')
            ->orRegex('name', '/^N/')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ] ]);
    }

    public function testWhereRegexExceptionColumns(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->request
            ->from('user')
            ->regex('foo', '/^D/')
            ->fetch();
    }

    public function testAndWhere(): void
    {
        $data = $this->request
            ->from('user')
            ->where('name', '=', 'DUPOND')
            ->where('firstname', '=', 'Pierre')
            ->fetch();

        self::assertEquals($data, [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]);
    }

    public function testOrWhere(): void
    {
        $data = $this->request
            ->from('user')
            ->where('name', '=', 'DUPOND')
            ->orWhere('firstname', '=', 'Mathieu')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
        ]);
    }

    public function testOrNotWhere(): void
    {
        $data = $this->request
            ->from('user')
            ->where('name', '=', 'DUPOND')
            ->orNotWhere('firstname', '=', 'Mathieu')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    public function testWhereAndGroup(): void
    {
        $data = $this->request
            ->from('user')
            ->where('id', '>=', 2)
            ->where(static function ($query) {
                $query->where('name', '=', 'DUPOND')
                ->orWhere('firstname', '=', 'Eva');
            })
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ]
        ]);
    }

    public function testWhereOrGroup(): void
    {
        $data = $this->request
            ->from('user')
            ->where('name', '=', 'DUPOND')
            ->orWhere(static function ($query) {
                $query->where('firstname', '=', 'Eva')
                ->orWhere('firstname', '=', 'Mathieu');
            })
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ]
        ]);
    }

    public function testLimit(): void
    {
        $data = $this->request
            ->from('user')
            ->limit(1)
            ->fetchAll();

        self::assertEquals($data, [ [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ] ]);
    }

    public function testLimitException(): void
    {
        $this->expectException(QueryException::class);
        $this->request
            ->from('user')
            ->limit(-1)
            ->fetchAll();
    }

    public function testLimitOffset(): void
    {
        $data = $this->request
            ->from('user')
            ->limit(1, 1)
            ->fetchAll();

        self::assertEquals($data, [ [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ] ]);
    }

    public function testOffsetException(): void
    {
        $this->expectException(QueryException::class);
        $this->request
            ->from('user')
            ->limit(1, -1)
            ->fetchAll();
    }

    public function testLimitOffsetWhere(): void
    {
        $data = $this->request
            ->from('user')
            ->where('name', '=', 'DUPOND')
            ->limit(1, 1)
            ->fetchAll();

        self::assertEquals($data, [ [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ] ]);
    }

    public function testOrderByAsc(): void
    {
        $data = $this->request->select([ 'firstname' ])
            ->from('user')
            ->orderBy('firstname')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'firstname' => null ],
            [ 'firstname' => 'Eva' ],
            [ 'firstname' => 'Jean' ],
            [ 'firstname' => 'Manon' ],
            [ 'firstname' => 'Marie' ],
            [ 'firstname' => 'Mathieu' ],
            [ 'firstname' => 'Pierre' ]
        ]);
    }

    public function testOrderByDesc(): void
    {
        $data = $this->request->select([ 'firstname' ])
            ->from('user')
            ->orderBy('firstname', SORT_DESC)
            ->fetchAll();

        self::assertEquals($data, [
            [ 'firstname' => 'Pierre' ],
            [ 'firstname' => 'Mathieu' ],
            [ 'firstname' => 'Marie' ],
            [ 'firstname' => 'Manon' ],
            [ 'firstname' => 'Jean' ],
            [ 'firstname' => 'Eva' ],
            [ 'firstname' => null ]
        ]);
    }

    public function testOrderByDescFetch(): void
    {
        $data = $this->request->select([ 'name' ])
            ->from('user')
            ->where('id', '>=', 4)
            ->orderBy('name', SORT_DESC)
            ->fetch();

        self::assertEquals($data, [ 'name' => 'ROBERT' ]);
    }

    public function testOrderByDescLimitOffset(): void
    {
        $data = $this->request->select([ 'name' ])
            ->from('user')
            ->where('id', '>=', 3)
            ->orderBy('name', SORT_DESC)
            ->limit(2, 1)
            ->fetchAll();

        self::assertEquals($data, [
            [ 'name' => 'MEYER' ],
            [ 'name' => 'DUPOND' ]
        ]);
    }

    public function testOrderByMultipleAsc(): void
    {
        $data = $this->request->select([ 'name', 'firstname' ])
            ->from('user')
            ->orderBy('name', SORT_DESC)
            ->orderBy('firstname')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'name' => 'ROBERT', 'firstname' => null ],
            [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'name' => null, 'firstname' => 'Marie' ]
        ]);
    }

    public function testOrderByMultipleDesc(): void
    {
        $data = $this->request->select([ 'name', 'firstname' ])
            ->from('user')
            ->orderBy('name', SORT_DESC)
            ->orderBy('firstname', SORT_DESC)
            ->fetchAll();

        self::assertEquals($data, [
            [ 'name' => 'ROBERT', 'firstname' => null ],
            [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'name' => null, 'firstname' => 'Marie' ]
        ]);
    }

    public function testRightJoin(): void
    {
        $data = $this->request->select('id', 'name', 'firstname', 'labelle')
            ->from('role')
            ->rightJoin('user_role', 'id_role', '=', 'user_role.id_role')
            ->rightJoin('user', 'id_user', '=', 'user.id')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu', 'labelle' => 'Admin' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean', 'labelle' => 'Admin' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon', 'labelle' => 'Author' ],
            [ 'id' => 3, 'name' => null, 'firstname' => 'Marie', 'labelle' => 'Author' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre', 'labelle' => 'User' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva', 'labelle' => 'User' ],
            /* Pas de corespondance pour ROBERT donc le reste des colonnes en null */
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null, 'labelle' => null ],
        ]);
    }

    public function testLeftJoin(): void
    {
        $data = $this->request->select('id', 'name', 'firstname', 'labelle')
            ->from('user')
            ->leftJoin('user_role', 'id', '=', 'user_role.id_user')
            ->leftJoin('role', 'id_role', '=', 'role.id_role')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu', 'labelle' => 'Admin' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean', 'labelle' => 'Admin' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon', 'labelle' => 'Author' ],
            [ 'id' => 3, 'name' => null, 'firstname' => 'Marie', 'labelle' => 'Author' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre', 'labelle' => 'User' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva', 'labelle' => 'User' ],
            /* Pas de corespondance pour ROBERT donc le reste des colonnes en null */
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null, 'labelle' => null ],
        ]);
    }

    public function testLeftJoinWhere(): void
    {
        $data = $this->request->select('id', 'name', 'firstname')
            ->from('user')
            ->leftJoin('user_role', 'id', '=', 'user_role.id_user')
            ->leftJoin('role', 'id_role', '=', 'role.id_role')
            ->where('labelle', '=', 'Admin')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
        ]);
    }

    public function testLeftJoinGroup(): void
    {
        $data = $this->request->select('id', 'name', 'firstname')
            ->from('user')
            ->leftJoin('user_role', static function ($query) {
                $query->where('id', '=', 'user_role.id_user');
            })
            ->leftJoin('role', 'id_role', '=', 'role.id_role')
            ->where('labelle', '=', 'Admin')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
        ]);
    }

    public function testLeftJoinGroupMultiple(): void
    {
        $data = $this->request->select('id', 'name', 'firstname', 'labelle')
            ->from('user')
            ->leftJoin('user_role', 'id', '=', 'user_role.id_user')
            ->leftJoin('role', static function ($query) {
                $query->where(static function ($query) {
                    $query->where('id_role', '=', 'role.id_role');
                });
            })
            ->where('labelle', '=', 'Admin')
            ->fetchAll();

        self::assertEquals($data, [
            [ 'labelle' => 'Admin', 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'labelle' => 'Admin', 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
        ]);
    }

    public function testRightJoinGroupe(): void
    {
        $data = $this->request->select('id', 'name', 'firstname', 'labelle')
            ->from('role')
            ->rightJoin('user_role', 'id_role', '=', 'user_role.id_role')
            ->rightJoin('user', static function ($query) {
                $query->where('id_user', '=', 'user.id');
            })
            ->fetchAll();

        self::assertEquals($data, [
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu', 'labelle' => 'Admin' ],
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean', 'labelle' => 'Admin' ],
            [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon', 'labelle' => 'Author' ],
            [ 'id' => 3, 'name' => null, 'firstname' => 'Marie', 'labelle' => 'Author' ],
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre', 'labelle' => 'User' ],
            [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva', 'labelle' => 'User' ],
            /* Pas de corespondance pour ROBERT donc le reste des colonnes en null */
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null, 'labelle' => null ],
        ]);
    }

    public function testLeftJoinExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->request->select([ 'id', 'name', 'firstname' ])
            ->from('user')
            ->leftJoin('user_role', 'foo', '==', 'user_role.id_user')
            ->leftJoin('role', 'id_role', '==', 'role.id')
            ->where('labelle', '=', 'Admin')
            ->fetch();
    }

    public function testUnion(): void
    {
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->secondRequest
            ->select('name')
            ->from('user')
            ->union($union)
            ->fetchAll();

        self::assertEquals($data, [
            [ 'name' => 'NOEL' ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MARTIN' ],
            [ 'name' => null ],
            [ 'name' => 'MEYER' ],
            [ 'name' => 'ROBERT' ]
        ]);
    }

    public function testUnionMultiple(): void
    {
        $union = $this->request
            ->select('name', 'firstname')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->secondRequest
            ->select('name', 'firstname')
            ->from('user')
            ->union($union)
            ->fetchAll();

        self::assertEquals($data, [
            [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'name' => null, 'firstname' => 'Marie' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'name' => 'ROBERT', 'firstname' => null ]
        ]);
    }

    public function testUnionMultipleException(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $this->secondRequest
            ->select('name', 'firstname')
            ->from('user')
            ->union($union)
            ->fetchAll();
    }

    public function testUnionAll(): void
    {
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->secondRequest
            ->select('name')
            ->from('user')
            ->unionAll($union)
            ->fetchAll();

        self::assertEquals($data, [
            [ 'name' => 'NOEL' ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MARTIN' ],
            [ 'name' => null ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MEYER' ],
            [ 'name' => 'ROBERT' ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MARTIN' ],
            [ 'name' => null ],
            [ 'name' => 'DUPOND' ],
            [ 'name' => 'MEYER' ]
        ]);
    }

    public function testUnionAllMultiple(): void
    {
        $union = $this->request
            ->select('name', 'firstname')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->secondRequest
            ->select('name', 'firstname')
            ->from('user')
            ->unionAll($union)
            ->fetchAll();

        self::assertEquals($data, [
            [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'name' => null, 'firstname' => 'Marie' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
            [ 'name' => 'ROBERT', 'firstname' => null ],
            [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
            [ 'name' => null, 'firstname' => 'Marie' ],
            [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            [ 'name' => 'MEYER', 'firstname' => 'Eva' ]
        ]);
    }

    public function testUnionAllMultipleException(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $this->secondRequest
            ->select('name', 'firstname')
            ->from('user')
            ->unionAll($union)
            ->fetchAll();
    }

    public function testUnionList(): void
    {
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->secondRequest
            ->select('name')
            ->from('user')
            ->union($union)
            ->lists('name');

        self::assertEquals($data, [
            'NOEL',
            'DUPOND',
            'MARTIN',
            null,
            'MEYER',
            'ROBERT'
        ]);
    }

    public function testUnionListOrder(): void
    {
        $union = $this->request
            ->select('name')
            ->from('user')
            ->between('id', 1, 5);

        $data = $this->secondRequest
            ->select('name')
            ->from('user')
            ->union($union)
            ->orderBy('name')
            ->lists('name');

        self::assertEquals($data, [
            null,
            'DUPOND',
            'MARTIN',
            'MEYER',
            'NOEL',
            'ROBERT'
        ]);
    }

    public function testPredicate(): void
    {
        $this->expectException(\Exception::class);
        \Queryflatfile\Where::predicate('', 'error', '');
    }

    /**
     * @return DriverInterface&MockObject
     */
    private function getDriverMock()
    {
        $mock = $this->createMock(DriverInterface::class);
        $mock->expects(self::any())
            ->method('create')
            ->willReturnCallback(
                function (string $path, string $filename): bool {
                    return in_array($filename, [ 'schema', 'user', 'user_role', 'role' ]);
                }
            );

        $mock->expects(self::any())
            ->method('has')
            ->willReturnCallback(
                function (string $path, string $filename): bool {
                    return in_array($filename, [ 'schema', 'user', 'user_role', 'role' ]);
                }
            );

        $mock->expects(self::any())
            ->method('read')
            ->willReturnCallback(
                function (string $path, string $filename): array {
                    if ($filename === 'schema') {
                        return $this->loadFixtures('schema');
                    }
                    if ($filename === 'user') {
                        return $this->loadFixtures('user');
                    }
                    if ($filename === 'user_role') {
                        return $this->loadFixtures('user_role');
                    }
                    if ($filename === 'role') {
                        return $this->loadFixtures('role');
                    }

                    throw new \Exception("Table $filename not found");
                }
            );

        return $mock;
    }

    private function loadFixtures(string $filename): array
    {
        $json = (string) file_get_contents(__DIR__ . "/fixtures/$filename.json");

        return json_decode($json, true);
    }
}
