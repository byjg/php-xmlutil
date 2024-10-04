<?php

namespace Tests;

use ByJG\XmlUtil\Exception\XmlUtilException;
use ByJG\XmlUtil\File;
use ByJG\XmlUtil\XmlDocument;
use ByJG\XmlUtil\XmlNode;
use PHPUnit\Framework\TestCase;
use Tests\Fixture\ClassSample1;
use Tests\Fixture\ClassSampleWithArrayElement;

class XmlUtilTest extends TestCase
{

    const XML_HEADER = '<?xml version="1.0" encoding="utf-8"?>';

    public function testCreateXmlDocument(): void
    {
        $xml = new XmlDocument();
        $this->assertEquals(self::XML_HEADER . "\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromFile(): void
    {
        $file = new File(__DIR__ . '/buggy.xml');
        $xml = new XmlDocument($file, preserveWhiteSpace: false);
        $this->assertEquals(self::XML_HEADER . "\n<root><node><subnode>value</subnode></node></root>\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromStr(): void
    {
        $xmlStr = '<root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromStr2(): void
    {
        $xmlStr = '<?xml?><root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());
    }


    public function testCreateXmlDocumentFromStr3(): void
    {
        $xmlStr = '<?xml version="1.0"?><root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromStr4(): void
    {
        $xmlStr = '<?xml encoding="utf8"?><root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromStr5(): void
    {
        $xmlStr = '<?xml encoding="ascii"?><root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());
    }

    public function testCreateDocumentFromNode(): void
    {
        $file = new File(__DIR__ . '/buggy.xml');
        $xml = new XmlDocument($file, preserveWhiteSpace: false);

        $node = $xml->selectSingleNode( '//subnode');

        $xmlFinal = new XmlDocument($node);
        $this->assertEquals(self::XML_HEADER . "\n<subnode>value</subnode>\n", $xmlFinal->toString());
    }

    public function testCreateFailed(): void
    {
        $this->expectException(XmlUtilException::class);
        $this->expectExceptionMessage('Error loading XML Document');
        new XmlDocument('<a>1');
    }

    public function testSaveXmlDocument(): void
    {
        $file = new File(__DIR__ . '/buggy.xml');
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


    public function testGetFormattedDocument(): void
    {
        $file = new File(__DIR__ . '/buggy.xml');
        $xml = new XmlDocument($file);

        $formatted = $xml->toString(true);

        $this->assertEquals(
            self::XML_HEADER . "\n<root>\n  <node>\n    <subnode>value</subnode>\n  </node>\n</root>\n" , $formatted
        );
    }
//
//    public function testAddNamespaceToDocument(): void
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//            'This test has not been implemented yet.'
//        );
//    }
//
//    public function testAddNodeFromFile(): void
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//            'This test has not been implemented yet.'
//        );
//    }
//
//    public function testAddNodeFromNode(): void
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//            'This test has not been implemented yet.'
//        );
//    }
//
    public function testCreateChild(): void
    {
        $dom = new XmlDocument('<root/>');
        $node = $dom->appendChild('test1');
        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<test1/>'
            . '</root>'
            . "\n",
            $dom->toString()
        );

        $node2 = $node->appendChild('test2', 'text2');
        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<test1>'
            .   '<test2>text2</test2>'
            . '</test1>'
            . '</root>'
            . "\n",
            $dom->toString()
        );

        $node3 = $node->appendChild('test3', 'text3', 'http://opensource.byjg.com');
        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<test1>'
            .   '<test2>text2</test2>'
            .   '<test3 xmlns="http://opensource.byjg.com">text3</test3>'
            . '</test1>'
            . '</root>'
            . "\n",
            $dom->toString()
        );

        $node3->insertBefore('test1_2', 'text1-2');

        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<test1>'
            .   '<test2>text2</test2>'
            .   '<test1_2>text1-2</test1_2>'
            .   '<test3 xmlns="http://opensource.byjg.com">text3</test3>'
            . '</test1>'
            . '</root>'
            . "\n",
            $dom->toString()
        );

        $node2->insertBefore('testBefore', 'textBefore');

        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<test1>'
            .   '<testBefore>textBefore</testBefore>'
            .   '<test2>text2</test2>'
            .   '<test1_2>text1-2</test1_2>'
            .   '<test3 xmlns="http://opensource.byjg.com">text3</test3>'
            . '</test1>'
            . '</root>'
            . "\n",
            $dom->toString()
        );
    }

    public function testAddTextNode(): void
    {
        $dom = new XmlDocument('<root><subject></subject></root>');

        $node = $dom->selectSingleNode('subject');
        $node->addText( 'Text');

        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<subject>'
            .   'Text'
            . '</subject>'
            . '</root>'
            . "\n",
            $dom->toString()
        );
    }

    public function testAddAttribute(): void
    {
        $dom = new XmlDocument('<root><subject>Text</subject></root>');

        $node = $dom->selectSingleNode('subject');
        $node->addAttribute('attr', 'value');

        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<subject attr="value">'
            .   'Text'
            . '</subject>'
            . '</root>'
            . "\n",
            $dom->toString()
        );
    }

    public function testSelectNodes(): void
    {
        $dom = new XmlDocument('<root><a><item arg="1"/><item arg="2"><b1/><b2/></item><item arg="3"/></a></root>');

        $nodeList = $dom->selectNodes('a/item');
        $this->assertEquals(3, $nodeList->length);
        $this->assertEquals('item', $nodeList->item(0)->nodeName);
        $this->assertEquals('1', $nodeList->item(0)->attributes->getNamedItem('arg')->nodeValue);
        $this->assertEquals('item', $nodeList->item(1)->nodeName);
        $this->assertEquals('2', $nodeList->item(1)->attributes->getNamedItem('arg')->nodeValue);
        $this->assertEquals('item', $nodeList->item(2)->nodeName);
        $this->assertEquals('3', $nodeList->item(2)->attributes->getNamedItem('arg')->nodeValue);

        $node = XmlNode::instance($nodeList->item(1))->selectSingleNode( 'b2');
        $this->assertEquals('b2', $node->DOMNode()->nodeName);
    }

    public function testInnerText(): void
    {
        $dom = new XmlDocument('<root><a><item arg="1"/><item arg="2"><b1/><b2/></item><item arg="3"/></a></root>');

        $node = $dom->selectSingleNode('a/item[@arg="2"]');

        $text = $node->toString();
        $this->assertEquals('<item arg="2"><b1/><b2/></item>', $text);

        $text = $node->innerText();
        $this->assertEquals("<b1/><b2/>", $text);
    }

    public function testRemoveNode(): void
    {
        $dom = new XmlDocument('<root><subject>Text</subject><a/><b/></root>');

        $dom->selectSingleNode( 'subject')->removeNode();

        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<a/>'
            . '<b/>'
            . '</root>'
            . "\n",
            $dom->toString()
        );

    }

