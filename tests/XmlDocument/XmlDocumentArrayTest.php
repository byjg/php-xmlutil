<?php

namespace Tests\XmlDocument;

use ByJG\XmlUtil\File;
use ByJG\XmlUtil\XmlDocument;
use PHPUnit\Framework\TestCase;

class XmlDocumentArrayTest extends TestCase
{
    const XML_HEADER = '<?xml version="1.0" encoding="utf-8"?>';

    public function testXml2Array1(): void
    {
        $file = new File(__DIR__ . '/../buggy.xml');
        $xml = new XmlDocument($file, preserveWhiteSpace: false);

        $array = $xml->toArray();
        $this->assertEquals([ "node" => [ "subnode" => "value"]], $array);
    }

    public function testXml2Array2(): void
    {
        $xml = new XmlDocument('<root><node param="pval">value</node></root>');

        $array = $xml->toArray();
        $this->assertEquals([ "node" => "value"], $array);
    }
} 