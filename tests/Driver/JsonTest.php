<?php

namespace Queryflatfile\Test;

/**
 * @group driver
 */
class JsonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    public static function tearDownAfterClass()
    {
        if (count(scandir('tests/json')) == 2) {
            rmdir('tests/json');
        }
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        if (!extension_loaded('json')) {
            $this->markTestSkipped(
                'The json extension is not available.'
            );
        }
        $this->driver = new \Queryflatfile\Driver\Json();
    }

    public function testCreate()
    {
        $output = $this->driver->create('tests/json', 'driver_test', [ 'key_test' => 'value_test' ]);

        self::assertTrue($output);
        self::assertFileExists('tests/json/driver_test.json');
    }

    public function testNoCreate()
    {
        $output = $this->driver->create('tests/json', 'driver_test', [ 'key_test' => 'value_test' ]);

        self::assertFalse($output);
    }

    public function testRead()
    {
        $json = $this->driver->read('tests/json', 'driver_test');

        self::assertArraySubset($json, [ 'key_test' => 'value_test' ]);
    }

    /**
     * @expectedException \Queryflatfile\Exception\Driver\FileNotFoundException
     */
    public function testReadException()
    {
        $this->driver->read('tests/json', 'driver_test_error');
    }

    public function testSave()
    {
        $json                 = $this->driver->read('tests/json', 'driver_test');
        $json[ 'key_test_2' ] = 'value_test_2';

        $output = $this->driver->save('tests/json', 'driver_test', $json);

        $newJson = $this->driver->read('tests/json', 'driver_test');

        self::assertTrue($output);
        self::assertArraySubset($newJson, $json);
    }

    /**
     * @expectedException \Queryflatfile\Exception\Driver\FileNotFoundException
     */
    public function testSaveException()
    {
        $this->driver->save('tests/json', 'driver_test_error', []);
    }

    public function testHas()
    {
        $has    = $this->driver->has('tests/json', 'driver_test');
        $notHas = $this->driver->has('tests/json', 'driver_test_not_found');

        self::assertTrue($has);
        self::assertFalse($notHas);
    }

    public function testDelete()
    {
        $output = $this->driver->delete('tests/json', 'driver_test');

        self::assertTrue($output);
        self::assertFileNotExists('tests/json/driver_test.json');
    }
}
