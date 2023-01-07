<?php

namespace Soosyze\Queryflatfile\Tests\unit\Driver;

use Soosyze\Queryflatfile\Driver\Igbinary;

class IgbinaryTest extends Driver
{
    protected const FIXTURES_DIR = 'fixtures/driver/igbinary';

    protected function setUp(): void
    {
        if (!extension_loaded('igbinary')) {
            $this->markTestSkipped(
                'The igbinary extension is not available.'
            );
        }
        $this->driver = new Igbinary();
    }

    public function testSave(): void
    {
        $copyFilename = $this->getCopyFile();

        $data = (array) \igbinary_unserialize(
            (string) file_get_contents($copyFilename)
        );

        $data['key_test_2'] = 'value_test_2';

        $output  = $this->driver->save(self::getFixturesDir(), self::TEST_FILE_NAME, $data);
        $newData = \igbinary_unserialize(
            (string) file_get_contents($copyFilename)
        );

        self::assertTrue($output);
        self::assertEquals($data, $newData);

        unlink($copyFilename);
    }

    public function testDelete(): void
    {
        $copyFilename = $this->getCopyFile();

        $output = $this->driver->delete(self::getFixturesDir(), self::TEST_FILE_NAME);

        self::assertTrue($output);
        self::assertFileNotExists(self::getFixturesDir($copyFilename));
    }
}
