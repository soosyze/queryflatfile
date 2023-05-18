<?php

namespace Soosyze\Queryflatfile\Tests\unit\Driver;

use Soosyze\Queryflatfile\DriverInterface;
use Soosyze\Queryflatfile\Exception\Driver\FileNotFoundException;

class Driver extends \PHPUnit\Framework\TestCase
{
    protected const TEST_FILE_NAME  = 'test';

    protected const TEST_FILE_ERROR = 'test_error';

    protected const FIXTURES_DIR = '';

    protected const FIXTURES_FILE_NAME  = 'data';

    protected DriverInterface $driver;

    public function testCreate(): void
    {
        $output = $this->driver->create(
            self::getFixturesDir(),
            self::TEST_FILE_NAME,
            ['key_test' => 'value_test']
        );

        $filename = self::getFixturesDir(
            sprintf(
                '/%s.%s',
                self::TEST_FILE_NAME,
                $this->driver->getExtension()
            )
        );

        self::assertTrue($output);
        self::assertFileExists($filename);

        unlink($filename);
    }

    public function testNoCreate(): void
    {
        $output = $this->driver->create(
            self::getFixturesDir(),
            self::FIXTURES_FILE_NAME,
            ['key_test' => 'value_test']
        );

        self::assertFalse($output);
    }

    public function testRead(): void
    {
        $data = $this->driver->read(
            self::getFixturesDir(),
            self::FIXTURES_FILE_NAME
        );

        self::assertEquals(['key_test' => 'value_test'], $data);
    }

    public function testReadException(): void
    {
        $filename = sprintf(
            '/%s.%s',
            self::TEST_FILE_ERROR,
            $this->driver->getExtension()
        );

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage(self::getFixturesDir($filename) . ' file is missing.');
        $this->driver->read(self::getFixturesDir(), self::TEST_FILE_ERROR);
    }

    public function testSaveException(): void
    {
        $filename = sprintf(
            '/%s.%s',
            self::TEST_FILE_ERROR,
            $this->driver->getExtension()
        );

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage(self::getFixturesDir($filename) . ' file is missing.');
        $this->driver->save(self::getFixturesDir(), self::TEST_FILE_ERROR, []);
    }

    public function testHas(): void
    {
        $has    = $this->driver->has(self::getFixturesDir(), self::FIXTURES_FILE_NAME);
        $notHas = $this->driver->has(self::getFixturesDir(), self::TEST_FILE_ERROR);

        self::assertTrue($has);
        self::assertFalse($notHas);
    }

    protected static function getFixturesDir(string $file = ''): string
    {
        return sprintf(
            '%s/%s%s',
            dirname(__DIR__, 2),
            static::FIXTURES_DIR,
            $file
        );
    }

    protected function getCopyFile(): string
    {
        $sourceFilename = self::getFixturesDir(
            sprintf(
                '/%s.%s',
                static::FIXTURES_FILE_NAME,
                $this->driver->getExtension()
            )
        );
        $copyFilename = self::getFixturesDir(
            sprintf(
                '/%s.%s',
                static::TEST_FILE_NAME,
                $this->driver->getExtension()
            )
        );

        copy($sourceFilename, $copyFilename);

        return $copyFilename;
    }
}
