<?php

namespace Tests\XmlDocument;

use ByJG\XmlUtil\File;
use ByJG\XmlUtil\XmlDocument;
use PHPUnit\Framework\TestCase;

class XmlDocumentFileTest extends TestCase
{
    const XML_HEADER = '<?xml version="1.0" encoding="utf-8"?>';

    public function testSaveXmlDocument(): void
    {
        $file = new File(__DIR__ . '/../buggy.xml');
        $xml = new XmlDocument($file, preserveWhiteSpace: false);

        $filename = sys_get_temp_dir() . '/save.xml';
        if (file_exists($filename)) {
            unlink($filename);
        }

        // Save without format
        $this->assertFalse(file_exists($filename));
        $xml->save($filename);
        $this->assertTrue(file_exists($filename));
        $contents = file_get_contents($filename);
        $this->assertEquals(self::XML_HEADER . "\n<root><node><subnode>value</subnode></node></root>\n", $contents);
        unlink($filename);

        // Save with format
        $this->assertFalse(file_exists($filename));
        $xml->save($filename, true);
        $this->assertTrue(file_exists($filename));
        $contents = file_get_contents($filename);
        $this->assertEquals(
            self::XML_HEADER . "\n"
            . "<root>\n"
            . "  <node>\n"
            . "    <subnode>value</subnode>\n"
            . "  </node>\n"
            . "</root>\n",
            $contents);
        unlink($filename);
    }
} 