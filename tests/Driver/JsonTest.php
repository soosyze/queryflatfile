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

    /**
     * Le chemin des fichiers.
     *
     * @var string
     */
    protected static $path = 'tests/driver/json';

    public static function tearDownAfterClass()
    {
        rmdir(self::$path);
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
        $output = $this->driver->create(self::$path, 'driver_test', [ 'key_test' => 'value_test' ]);

        $this->assertTrue($output);
        $this->assertFileExists(self::$path . '/driver_test.json');
    }

    public function testNoCreate()
    {
        $output = $this->driver->create(self::$path, 'driver_test', [ 'key_test' => 'value_test' ]);

        $this->assertFalse($output);
    }

    public function testRead()
    {
        $json = $this->driver->read(self::$path, 'driver_test');

        $this->assertArraySubset($json, [ 'key_test' => 'value_test' ]);
    }

    /**
     * @expectedException Queryflatfile\Exception\Driver\FileNotFoundException
     */
    public function testReadException()
    {
        $this->driver->read(self::$path, 'driver_test_error');
    }

    public function testSave()
    {
        $json                 = $this->driver->read(self::$path, 'driver_test');
        $json[ 'key_test_2' ] = 'value_test_2';

        $output = $this->driver->save(self::$path, 'driver_test', $json);

        $newJson = $this->driver->read(self::$path, 'driver_test');

        $this->assertTrue($output);
        $this->assertArraySubset($newJson, $json);
    }

    /**
     * @expectedException Queryflatfile\Exception\Driver\FileNotFoundException
     */
    public function testSaveException()
    {
        $this->driver->save(self::$path, 'driver_test_error', []);
    }

    public function testHas()
    {
        $has    = $this->driver->has(self::$path, 'driver_test');
        $notHas = $this->driver->has(self::$path, 'driver_test_not_found');

        $this->assertTrue($has);
        $this->assertFalse($notHas);
    }

    public function testDelete()
    {
        $output = $this->driver->delete(self::$path, 'driver_test');

        $this->assertTrue($output);
        $this->assertFileNotExists(self::$path . '/driver_test.json');
    }
}
