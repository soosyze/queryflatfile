<?php

namespace Queryflatfile\Test;

use Queryflatfile\DriverJson;

/**
 * @group driver
 */
class DriverJsonTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->driver = new DriverJson();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testCreate()
    {
        $this->driver->create('tests/data', 'driver_test', [ 'key_test'=>'value_test' ]);

        $this->assertFileExists('tests/data/driver_test.json');
    }

    public function testRead()
    {
        $json = $this->driver->read('tests/data', 'driver_test');

        $this->assertArraySubset($json, [ 'key_test'=>'value_test' ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Driver\FileNotFoundException
     */
    public function testReadException()
    {
        $this->driver->read('tests/data', 'driver_test_error');
    }

    public function testSave()
    {
        $json               = $this->driver->read('tests/data', 'driver_test');
        $json['key_test_2'] = 'value_test_2';

        $output = $this->driver->save('tests/data', 'driver_test', $json);

        $newJson = $this->driver->read('tests/data', 'driver_test');

        $this->assertTrue($output);
        $this->assertArraySubset($newJson, $json);
    }

    /**
     * @expectedException Queryflatfile\Exception\Driver\FileNotFoundException
     */
    public function testSaveException()
    {
        $this->driver->save('tests/data', 'driver_test_error', []);
    }

    public function testHas()
    {
        $has    = $this->driver->has('tests/data', 'driver_test');
        $notHas = $this->driver->has('tests/data', 'driver_test_not_found');

        $this->assertTrue($has);
        $this->assertFalse($notHas);
    }

    public function testDelete()
    {
        $output = $this->driver->delete('tests/data', 'driver_test');

        $this->assertTrue($output);
        $this->assertFileNotExists('tests/data/driver_test.json');
    }
}
