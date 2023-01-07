<?php

namespace Soosyze\Queryflatfile\Tests\unit\Driver;

use Soosyze\Queryflatfile\Driver\Txt;

class TxtTest extends Driver
{
    protected const FIXTURES_DIR = 'fixtures/driver/txt';

    protected function setUp(): void
    {
        $this->driver = new Txt();
    }

    public function testSave(): void
    {
        $copyFilename = self::getCopyFile();

        $data = (array) \unserialize(
            (string) file_get_contents($copyFilename)
        );

        $data['key_test_2'] = 'value_test_2';

        $output  = $this->driver->save(self::getFixturesDir(), self::TEST_FILE_NAME, $data);
        $newData = \unserialize(
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
        self::assertFileNotExists(self::getFixturesDir($copyFilename));
    }
}
