<?php

namespace Soosyze\Queryflatfile\Tests\Unit\Drivers;

use Soosyze\Queryflatfile\Drivers\Json;

class JsonTest extends Driver
{
    protected const FIXTURES_DIR = 'fixtures/driver/json';

    protected function setUp(): void
    {
        if (!extension_loaded('json')) {
            $this->markTestSkipped(
                'The json extension is not available.'
            );
        }
        $this->driver = new Json();
    }

    public function testSave(): void
    {
        $copyFilename = self::getCopyFile();

        $data = (array) \json_decode(
            (string) file_get_contents($copyFilename),
            true
        );

        $data['key_test_2'] = 'value_test_2';

        $output  = $this->driver->save(self::getFixturesDir(), self::TEST_FILE_NAME, $data);
        $newData = \json_decode(
            (string) file_get_contents($copyFilename),
            true
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
