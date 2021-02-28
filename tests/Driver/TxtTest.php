<?php

namespace Queryflatfile\Test;

class TxtTest extends \PHPUnit\Framework\TestCase
{
    const TEST_DIR = 'tests/txt';

    const TEST_FILE_NAME = 'driver_test';

    /**
     * @var DriverInterface
     */
    protected $driver;

    public static function tearDownAfterClass()
    {
        if (count(scandir(self::TEST_DIR)) == 2) {
            rmdir(self::TEST_DIR);
        }
    }

    protected function setUp()
    {
        $this->driver = new \Queryflatfile\Driver\Txt();
    }

    public function testCreate()
    {
        $output = $this->driver->create(
            self::TEST_DIR,
            self::TEST_FILE_NAME,
            [ 'key_test' => 'value_test' ]
        );

        self::assertTrue($output);
        self::assertFileExists(self::TEST_DIR . '/driver_test.txt');
    }

    public function testNoCreate()
    {
        $output = $this->driver->create(
            self::TEST_DIR,
            self::TEST_FILE_NAME,
            [ 'key_test' => 'value_test' ]
        );

        self::assertFalse($output);
    }

    public function testRead()
    {
        $data = $this->driver->read(self::TEST_DIR, self::TEST_FILE_NAME);

        self::assertArraySubset($data, [ 'key_test' => 'value_test' ]);
    }

    /**
     * @expectedException \Queryflatfile\Exception\Driver\FileNotFoundException
     */
    public function testReadException()
    {
        $this->driver->read(self::TEST_DIR, 'driver_test_error');
    }

    public function testSave()
    {
        $data = $this->driver->read(self::TEST_DIR, self::TEST_FILE_NAME);

        $data[ 'key_test_2' ] = 'value_test_2';

        $output  = $this->driver->save(self::TEST_DIR, self::TEST_FILE_NAME, $data);
        $newData = $this->driver->read(self::TEST_DIR, self::TEST_FILE_NAME);

        self::assertTrue($output);
        self::assertArraySubset($newData, $data);
    }

    /**
     * @expectedException \Queryflatfile\Exception\Driver\FileNotFoundException
     */
    public function testSaveException()
    {
        $this->driver->save(self::TEST_DIR, 'driver_test_error', []);
    }

    public function testHas()
    {
        $has    = $this->driver->has(self::TEST_DIR, self::TEST_FILE_NAME);
        $notHas = $this->driver->has(self::TEST_DIR, 'driver_test_not_found');

        self::assertTrue($has);
        self::assertFalse($notHas);
    }

    public function testDelete()
    {
        $output = $this->driver->delete(self::TEST_DIR, self::TEST_FILE_NAME);

        self::assertTrue($output);
        self::assertFileNotExists(self::TEST_DIR . '/driver_test.txt');
    }
}
