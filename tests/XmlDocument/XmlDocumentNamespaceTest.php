<?php

namespace Tests\XmlDocument;

use ByJG\XmlUtil\Exception\XmlUtilException;
use ByJG\XmlUtil\File;
use ByJG\XmlUtil\XmlDocument;
use PHPUnit\Framework\TestCase;

class XmlDocumentNamespaceTest extends TestCase
{
    const XML_HEADER = '<?xml version="1.0" encoding="utf-8"?>';

    public function testSelectNodesNamespace(): void
    {
        $file = new File(__DIR__ . '/../feed-atom.txt');
        $document = new XmlDocument($file);

        $nodes = $document->selectNodes(
            'ns:entry',
            [
                "ns" => "http://www.w3.org/2005/Atom"
            ]
        );

        $this->assertEquals(25, $nodes->length);
    }

    public function testSelectNodesNamespaceError(): void
    {
        $file = new File(__DIR__ . '/../feed-atom.txt');
        $document = new XmlDocument($file);

        $this->expectException(XmlUtilException::class);
        $this->expectExceptionMessage('Error selecting nodes');

        $nodes = $document->selectNodes(
            'ns:entry'
        );
    }

    public function testSelectNodesNamespaceFromNewDoc(): void
    {
        $document = XmlDocument::emptyDocument("e:root", "http://example.com");
        $document->appendChild("e:node", "value");

        $nodes = $document->selectSingleNode("e:node", ["e" => "http://example.com"]);

        $this->assertEquals("value", $nodes->DOMNode()->textContent);
    }

    public function testSelectNodesNamespaceFromNewDocError(): void
    {
        $document = XmlDocument::emptyDocument("e:root");
        $document->appendChild("e:node", "value");

        $this->expectException(XmlUtilException::class);
        $this->expectExceptionMessage('Node not found');

        $nodes = $document->selectSingleNode("e:node", ["e" => "http://example.com"]);

        $this->assertEquals("value", $nodes->DOMNode()->textContent);
    }

    public function testAppendNewNode(): void
    {
        $document = XmlDocument::emptyDocument("e:root", "http://example.com");
        $document->appendChild("f:node", "value", "http://example.com/2");

        $nodes = $document->selectSingleNode("f:node", ["f" => "http://example.com/2"]);
        $this->assertEquals("value", $nodes->DOMNode()->textContent);

        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<e:root xmlns:e="http://example.com">'
            . '<node xmlns="http://example.com/2">value</node>'
            . '</e:root>'
            . "\n",
            $document->toString()
        );
    }

    public function testAddNamespace(): void
    {
        $xmlStr = '<root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());

        $xml->addNamespace('my', 'http://www.example.com/mytest/');
        $this->assertEquals(self::XML_HEADER . "\n<root xmlns:my=\"http://www.example.com/mytest/\"/>\n", $xml->toString());

        $xml->appendChild('my:othernodens', 'teste');
        $this->assertEquals(self::XML_HEADER . "\n<root xmlns:my=\"http://www.example.com/mytest/\"><my:othernodens>teste</my:othernodens></root>\n", $xml->toString());

        $xml->appendChild('nodens', 'teste', 'http://www.example.com/mytest/');
        $this->assertEquals(self::XML_HEADER . "\n<root xmlns:my=\"http://www.example.com/mytest/\"><my:othernodens>teste</my:othernodens><my:nodens>teste</my:nodens></root>\n", $xml->toString());

        $xml->appendChild('other', 'text', 'http://www.example.org/x/');
        $this->assertEquals(self::XML_HEADER . "\n<root xmlns:my=\"http://www.example.com/mytest/\"><my:othernodens>teste</my:othernodens><my:nodens>teste</my:nodens><other xmlns=\"http://www.example.org/x/\">text</other></root>\n", $xml->toString());
    }

    public function testCreateXmlDocumentWithNamespace()
    {
        $xml = XmlDocument::emptyDocument('root');
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());

        $xml = XmlDocument::emptyDocument('p:root', 'http://example.com');
        $this->assertEquals(self::XML_HEADER . "\n<p:root xmlns:p=\"http://example.com\"/>\n", $xml->toString());
    }
} 