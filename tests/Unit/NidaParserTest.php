<?php

namespace NidaParse\Tests\Unit;

use NidaParse\Services\NidaParser;
use PHPUnit\Framework\TestCase;

class NidaParserTest extends TestCase
{
    public function testParsesDashedFormat(): void
    {
        $parsed = NidaParser::parse('19990504-35710-00001-28');

        $this->assertSame('1999-05-04', $parsed['date']);
        $this->assertSame('35710', $parsed['ward_number']);
        $this->assertSame('00001', $parsed['seq_number']);
        $this->assertSame('28', $parsed['unknown']);
    }

    public function testParsesCompactFormat(): void
    {
        $parsed = NidaParser::parse('19990504357100000128');

        $this->assertSame('1999-05-04', $parsed['date']);
        $this->assertSame('35710', $parsed['ward_number']);
        $this->assertSame('00001', $parsed['seq_number']);
        $this->assertSame('28', $parsed['unknown']);
    }

    public function testRejectsInvalidLengthCompactFormat(): void
    {
        $parsed = NidaParser::parse('199905043571000001280');

        $this->assertSame('Invalid compact format: expected exactly 20 digits', $parsed['error']);
    }

    public function testRejectsInvalidDashedFormat(): void
    {
        $parsed = NidaParser::parse('19990504-35710-128');

        $this->assertSame('Invalid dashed format: expected YYYYMMDD-LLLLL-SSSSS-XX', $parsed['error']);
    }

    public function testRejectsInvalidDateSegment(): void
    {
        $parsed = NidaParser::parse('19991304-35710-00001-28');

        $this->assertSame('Invalid birth date segment in NIN', $parsed['error']);
    }
}
