<?php

namespace Queryflatfile\Tests\unit\Driver;

use Queryflatfile\Driver\MsgPack;
use Queryflatfile\DriverInterface;
use Queryflatfile\Exception\Driver\FileNotFoundException;

class MsgPackTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_DIR = 'tests/msgpack';

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
        if (!extension_loaded('msgpack')) {
            $this->markTestSkipped(
                'The msgpack extension is not available.'
            );
        }
        $this->driver = new MsgPack();
    }

    public function testCreate(): void
    {
        $output = $this->driver->create(
            self::TEST_DIR,
            self::TEST_FILE_NAME,
            [ 'key_test' => 'value_test' ]
        );

        self::assertTrue($output);
        self::assertFileExists(self::TEST_DIR . '/driver_test.msg');
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

        self::assertEquals([ 'key_test' => 'value_test' ], $data);
    }

    public function testReadException(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('tests/msgpack/driver_test_error.msg file is missing.');
        $this->driver->read(self::TEST_DIR, 'driver_test_error');
    }

    public function testSave(): void
    {
        $data = $this->driver->read(self::TEST_DIR, self::TEST_FILE_NAME);

        $data[ 'key_test_2' ] = 'value_test_2';

        $output  = $this->driver->save(self::TEST_DIR, self::TEST_FILE_NAME, $data);
        $newData = $this->driver->read(self::TEST_DIR, self::TEST_FILE_NAME);

        self::assertTrue($output);
        self::assertEquals($data, $newData);
    }

    public function testSaveException(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('tests/msgpack/driver_test_error.msg file is missing.');
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
        self::assertFileNotExists(self::TEST_DIR . '/driver_test.msg');
    }
}
