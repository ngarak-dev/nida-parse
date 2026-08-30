<?php

namespace NidaParse\Tests\Unit;

use NidaParse\Services\LocationLookup;
use PHPUnit\Framework\TestCase;

class LocationLookupTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/nida_parse_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
        parent::tearDown();
    }

    public function testCombinedLookupIsServedFromCacheOnRepeatedCalls(): void
    {
        $combinedPath = $this->tempDir . '/combined.csv';
        file_put_contents(
            $combinedPath,
            "REGION,REGIONCODE,DISTRICT,DISTRICTCODE,WARD,WARDCODE,STREET,PLACES\n"
            . "MOROGORO,35,MOROGORO URBAN,351,MAZIMBU,35710,,\n"
        );

        $lookup = new LocationLookup($this->tempDir, $combinedPath);
        $first = $lookup->getAdministrativeHierarchy('35710');
        $this->assertSame('MAZIMBU', $first['WARD']);

        unlink($combinedPath);
        $second = $lookup->getAdministrativeHierarchy('35710');
        $this->assertSame($first, $second);
    }

    public function testMissedLookupIsCachedForWardCode(): void
    {
        $combinedPath = $this->tempDir . '/missing_combined.csv';
        $lookup = new LocationLookup($this->tempDir, $combinedPath);

        $first = $lookup->getAdministrativeHierarchy('99999');
        $this->assertNull($first);

        file_put_contents(
            $this->tempDir . '/99.csv',
            "REGION,REGIONCODE,DISTRICT,DISTRICTCODE,WARD,WARDCODE,STREET,PLACES\n"
            . "TEST,99,TEST DIST,9901,TEST WARD,99999,,\n"
        );

        $second = $lookup->getAdministrativeHierarchy('99999');
        $this->assertNull($second);
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
            } else {
                @unlink($itemPath);
            }
        }

        @rmdir($path);
    }
}
