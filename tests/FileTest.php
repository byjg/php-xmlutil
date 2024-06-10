<?php

namespace Tests;

use ByJG\Util\Exception\XmlUtilException;
use ByJG\Util\File;
use Exception;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testFileInvalid()
    {
        $this->expectException(XmlUtilException::class);
        $this->expectExceptionMessage('File not found');

        new File('/a/a');
    }

    public function testFile()
    {
        $file = new File(__DIR__ . '/buggy.xml');

        $this->assertStringContainsString('buggy.xml', $file->getFilename());
        $this->assertEquals("﻿<root>\n    <node>\n        <subnode>value</subnode>\n    </node>\n</root>\n", $file->getContents());
    }
}