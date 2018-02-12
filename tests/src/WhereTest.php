<?php

namespace Queryflatfile\Test;

use Queryflatfile\Where;

class WhereTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Where
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Where;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    public function testWhereEqualsString()
    {
        $this->object->where('id', '=', '1');
        $exe = $this->object->execute();

        $this->assertEquals($exe, '($row[\'id\'] === \'1\')');

        $row["id"] = '1';
        $this->assertTrue(eval('return ' . $exe . ';'));

        $row["id"] = 1;
        $this->assertFalse(eval('return ' . $exe . ';'));
    }

    public function testWhereEqualsStringDoubleQuotes()
    {
        $this->object->where("i\"d", '=', '1');
        $exe = $this->object->execute();

        $this->assertEquals($this->object->execute(), '($row[\'i\"d\'] === \'1\')');

        $row['i\"d'] = '1';
        $this->assertTrue(eval('return ' . $exe . ';'));

        $row['i\"d'] = 1;
        $this->assertFalse(eval('return ' . $exe . ';'));
    }

    public function testWhereEqualsInt()
    {
        $this->object->where('id', '=', 1);
        $exe = $this->object->execute();

        $this->assertEquals($exe, '($row[\'id\'] === 1)');

        $row["id"] = 1;
        $this->assertTrue(eval('return ' . $exe . ';'));

        $row["id"] = '1';
        $this->assertFalse(eval('return ' . $exe . ';'));
    }

    public function testWhereEqualsIntDoubleQuotes()
    {
        // Remove the following lines when you implement this test.
        $this->object->where('i"d', '=', 1);
        $exe = $this->object->execute();

        $this->assertEquals($this->object->execute(), '($row[\'i\"d\'] === 1)');

        $row['i\"d'] = 1;
        $this->assertTrue(eval('return ' . $exe . ';'));

        $row['i\"d'] = '1';
        $this->assertFalse(eval('return ' . $exe . ';'));
    }

}
