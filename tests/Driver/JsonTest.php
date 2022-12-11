<?php

namespace Queryflatfile\Test\Driver;

use Queryflatfile\Driver\Json;
use Queryflatfile\DriverInterface;
use Queryflatfile\Exception\Driver\FileNotFoundException;

class JsonTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_DIR = 'tests/json';

    private const TEST_FILE_NAME = 'driver_test';

    /**
     * @var DriverInterface
     */
    protected $driver;

    public static function tearDownAfterClass(): void
    {
        if (($nbFile = scandir(self::TEST_DIR)) === false) {
            return;
        }

        if (count($nbFile) == 2) {
            rmdir(self::TEST_DIR);
        }
    }

    protected function setUp(): void
    {
        if (!extension_loaded('json')) {
            $this->markTestSkipped(
                'The json extension is not available.'
            );
        }
        $this->driver = new Json();
    }

    public function testCreate(): void
    {
        $output = $this->driver->create(
            self::TEST_DIR,
            self::TEST_FILE_NAME,
            [ 'key_test' => 'value_test' ]
        );

        self::assertTrue($output);
        self::assertFileExists(self::TEST_DIR . '/driver_test.json');
    }

    public function testNoCreate(): void
    {
        $output = $this->driver->create(
            self::TEST_DIR,
            self::TEST_FILE_NAME,
            [ 'key_test' => 'value_test' ]
        );

        self::assertFalse($output);
    }

    public function testRead(): void
    {
        $data = $this->driver->read(self::TEST_DIR, self::TEST_FILE_NAME);

        self::assertEquals($data, [ 'key_test' => 'value_test' ]);
    }

    public function testReadException(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->driver->read(self::TEST_DIR, 'driver_test_error');
    }

    public function testSave(): void
    {
        $data = $this->driver->read(self::TEST_DIR, self::TEST_FILE_NAME);

        $data[ 'key_test_2' ] = 'value_test_2';

        $output  = $this->driver->save(self::TEST_DIR, self::TEST_FILE_NAME, $data);
        $newData = $this->driver->read(self::TEST_DIR, self::TEST_FILE_NAME);

        self::assertTrue($output);
        self::assertEquals($newData, $data);
    }

    public function testSaveException(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->driver->save(self::TEST_DIR, 'driver_test_error', []);
    }

    public function testHas(): void
    {
        $has    = $this->driver->has(self::TEST_DIR, self::TEST_FILE_NAME);
        $notHas = $this->driver->has(self::TEST_DIR, 'driver_test_not_found');

        self::assertTrue($has);
        self::assertFalse($notHas);
    }

    public function testDelete(): void
    {
        $output = $this->driver->delete(self::TEST_DIR, self::TEST_FILE_NAME);

        self::assertTrue($output);
        self::assertFileNotExists(self::TEST_DIR . '/driver_test.json');
    }
}
