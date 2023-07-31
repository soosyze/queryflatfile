<?php

namespace Soosyze\Queryflatfile\Tests\Unit\Drivers;

use Soosyze\Queryflatfile\Drivers\MsgPack;

class MsgPackTest extends Driver
{
    protected const FIXTURES_DIR = 'fixtures/driver/msgpack';

    protected function setUp(): void
    {
        if (!extension_loaded('msgpack')) {
            $this->markTestSkipped(
                'The msgpack extension is not available.'
            );
        }
        $this->driver = new MsgPack();
    }

    public function testSave(): void
    {
        $copyFilename = self::getCopyFile();

        $data = (array) \msgpack_unpack(
            (string) file_get_contents($copyFilename)
        );

        $data['key_test_2'] = 'value_test_2';

        $output  = $this->driver->save(self::getFixturesDir(), self::TEST_FILE_NAME, $data);
        $newData = \msgpack_unpack(
            (string) file_get_contents($copyFilename)
        );

        self::assertTrue($output);
        self::assertEquals($data, $newData);

        unlink($copyFilename);
    }

    public function testDelete(): void
    {
        $copyFilename = self::getCopyFile();

        $output = $this->driver->delete(self::getFixturesDir(), self::TEST_FILE_NAME);

        self::assertTrue($output);
        self::assertFileDoesNotExist(self::getFixturesDir($copyFilename));
    }
}
