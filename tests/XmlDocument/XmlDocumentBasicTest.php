<?php

namespace Tests\XmlDocument;

use ByJG\XmlUtil\Exception\XmlUtilException;
use ByJG\XmlUtil\File;
use ByJG\XmlUtil\XmlDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class XmlDocumentBasicTest extends TestCase
{
    const XML_HEADER = '<?xml version="1.0" encoding="utf-8"?>';

    public function testCreateXmlDocument(): void
    {
        $xml = new XmlDocument();
        $this->assertEquals(self::XML_HEADER . "\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromFile(): void
    {
        $file = new File(__DIR__ . '/../buggy.xml');
        $xml = new XmlDocument($file, preserveWhiteSpace: false);
        $this->assertEquals(self::XML_HEADER . "\n<root><node><subnode>value</subnode></node></root>\n", $xml->toString());
    }

    /**
     * Data provider for XML declaration tests
     *
     * @return array
     */
    public static function xmlDeclarationProvider(): array
    {
        return [
            'no declaration' => [
                'input' => '<root/>',
                'expected' => self::XML_HEADER . "\n<root/>\n"
            ],
            'empty declaration' => [
                'input' => '<?xml?><root/>',
                'expected' => self::XML_HEADER . "\n<root/>\n"
            ],
            'only version' => [
                'input' => '<?xml version="1.0"?><root/>',
                'expected' => self::XML_HEADER . "\n<root/>\n"
            ],
            'only encoding utf8' => [
                'input' => '<?xml encoding="utf8"?><root/>',
                'expected' => self::XML_HEADER . "\n<root/>\n"
            ],
            'only encoding ascii' => [
                'input' => '<?xml encoding="ascii"?><root/>',
                'expected' => '<?xml version="1.0" encoding="ascii"?>' . "\n<root/>\n"
            ],
            'invalid version' => [
                'input' => '<?xml version="9.0"encoding="ascii"?><root/>',
                'expected' => '<?xml version="1.0" encoding="ascii"?>' . "\n<root/>\n"
            ],
            'different attribute order' => [
                'input' => '<?xml encoding="ascii" version="1.0"?><root/>',
                'expected' => '<?xml version="1.0" encoding="ascii"?>' . "\n<root/>\n"
            ],
            'with standalone' => [
                'input' => '<?xml version="1.0" encoding="ascii" standalone="yes"?><root/>',
                'expected' => '<?xml version="1.0" encoding="ascii" standalone="yes"?>' . "\n<root/>\n"
            ],
            'with only standalone' => [
                'input' => '<?xml standalone="yes"?><root/>',
                'expected' => '<?xml version="1.0" encoding="utf-8" standalone="yes"?>' . "\n<root/>\n"
            ],
            'with invalid attribute' => [
                'input' => '<?xml abc="test"?><root/>',
                'expected' => self::XML_HEADER . "\n<root/>\n"
            ]
        ];
    }

    #[DataProvider('xmlDeclarationProvider')]
    public function testCreateXmlDocumentFromStr(string $input, string $expected): void
    {
        $xml = new XmlDocument($input);
        $this->assertEquals($expected, $xml->toString());
    }

    public function testCreateDocumentFromNode(): void
    {
        $file = new File(__DIR__ . '/../buggy.xml');
        $xml = new XmlDocument($file, preserveWhiteSpace: false);

        $node = $xml->selectSingleNode('//subnode');

        $xmlFinal = new XmlDocument($node);
        $this->assertEquals(self::XML_HEADER . "\n<subnode>value</subnode>\n", $xmlFinal->toString());
    }

    public function testCreateFailed(): void
    {
        $this->expectException(XmlUtilException::class);
        $this->expectExceptionMessage('Error loading XML Document');
        new XmlDocument('<a>1');
    }

    public function testGetFormattedDocument(): void
    {
        $file = new File(__DIR__ . '/../buggy.xml');
        $xml = new XmlDocument($file);

        $formatted = $xml->toString(true);

        $this->assertEquals(
            self::XML_HEADER . "\n<root>\n  <node>\n    <subnode>value</subnode>\n  </node>\n</root>\n" , $formatted
        );
    }
} 