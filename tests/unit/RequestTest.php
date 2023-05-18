<?php

namespace Soosyze\Queryflatfile\Tests\unit;

use PHPUnit\Framework\MockObject\MockObject;
use Soosyze\Queryflatfile\DriverInterface;
use Soosyze\Queryflatfile\Exception\Query\ColumnsNotFoundException;
use Soosyze\Queryflatfile\Exception\Query\OperatorNotFoundException;
use Soosyze\Queryflatfile\Exception\Query\QueryException;
use Soosyze\Queryflatfile\Exception\Query\TableNotFoundException;
use Soosyze\Queryflatfile\Request;
use Soosyze\Queryflatfile\Schema;
use Soosyze\Queryflatfile\WhereHandler;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    protected Schema $bdd;

    protected Request $request;

    protected Request $secondRequest;

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
        $data1 = $this->request->select('firstname')->from('user');

        self::assertEquals(
            'SELECT firstname FROM user;',
            (string) $data1
        );
        self::assertEquals(
            [ 'firstname' => 'Mathieu' ],
            $data1->fetch()
        );

        $data2 = $this->request->select()->from('user');

        self::assertEquals(
            'SELECT * FROM user;',
            (string) $data2
        );
        self::assertEquals(
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            $data2->fetch()
        );

        $data3 = $this->request->from('user');

        self::assertEquals(
            'SELECT * FROM user;',
            (string) $data3
        );
        self::assertEquals(
            [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
            $data3->fetch()
        );
    }

    public function testLists(): void
    {
        $data = $this->request->from('user');

        self::assertEquals(
            'SELECT * FROM user;',
            (string) $data
        );
        self::assertEquals(
            [ 'Mathieu', 'Jean', 'Manon', 'Marie', 'Pierre', 'Eva', null ],
            $data->lists('firstname')
        );
    }

    public function testListsKey(): void
    {
        $data = $this->request->from('user');

        self::assertEquals(
            'SELECT * FROM user;',
            (string) $data
        );
        self::assertEquals(
            [
                0 => 'Mathieu',
                1 => 'Jean',
                2 => 'Manon',
                3 => 'Marie',
                4 => 'Pierre',
                5 => 'Eva',
                6 => null
            ],
            $data->lists('firstname', 'id')
        );
    }

    public function testListsVoid(): void
    {
        $data = $this->request->from('user')->lists('error');

        self::assertEquals([], $data);
    }

    public function testSelectExceptionValue(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage('Column foo is absent: SELECT foo FROM user LIMIT 1;');
        $this->request->select('foo')->from('user')->fetch();
    }

    public function testSelectExceptionFrom(): void
    {
        $this->expectException(TableNotFoundException::class);
        $this->expectExceptionMessage('The foo table is missing.');
        $this->request->select('firstname')->from('foo')->fetch();
    }

    public function testFromException(): void
    {
        $this->expectException(TableNotFoundException::class);
        $this->expectExceptionMessage('Table is missing.');
        $this->request->select('firstname')->fetch();
    }

    /**
     * @dataProvider whereEqualsProvider
     */
    public function testWhereEquals(
        string $operator,
        string|int|float $value,
        string $expectedQueryStr,
        ?array $expectedData
    ): void {
        $data = $this->request
            ->select('name')
            ->from('user')
            ->where('id', $operator, $value);

        self::assertEquals($expectedQueryStr, (string) $data);
        self::assertEquals($expectedData, $data->fetch());
    }

    public function whereEqualsProvider(): \Generator
    {
        yield [
            '=', '1',
            'SELECT name FROM user WHERE id = \'1\';',
            null
        ];
        yield [
            '===', '1',
            'SELECT name FROM user WHERE id === \'1\';',
            null
        ];
        yield [
            '=', 1,
            'SELECT name FROM user WHERE id = 1;',
            [ 'name' => 'DUPOND' ]
        ];
        yield [
            '===', '1',
            'SELECT name FROM user WHERE id === \'1\';',
            null
        ];
        yield [
            '==', '1',
            'SELECT name FROM user WHERE id == \'1\';',
            [ 'name' => 'DUPOND' ]
        ];
    }

    public function testWhereOperatorException(): void
    {
        $this->expectException(OperatorNotFoundException::class);
        $this->expectExceptionMessage('The condition error is not exist.');
        $this->request
            ->select('name')
            ->from('user')
            ->where('id', 'error', '1')
            ->fetch();
    }

    /**
     * @dataProvider whereNotEqualsProvider
     */
    public function testWhereNotEqualsNoType(
        string $operator,
        string|int|float $value,
        string $expectedQueryStr
    ): void {
        $data = $this->request
            ->select('id')
            ->from('user')
            ->where('id', $operator, $value);

        self::assertEquals($expectedQueryStr, (string) $data);
        /* whereNotEquals sans prendre en compte le type */
        self::assertEquals(
            [
                [ 'id' => 0 ],
                [ 'id' => 2 ],
                [ 'id' => 3 ],
                [ 'id' => 4 ],
                [ 'id' => 5 ],
                [ 'id' => 6 ]
            ],
            $data->fetchAll()
        );
    }

    public function whereNotEqualsProvider(): \Generator
    {
        yield [
            '<>', '1',
            'SELECT id FROM user WHERE id <> \'1\';'
        ];
        yield [
            '<>', 1,
            'SELECT id FROM user WHERE id <> 1;'
        ];
        yield [
            '!=', '1',
            'SELECT id FROM user WHERE id != \'1\';'
        ];
        yield [
            '!=', 1,
            'SELECT id FROM user WHERE id != 1;'
        ];
    }

    public function testWhereNotEqualsType(): void
    {
        /* L'identifiant stocké est un integer, pas un string */
        $data1 = $this->request
            ->select('firstname')
            ->from('user')
            ->where('id', '!==', '1');

        self::assertEquals(
            'SELECT firstname FROM user WHERE id !== \'1\';',
            (string) $data1
        );
        self::assertEquals(
            [
                [ 'firstname' => 'Mathieu' ],
                [ 'firstname' => 'Jean' ],
                [ 'firstname' => 'Manon' ],
                [ 'firstname' => 'Marie' ],
                [ 'firstname' => 'Pierre' ],
                [ 'firstname' => 'Eva' ],
                [ 'firstname' => null ]
            ],
            $data1->fetchAll()
        );

        $data2 = $this->request
            ->select('firstname')
            ->from('user')
            ->where('id', '!==', 1);

        self::assertEquals(
            'SELECT firstname FROM user WHERE id !== 1;',
            (string) $data2
        );
        self::assertEquals(
            [
                [ 'firstname' => 'Mathieu' ],
                [ 'firstname' => 'Manon' ],
                [ 'firstname' => 'Marie' ],
                [ 'firstname' => 'Pierre' ],
                [ 'firstname' => 'Eva' ],
                [ 'firstname' => null ]
            ],
            $data2->fetchAll()
        );
    }

    /**
     * @dataProvider whereOperatorProvider
     */
    public function testWhereOperator(
        string $operator,
        int $value,
        string $expectedQueryStr,
        array $expectedData
    ): void {
        $data = $this->request
            ->from('user')
            ->where('id', $operator, $value);

        self::assertEquals($expectedQueryStr, (string) $data);
        self::assertEquals($expectedData, $data->fetchAll());
    }

    public function whereOperatorProvider(): \Generator
    {
        yield [
            '<', 1,
            'SELECT * FROM user WHERE id < 1;',
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]
            ]
        ];
        yield [
            '<=', 1,
            'SELECT * FROM user WHERE id <= 1;',
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ]
            ]
        ];
        yield [
            '>', 5,
            'SELECT * FROM user WHERE id > 5;',
            [
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ]
        ];
        yield [
            '>=', 5,
            'SELECT * FROM user WHERE id >= 5;',
            [
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ]
        ];
    }

    public function testWhereEqualsExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage('Column foo is absent: SELECT name FROM user WHERE foo = \'Jean\' LIMIT 1;');
        $this->request
            ->select('name')
            ->from('user')
            ->where('foo', '=', 'Jean')
            ->fetch();
    }

    public function testWhereBetween(): void
    {
        $data = $this->request
            ->from('user')
            ->between('id', 1, 2);

        self::assertEquals(
            'SELECT * FROM user WHERE id BETWEEN 1 AND 2;',
            (string) $data
        );
        self::assertEquals(
            [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            $data->fetch()
        );
    }

    public function testWhereNotBetween(): void
    {
        $data = $this->request
            ->from('user')
            ->notBetween('id', 1, 2);

        self::assertEquals(
            'SELECT * FROM user WHERE id NOT BETWEEN 1 AND 2;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereOrBetween(): void
    {
        $data = $this->request
            ->from('user')
            ->between('id', 1, 2)
            ->orBetween('id', 5, 6);

        self::assertEquals(
            'SELECT * FROM user WHERE id BETWEEN 1 AND 2 OR id BETWEEN 5 AND 6;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereOrNotBetween(): void
    {
        $data = $this->request
            ->from('user')
            ->between('id', 1, 2)
            ->orNotBetween('id', 5, 6);

        self::assertEquals(
            'SELECT * FROM user WHERE id BETWEEN 1 AND 2 OR id NOT BETWEEN 5 AND 6;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereBetweenExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage('Column foo is absent: SELECT * FROM user WHERE foo BETWEEN 0 AND 2 LIMIT 1;');
        $this->request
            ->from('user')
            ->between('foo', 0, 2)
            ->fetch();
    }

    public function testWhereIn(): void
    {
        $data = $this->request
            ->from('user')
            ->in('id', [ 0, 1 ]);

        self::assertEquals(
            'SELECT * FROM user WHERE id IN 0, 1;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereNotIn(): void
    {
        $data = $this->request
            ->from('user')
            ->notIn('id', [ 0, 1 ]);

        self::assertEquals(
            'SELECT * FROM user WHERE id NOT IN 0, 1;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereOrIn(): void
    {
        $data = $this->request
            ->from('user')
            ->in('id', [ 0, 1 ])
            ->orIn('id', [ 5, 6 ]);

        self::assertEquals(
            'SELECT * FROM user WHERE id IN 0, 1 OR id IN 5, 6;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereOrNotIn(): void
    {
        $data = $this->request
            ->from('user')
            ->in('id', [ 0, 1 ])
            ->orNotIn('id', [ 5, 6 ]);

        self::assertEquals(
            'SELECT * FROM user WHERE id IN 0, 1 OR id NOT IN 5, 6;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            ],
            $data->fetchAll()
        );
    }

    public function testWhereInExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage('Column foo is absent: SELECT * FROM user WHERE foo IN 0, 1;');
        $this->request
            ->from('user')
            ->in('foo', [ 0, 1 ])
            ->fetchAll();
    }

    public function testWhereIsNull(): void
    {
        $data = $this->request
            ->from('user')
            ->isNull('firstname');

        self::assertEquals(
            'SELECT * FROM user WHERE firstname IS NULL;',
            (string) $data
        );
        self::assertEquals(
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ],
            $data->fetch()
        );
    }

    public function testWhereEqualsNull(): void
    {
        $data = $this->request
            ->from('user')
            ->where('firstname', '===', null);

        self::assertEquals(
            'SELECT * FROM user WHERE firstname === null;',
            (string) $data
        );
        self::assertEquals(
            [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ],
            $data->fetch()
        );
    }

    public function testWhereIsNotNull(): void
    {
        $data = $this->request
            ->from('user')
            ->isNotNull('firstname');

        self::assertEquals(
            'SELECT * FROM user WHERE firstname IS NOT NULL;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
            ],
            $data->fetchAll()
        );
    }

    public function testWhereOrIsNull(): void
    {
        $data = $this->request
            ->from('user')
            ->isNull('firstname')
            ->orIsNull('name');

        self::assertEquals(
            'SELECT * FROM user WHERE firstname IS NULL OR name IS NULL;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereOrIsNotNull(): void
    {
        $data = $this->request
            ->from('user')
            ->isNull('firstname')
            ->orIsNotNull('name');

        self::assertEquals(
            'SELECT * FROM user WHERE firstname IS NULL OR name IS NOT NULL;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereIsNullExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage('Column foo is absent: SELECT * FROM user WHERE foo IS NULL LIMIT 1;');
        $this->request
            ->from('user')
            ->isNull('foo')
            ->fetch();
    }

    /**
     * @dataProvider whereLikeProvider
     */
    public function testWhereLike(
        string $operator,
        string $value,
        string $expectedQueryStr,
        array $expectedData
    ): void {
        $data = $this->request
            ->select('id', 'name')
            ->from('user')
            ->where('name', $operator, $value);

        self::assertEquals($expectedQueryStr, (string) $data);
        self::assertEquals($expectedData, $data->fetchAll());
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
        yield [
            'like', 'DUP%',
            'SELECT id, name FROM user WHERE name LIKE \'/^DUP.*$/\';',
            [
                [ 'id' => 1, 'name' => 'DUPOND' ],
                [ 'id' => 4, 'name' => 'DUPOND' ]
            ]
        ];
        yield [
            'like', '%TI%',
            'SELECT id, name FROM user WHERE name LIKE \'/^.*TI.*$/\';',
            [
                [ 'id' => 2, 'name' => 'MARTIN' ]
            ]
        ];
        yield [
            'like', 'OND',
            'SELECT id, name FROM user WHERE name LIKE \'/^OND$/\';',
            []
        ];
        yield [
            'like', 'OND%',
            'SELECT id, name FROM user WHERE name LIKE \'/^OND.*$/\';',
            []
        ];
        yield [
            'like', '%OND',
            'SELECT id, name FROM user WHERE name LIKE \'/^.*OND$/\';',
            [
                [ 'id' => 1, 'name' => 'DUPOND' ],
                [ 'id' => 4, 'name' => 'DUPOND' ]
            ]
        ];
        yield [
            'like', '%OND%',
            'SELECT id, name FROM user WHERE name LIKE \'/^.*OND.*$/\';',
            [
                [ 'id' => 1, 'name' => 'DUPOND' ],
                [ 'id' => 4, 'name' => 'DUPOND' ]
            ]
        ];

        // ILIKE
        yield [
            'ilike', 'Dup%',
            'SELECT id, name FROM user WHERE name LIKE \'/^Dup.*$/i\';',
            [
                [ 'id' => 1, 'name' => 'DUPOND' ],
                [ 'id' => 4, 'name' => 'DUPOND' ]
            ]
        ];
        yield [
            'ilike', '%OnD',
            'SELECT id, name FROM user WHERE name LIKE \'/^.*OnD$/i\';',
            [
                [ 'id' => 1, 'name' => 'DUPOND' ],
                [ 'id' => 4, 'name' => 'DUPOND' ]
            ]
        ];
        yield [
            'ilike', '%ti%',
            'SELECT id, name FROM user WHERE name LIKE \'/^.*ti.*$/i\';',
            [
                [ 'id' => 2, 'name' => 'MARTIN' ]
            ]
        ];

        // NOT LIKE
        yield [
            'not like', 'DUP%',
            'SELECT id, name FROM user WHERE name NOT LIKE \'/^DUP.*$/\';',
            [
                [ 'id' => 0, 'name' => 'NOEL' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 5, 'name' => 'MEYER' ],
                [ 'id' => 6, 'name' => 'ROBERT' ]
            ]
        ];
        yield [
            'not like', '%OND',
            'SELECT id, name FROM user WHERE name NOT LIKE \'/^.*OND$/\';',
            [
                [ 'id' => 0, 'name' => 'NOEL' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 5, 'name' => 'MEYER' ],
                [ 'id' => 6, 'name' => 'ROBERT' ]
            ]
        ];
        yield [
            'not like', '%E%',
            'SELECT id, name FROM user WHERE name NOT LIKE \'/^.*E.*$/\';',
            [
                [ 'id' => 1, 'name' => 'DUPOND' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 4, 'name' => 'DUPOND' ]
            ]
        ];

        // NOT ILIKE
        yield [
            'not ilike', 'DuP%',
            'SELECT id, name FROM user WHERE name NOT LIKE \'/^DuP.*$/i\';',
            [
                [ 'id' => 0, 'name' => 'NOEL' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 5, 'name' => 'MEYER' ],
                [ 'id' => 6, 'name' => 'ROBERT' ]
            ]
        ];
        yield [
            'not ilike', '%D',
            'SELECT id, name FROM user WHERE name NOT LIKE \'/^.*D$/i\';',
            [
                [ 'id' => 0, 'name' => 'NOEL' ],
                [ 'id' => 2, 'name' => 'MARTIN' ],
                [ 'id' => 5, 'name' => 'MEYER' ],
                [ 'id' => 6, 'name' => 'ROBERT' ]
            ]
        ];
        yield [
            'not ilike', '%E%',
            'SELECT id, name FROM user WHERE name NOT LIKE \'/^.*E.*$/i\';',
            [
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
            ->regex('name', '/^D/');

        self::assertEquals('SELECT * FROM user WHERE name REGEX \'/^D/\';', (string) $data);
        self::assertEquals(
            [
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereNotRegex(): void
    {
        $data = $this->request
            ->from('user')
            ->notRegex('name', '/^D/');

        self::assertEquals('SELECT * FROM user WHERE name NOT REGEX \'/^D/\';', (string) $data);
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereOrNotRegex(): void
    {
        $data = $this->request
            ->from('user')
            ->regex('name', '/^D/')
            ->orNotRegex('firstname', '/^M/');

        self::assertEquals(
            'SELECT * FROM user WHERE name REGEX \'/^D/\' OR firstname NOT REGEX \'/^M/\';',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereOrRegex(): void
    {
        $data = $this->request
            ->from('user')
            ->regex('name', '/^D/')
            ->orRegex('name', '/^N/');

        self::assertEquals(
            'SELECT * FROM user WHERE name REGEX \'/^D/\' OR name REGEX \'/^N/\';',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereRegexExceptionColumns(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage('Column foo is absent: SELECT * FROM user WHERE foo REGEX \'/^D/\' LIMIT 1;');
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
            ->where('firstname', '=', 'Pierre');

        self::assertEquals(
            'SELECT * FROM user WHERE name = \'DUPOND\' AND firstname = \'Pierre\';',
            (string) $data
        );
        self::assertEquals(
            [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
            $data->fetch()
        );
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
            ->orNotWhere('firstname', '=', 'Mathieu');

        self::assertEquals(
            'SELECT * FROM user WHERE name = \'DUPOND\' OR NOT firstname = \'Mathieu\';',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testWhereGroup(): void
    {
        $data = $this->request
            ->from('user')
            ->where('id', '>=', 2)
            ->whereGroup(static function (WhereHandler $query): void {
                $query->where('name', '=', 'DUPOND')
                ->orWhere('firstname', '=', 'Eva');
            });

        self::assertEquals(
            'SELECT * FROM user WHERE id >= 2 AND (name = \'DUPOND\' OR firstname = \'Eva\');',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ]
            ],
            $data->fetchAll()
        );
    }

    public function testNotWhereGroup(): void
    {
        $data = $this->request
            ->from('user')
            ->where('id', '>=', 2)
            ->notWhereGroup(static function (WhereHandler $query): void {
                $query->where('name', '=', 'DUPOND')
                ->orWhere('firstname', '=', 'Eva');
            });

        self::assertEquals(
            'SELECT * FROM user WHERE id >= 2 AND NOT (name = \'DUPOND\' OR firstname = \'Eva\');',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testOrWhereGroup(): void
    {
        $data = $this->request
            ->from('user')
            ->where('name', '=', 'DUPOND')
            ->orWhereGroup(static function (WhereHandler $query): void {
                $query->where('firstname', '=', 'Eva')
                ->orWhere('firstname', '=', 'Mathieu');
            });

        self::assertEquals(
            'SELECT * FROM user WHERE name = \'DUPOND\' OR (firstname = \'Eva\' OR firstname = \'Mathieu\');',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva' ]
            ],
            $data->fetchAll()
        );
    }

    public function testOrNotWhereGroup(): void
    {
        $data = $this->request
            ->from('user')
            ->where('name', '=', 'DUPOND')
            ->orNotWhereGroup(static function (WhereHandler $query): void {
                $query->where('firstname', '=', 'Eva')
                ->orWhere('firstname', '=', 'Mathieu');
            });

        self::assertEquals(
            'SELECT * FROM user WHERE name = \'DUPOND\' OR NOT (firstname = \'Eva\' OR firstname = \'Mathieu\');',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testLimit(): void
    {
        $data = $this->request
            ->from('user')
            ->limit(1);

        self::assertEquals('SELECT * FROM user LIMIT 1;', (string) $data);
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ]
            ],
            $data->fetchAll()
        );
    }

    public function testLimitException(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('The limit must be a non-zero positive integer.');
        $this->request
            ->from('user')
            ->limit(-1)
            ->fetchAll();
    }

    public function testLimitOffset(): void
    {
        $data = $this->request
            ->from('user')
            ->limit(1, 1);

        self::assertEquals('SELECT * FROM user LIMIT 1 OFFSET 1;', (string) $data);
        self::assertEquals(
            [
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ]
            ],
            $data->fetchAll()
        );
    }

    public function testOffsetException(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('The offset must be a non-zero positive integer.');
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
            ->limit(1, 1);

        self::assertEquals(
            'SELECT * FROM user WHERE name = \'DUPOND\' LIMIT 1 OFFSET 1;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre' ]
            ],
            $data->fetchAll()
        );
    }

    public function testOrderByAsc(): void
    {
        $data = $this->request
            ->select('firstname')
            ->from('user')
            ->orderBy('firstname');

        self::assertEquals(
            'SELECT firstname FROM user ORDER BY firstname ASC;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'firstname' => null ],
                [ 'firstname' => 'Eva' ],
                [ 'firstname' => 'Jean' ],
                [ 'firstname' => 'Manon' ],
                [ 'firstname' => 'Marie' ],
                [ 'firstname' => 'Mathieu' ],
                [ 'firstname' => 'Pierre' ]
            ],
            $data->fetchAll()
        );
    }

    public function testOrderByDesc(): void
    {
        $data = $this->request
            ->select('firstname')
            ->from('user')
            ->orderBy('firstname', SORT_DESC);

        self::assertEquals(
            'SELECT firstname FROM user ORDER BY firstname DESC;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'firstname' => 'Pierre' ],
                [ 'firstname' => 'Mathieu' ],
                [ 'firstname' => 'Marie' ],
                [ 'firstname' => 'Manon' ],
                [ 'firstname' => 'Jean' ],
                [ 'firstname' => 'Eva' ],
                [ 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testOrderByDescFetch(): void
    {
        $data = $this->request
            ->select('name')
            ->from('user')
            ->where('id', '>=', 4)
            ->orderBy('name', SORT_DESC);

        self::assertEquals(
            'SELECT name FROM user WHERE id >= 4 ORDER BY name DESC;',
            (string) $data
        );
        self::assertEquals(
            [ 'name' => 'ROBERT' ],
            $data->fetch()
        );
    }

    public function testOrderByDescLimitOffset(): void
    {
        $data = $this->request
            ->select('name')
            ->from('user')
            ->where('id', '>=', 3)
            ->orderBy('name', SORT_DESC)
            ->limit(2, 1);

        self::assertEquals(
            'SELECT name FROM user WHERE id >= 3 ORDER BY name DESC LIMIT 2 OFFSET 1;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'name' => 'MEYER' ],
                [ 'name' => 'DUPOND' ]
            ],
            $data->fetchAll()
        );
    }

    public function testOrderByMultipleAsc(): void
    {
        $data = $this->request
            ->select('name', 'firstname')
            ->from('user')
            ->orderBy('name', SORT_DESC)
            ->orderBy('firstname');

        self::assertEquals(
            'SELECT name, firstname FROM user ORDER BY name DESC, firstname ASC;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'name' => 'ROBERT', 'firstname' => null ],
                [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'name' => null, 'firstname' => 'Marie' ]
            ],
            $data->fetchAll()
        );
    }

    public function testOrderByMultipleDesc(): void
    {
        $data = $this->request
            ->select('name', 'firstname')
            ->from('user')
            ->orderBy('name', SORT_DESC)
            ->orderBy('firstname', SORT_DESC);

        self::assertEquals(
            'SELECT name, firstname FROM user ORDER BY name DESC, firstname DESC;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'name' => 'ROBERT', 'firstname' => null ],
                [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'name' => null, 'firstname' => 'Marie' ]
            ],
            $data->fetchAll()
        );
    }

    public function testRightJoin(): void
    {
        $data = $this->request
            ->select('id', 'name', 'firstname', 'labelle')
            ->from('role')
            ->rightJoin('user_role', 'id_role', '=', 'user_role.id_role')
            ->rightJoin('user', 'id_user', '=', 'user.id');

        self::assertEquals(
            'SELECT id, name, firstname, labelle FROM role '
            . 'RIGHT JOIN user_role ON id_role = \'user_role.id_role\' '
            . 'RIGHT JOIN user ON id_user = \'user.id\';',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu', 'labelle' => 'Admin' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean', 'labelle' => 'Admin' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon', 'labelle' => 'Author' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie', 'labelle' => 'Author' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre', 'labelle' => 'User' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva', 'labelle' => 'User' ],
                /* Pas de corespondance pour ROBERT donc le reste des colonnes en null */
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null, 'labelle' => null ],
            ],
            $data->fetchAll()
        );
    }

    public function testLeftJoin(): void
    {
        $data = $this->request
            ->select('id', 'name', 'firstname', 'labelle')
            ->from('user')
            ->leftJoin('user_role', 'id', '=', 'user_role.id_user')
            ->leftJoin('role', 'id_role', '=', 'role.id_role');

        self::assertEquals(
            'SELECT id, name, firstname, labelle FROM user '
            . 'LEFT JOIN user_role ON id = \'user_role.id_user\' '
            . 'LEFT JOIN role ON id_role = \'role.id_role\';',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu', 'labelle' => 'Admin' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean', 'labelle' => 'Admin' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon', 'labelle' => 'Author' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie', 'labelle' => 'Author' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre', 'labelle' => 'User' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva', 'labelle' => 'User' ],
                /* Pas de corespondance pour ROBERT donc le reste des colonnes en null */
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null, 'labelle' => null ],
            ],
            $data->fetchAll()
        );
    }

    public function testLeftJoinWhere(): void
    {
        $data = $this->request
            ->select('id', 'name', 'firstname')
            ->from('user')
            ->leftJoin('user_role', 'id', '=', 'user_role.id_user')
            ->leftJoin('role', 'id_role', '=', 'role.id_role')
            ->where('labelle', '=', 'Admin');

        self::assertEquals(
            'SELECT id, name, firstname FROM user '
            . 'LEFT JOIN user_role ON id = \'user_role.id_user\' '
            . 'LEFT JOIN role ON id_role = \'role.id_role\' '
            . 'WHERE labelle = \'Admin\';',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            ],
            $data->fetchAll()
        );
    }

    public function testLeftJoinGroup(): void
    {
        $data = $this->request
            ->select('id', 'name', 'firstname')
            ->from('user')
            ->leftJoin('user_role', static function (WhereHandler $query): void {
                $query->where('id', '=', 'user_role.id_user');
            })
            ->leftJoin('role', 'id_role', '=', 'role.id_role')
            ->where('labelle', '=', 'Admin');

        self::assertEquals(
            'SELECT id, name, firstname FROM user '
            . 'LEFT JOIN user_role ON id = \'user_role.id_user\' '
            . 'LEFT JOIN role ON id_role = \'role.id_role\' '
            . 'WHERE labelle = \'Admin\';',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            ],
            $data->fetchAll()
        );
    }

    public function testLeftJoinGroupMultiple(): void
    {
        $data = $this->request
            ->select('id', 'name', 'firstname', 'labelle')
            ->from('user')
            ->leftJoin('user_role', 'id', '=', 'user_role.id_user')
            ->leftJoin('role', static function (WhereHandler $query): void {
                $query->whereGroup(static function (WhereHandler $query): void {
                    $query->where('id_role', '=', 'role.id_role');
                });
            })
            ->where('labelle', '=', 'Admin');

        self::assertEquals(
            'SELECT id, name, firstname, labelle FROM user '
            . 'LEFT JOIN user_role ON id = \'user_role.id_user\' '
            . 'LEFT JOIN role ON (id_role = \'role.id_role\') '
            . 'WHERE labelle = \'Admin\';',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'labelle' => 'Admin', 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'labelle' => 'Admin', 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean' ],
            ],
            $data->fetchAll()
        );
    }

    public function testRightJoinGroupe(): void
    {
        $data = $this->request
            ->select('id', 'name', 'firstname', 'labelle')
            ->from('role')
            ->rightJoin('user_role', 'id_role', '=', 'user_role.id_role')
            ->rightJoin('user', static function (WhereHandler $query): void {
                $query->where('id_user', '=', 'user.id');
            });

        self::assertEquals(
            'SELECT id, name, firstname, labelle FROM role '
            . 'RIGHT JOIN user_role ON id_role = \'user_role.id_role\' '
            . 'RIGHT JOIN user ON id_user = \'user.id\';',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'id' => 0, 'name' => 'NOEL', 'firstname' => 'Mathieu', 'labelle' => 'Admin' ],
                [ 'id' => 1, 'name' => 'DUPOND', 'firstname' => 'Jean', 'labelle' => 'Admin' ],
                [ 'id' => 2, 'name' => 'MARTIN', 'firstname' => 'Manon', 'labelle' => 'Author' ],
                [ 'id' => 3, 'name' => null, 'firstname' => 'Marie', 'labelle' => 'Author' ],
                [ 'id' => 4, 'name' => 'DUPOND', 'firstname' => 'Pierre', 'labelle' => 'User' ],
                [ 'id' => 5, 'name' => 'MEYER', 'firstname' => 'Eva', 'labelle' => 'User' ],
                /* Pas de corespondance pour ROBERT donc le reste des colonnes en null */
                [ 'id' => 6, 'name' => 'ROBERT', 'firstname' => null, 'labelle' => null ],
            ],
            $data->fetchAll()
        );
    }

    public function testLeftJoinExceptionColumn(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage(
            'Column foo is absent: '
            . 'SELECT id, name, firstname FROM user '
            . 'LEFT JOIN user_role ON foo == \'user_role.id_user\' '
            . 'LEFT JOIN role ON id_role == \'role.id\' '
            . 'WHERE labelle = \'Admin\' '
            . 'LIMIT 1;'
        );
        $this->request
            ->select('id', 'name', 'firstname')
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
            ->union($union);

        self::assertEquals(
            'SELECT name FROM user UNION SELECT name FROM user WHERE id BETWEEN 1 AND 5;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'name' => 'NOEL' ],
                [ 'name' => 'DUPOND' ],
                [ 'name' => 'MARTIN' ],
                [ 'name' => null ],
                [ 'name' => 'MEYER' ],
                [ 'name' => 'ROBERT' ]
            ],
            $data->fetchAll()
        );
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
            ->union($union);

        self::assertEquals(
            'SELECT name, firstname FROM user UNION SELECT name, firstname FROM user WHERE id BETWEEN 1 AND 5;',
            (string) $data
        );
        self::assertEquals(
            [
                [ 'name' => 'NOEL', 'firstname' => 'Mathieu' ],
                [ 'name' => 'DUPOND', 'firstname' => 'Jean' ],
                [ 'name' => 'MARTIN', 'firstname' => 'Manon' ],
                [ 'name' => null, 'firstname' => 'Marie' ],
                [ 'name' => 'DUPOND', 'firstname' => 'Pierre' ],
                [ 'name' => 'MEYER', 'firstname' => 'Eva' ],
                [ 'name' => 'ROBERT', 'firstname' => null ]
            ],
            $data->fetchAll()
        );
    }

    public function testUnionMultipleException(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage(
            'The number of fields in the selections are different: name, firstname != name'
        );
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
            ->unionAll($union);

        self::assertEquals(
            'SELECT name FROM user UNION ALL SELECT name FROM user WHERE id BETWEEN 1 AND 5;',
            (string) $data
        );
        self::assertEquals(
            [
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
            ],
            $data->fetchAll()
        );
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
            ->unionAll($union);

        self::assertEquals(
            'SELECT name, firstname FROM user '
            . 'UNION ALL '
            . 'SELECT name, firstname FROM user WHERE id BETWEEN 1 AND 5;',
            (string) $data
        );
        self::assertEquals(
            [
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
            ],
            $data->fetchAll()
        );
    }

    public function testUnionAllMultipleException(): void
    {
        $this->expectException(ColumnsNotFoundException::class);
        $this->expectExceptionMessage(
            'The number of fields in the selections are different: name, firstname != name'
        );
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
            ->union($union);

        self::assertEquals(
            'SELECT name FROM user UNION SELECT name FROM user WHERE id BETWEEN 1 AND 5;',
            (string) $data
        );
        self::assertEquals(
            [
                'NOEL',
                'DUPOND',
                'MARTIN',
                null,
                'MEYER',
                'ROBERT'
            ],
            $data->lists('name')
        );
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
            ->orderBy('name');

        self::assertEquals(
            'SELECT name FROM user UNION SELECT name FROM user WHERE id BETWEEN 1 AND 5 ORDER BY name ASC;',
            (string) $data
        );
        self::assertEquals(
            [
                null,
                'DUPOND',
                'MARTIN',
                'MEYER',
                'NOEL',
                'ROBERT'
            ],
            $data->lists('name')
        );
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
                static fn (string $path, string $filename): bool => in_array($filename, [ 'schema', 'user', 'user_role', 'role' ])
            );

        $mock->expects(self::any())
            ->method('has')
            ->willReturnCallback(
                static fn (string $path, string $filename): bool => in_array($filename, [ 'schema', 'user', 'user_role', 'role' ])
            );

        $mock->expects(self::any())
            ->method('read')
            ->willReturnCallback(
                fn (string $path, string $filename): array => $this->loadFixtures($filename)
            );

        return $mock;
    }

    private function loadFixtures(string $filename): array
    {
        $filename = dirname(__DIR__) . "/fixtures/$filename.json";
        if (!is_file($filename)) {
            throw new \Exception("Table $filename not found");
        }

        $json = (string) file_get_contents($filename);

        $data = json_decode($json, true);
        if (!\is_array($data)) {
            throw new \Exception('An error occurred in deserializing the data.');
        }

        return $data;
    }
}
