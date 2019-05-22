<?php

namespace Queryflatfile\Test;

/**
 * @group driver
 */
class TxtTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    public static function tearDownAfterClass()
    {
        if (count(scandir('tests/txt')) == 2) {
            rmdir('tests/txt');
        }
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->driver = new \Queryflatfile\Driver\Txt();
    }

    public function testCreate()
    {
        $output = $this->driver->create('tests/txt', 'driver_test', [ 'key_test' => 'value_test' ]);

        self::assertTrue($output);
        self::assertFileExists('tests/txt/driver_test.txt');
    }

    public function testNoCreate()
    {
        $output = $this->driver->create('tests/txt', 'driver_test', [ 'key_test' => 'value_test' ]);

        self::assertFalse($output);
    }

    public function testRead()
    {
        $json = $this->driver->read('tests/txt', 'driver_test');

        self::assertArraySubset($json, [ 'key_test' => 'value_test' ]);
    }

    /**
     * @expectedException \Queryflatfile\Exception\Driver\FileNotFoundException
     */
    public function testReadException()
    {
        $this->driver->read('tests/txt', 'driver_test_error');
    }

    public function testSave()
    {
        $txt                 = $this->driver->read('tests/txt', 'driver_test');
        $txt[ 'key_test_2' ] = 'value_test_2';

        $output = $this->driver->save('tests/txt', 'driver_test', $txt);

        $newTxt = $this->driver->read('tests/txt', 'driver_test');

        self::assertTrue($output);
        self::assertArraySubset($newTxt, $txt);
    }

    /**
     * @expectedException \Queryflatfile\Exception\Driver\FileNotFoundException
     */
    public function testSaveException()
    {
        $this->driver->save('tests/txt', 'driver_test_error', []);
    }

    public function testHas()
    {
        $has    = $this->driver->has('tests/txt', 'driver_test');
        $notHas = $this->driver->has('tests/txt', 'driver_test_not_found');

        self::assertTrue($has);
        self::assertFalse($notHas);
    }

    public function testDelete()
    {
        $output = $this->driver->delete('tests/txt', 'driver_test');

        self::assertTrue($output);
        self::assertFileNotExists('tests/txt/driver_test.txt');
    }
}
