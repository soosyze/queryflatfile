<?php

namespace Soosyze\Queryflatfile\Tests\Unit\Helpers;

use PHPUnit\Framework\MockObject\MockObject;
use Soosyze\Queryflatfile\DriverInterface;

trait DriverMock
{
    /**
     * @return DriverInterface&MockObject
     */
    protected function getDriverMock()
    {
        $mock = $this->createMock(DriverInterface::class);
        $mock->expects(self::any())
            ->method('create')
            ->willReturnCallback(
                static fn (string $path, string $filename): bool => is_file("$path/$filename.json")
            );

        $mock->expects(self::any())
            ->method('has')
            ->willReturnCallback(
                static fn (string $path, string $filename): bool => is_file("$path/$filename.json")
            );

        $mock->expects(self::any())
            ->method('read')
            ->willReturnCallback(
                fn (string $path, string $filename): array => $this->loadFixtures($path, $filename)
            );

        return $mock;
    }

    private function loadFixtures(string $path, string $filename): array
    {
        $filename = "$path/$filename.json";
        if (!is_file($filename)) {
            throw new \Exception("Table $filename not found");
        }

        $json = (string) file_get_contents($filename);

        $data = json_decode($json, true, 5);
        if (!\is_array($data)) {
            throw new \Exception('An error occurred in deserializing the data.');
        }

        return $data;
    }
}