    public function testXml2Array1(): void
    {
        $file = new File(__DIR__ . '/buggy.xml');
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

    public function testSelectNodesNamespace(): void
    {
        $file = new File(__DIR__ . '/feed-atom.txt');
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
        $file = new File(__DIR__ . '/feed-atom.txt');
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

    public function testAppendObject(): void
    {
        $xmlStr = '<root/>';
        $xml = new XmlDocument($xmlStr);

        $xml->appendChild('test', 'value');
        $node = $xml->appendChild('test2', 'value2');

        $class = new ClassSample1();
        $class->setName('John');
        $class->setAge(30);
        $class->setAddress((object)['street' => 'Main St', 'number' => 123]);

        $node->appendObject($class);

        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<test>value</test>'
            . '<test2>value2'
            .   '<name>John</name>'
            .   '<age>30</age>'
            .   '<address>'
            .     '<street>Main St</street>'
            .     '<number>123</number>'
            .   '</address>'
            . '</test2>'
            . '</root>'
            . "\n",
            $xml->toString()
        );

    }

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
        $xml->validate(__DIR__ . '/example.xsd');
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

        $result = $xml->validate(__DIR__ . '/example.xsd', throwError: false);
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
        $result = $xml->validate(__DIR__ . '/example.xsd');
        $this->assertNull($result);
    }

    public function testRenameNode()
    {
        $xml = new XmlDocument('<root><node>value</node></root>');

        $node = $xml->selectSingleNode('node');
        $node->renameNode('newnode');
        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<newnode>value</newnode>'
            . '</root>'
            . "\n",
            $xml->toString()
        );
    }

    public function testRenameNodeComplex()
    {
        $xml = new XmlDocument('<root><node a="1">value<sub>test</sub><other id="2">foo</other></node></root>');

        $node = $xml->selectSingleNode('node');
        $node->renameNode('newnode');
        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<newnode a="1">value<sub>test</sub><other id="2">foo</other></newnode>'
            . '</root>'
            . "\n",
            $xml->toString()
        );
    }

    public function testCreateXmlDocumentWithNamespace()
    {
        $xml = XmlDocument::emptyDocument('root');
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());

        $xml = XmlDocument::emptyDocument('p:root', 'http://example.com');
        $this->assertEquals(self::XML_HEADER . "\n<p:root xmlns:p=\"http://example.com\"/>\n", $xml->toString());

    }

    public function testParentNode()
    {
        $xml = new XmlDocument('<root><node><subnode>a</subnode></node></root>');

        $subNode = $xml->selectSingleNode('//subnode');
        $this->assertEquals('subnode', $subNode->DOMNode()->nodeName);

        $parentNode = $subNode->parentNode();
        $this->assertEquals('node', $parentNode->DOMNode()->nodeName);

        $rootNode = $parentNode->parentNode();
        $this->assertEquals('root', $rootNode->DOMNode()->nodeName);

        $this->assertNull($rootNode->parentNode());
    }

    public function testShouldAcceptPropertyIgnoreEmptyOnArray()
    {

        $xmlStr = '<root/>';
        $xml = new XmlDocument($xmlStr);

        $xml->appendChild('test', 'value');
        $node = $xml->appendChild('test2', 'value2');

        $class = new ClassSampleWithArrayElement();
        $class->Name = 'John';
        $class->age = 30;
        $class->city = '';
        $class->addresses = [];

        $node->appendObject($class);

        $this->assertEquals(
            self::XML_HEADER . "\n" .
            '<root>'
            . '<test>value</test>'
            . '<test2>value2'
            .   '<name>John</name>'
            .   '<age>30</age>'
            . '</test2>'
            . '</root>'
            . "\n",
            $xml->toString()
        );
    }
}
