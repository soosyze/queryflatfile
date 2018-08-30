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

    /**
     * Le chemin des fichiers.
     *
     * @var string
     */
    protected static $path = 'tests/driver/txt';

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
        $this->driver = new \Queryflatfile\Driver\Txt();
    }

    public function testCreate()
    {
        $output = $this->driver->create(self::$path, 'driver_test', [ 'key_test' => 'value_test' ]);

        $this->assertTrue($output);
        $this->assertFileExists(self::$path . '/driver_test.txt');
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
        $txt                 = $this->driver->read(self::$path, 'driver_test');
        $txt[ 'key_test_2' ] = 'value_test_2';

        $output = $this->driver->save(self::$path, 'driver_test', $txt);

        $newTxt = $this->driver->read(self::$path, 'driver_test');

        $this->assertTrue($output);
        $this->assertArraySubset($newTxt, $txt);
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
        $this->assertFileNotExists(self::$path . '/driver_test.txt');
    }
}
