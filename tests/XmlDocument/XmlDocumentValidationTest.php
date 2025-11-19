<?php

namespace Tests\XmlDocument;

use ByJG\XmlUtil\Exception\XmlUtilException;
use ByJG\XmlUtil\XmlDocument;
use PHPUnit\Framework\TestCase;

class XmlDocumentValidationTest extends TestCase
{
    const XML_HEADER = '<?xml version="1.0" encoding="utf-8"?>';

    public function testValidateXmlError(): void
    {
        $this->expectException(XmlUtilException::class);
        $this->expectExceptionMessage('XML Document is not valid according to');

        $xml = new XmlDocument(
            self::XML_HEADER . "\n" .
            '<example>' .
              '<child_string>This is an example.</child_string>' .
              '<child_integer>Error condition.</child_integer>' .
            '</example>'
        );
        $xml->validate(__DIR__ . '/../example.xsd');
    }

    public function testValidateXmlError2(): void
    {
        $xml = new XmlDocument(
            self::XML_HEADER . "\n" .
            '<example>' .
              '<child_string>This is an example.</child_string>' .
              '<child_integer>Error condition.</child_integer>' .
            '</example>'
        );

        $result = $xml->validate(__DIR__ . '/../example.xsd', throwError: false);
        $this->assertCount(1, $result);
    }

    public function testValidateXml(): void
    {
        $xml = new XmlDocument(
            self::XML_HEADER . "\n" .
            '<example>' .
            '<child_string>This is an example.</child_string>' .
            '<child_integer>10</child_integer>' .
            '</example>'
        );
        $result = $xml->validate(__DIR__ . '/../example.xsd');
        $this->assertNull($result);
    }
} 