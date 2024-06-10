<?php

namespace Tests;

use ByJG\Util\Exception\XmlUtilException;
use ByJG\Util\File;
use ByJG\Util\XmlDocument;
use ByJG\Util\XmlNode;
use ByJG\Util\XmlUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Xml;

class XmlUtilTest extends TestCase
{

    const XML_HEADER = '<?xml version="1.0" encoding="utf-8"?>';

    /**
     * @var \ByJG\Util\XmlUtil
     */
    protected XmlUtil $object;

    public function testCreateXmlDocument()
    {
        $xml = new XmlDocument();
        $this->assertEquals(self::XML_HEADER . "\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromFile()
    {
        $file = new File(__DIR__ . '/buggy.xml');
        $xml = new XmlDocument($file, preserveWhiteSpace: false);
        $this->assertEquals(self::XML_HEADER . "\n<root><node><subnode>value</subnode></node></root>\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromStr()
    {
        $xmlStr = '<root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromStr2()
    {
        $xmlStr = '<?xml?><root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());
    }


    public function testCreateXmlDocumentFromStr3()
    {
        $xmlStr = '<?xml version="1.0"?><root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromStr4()
    {
        $xmlStr = '<?xml encoding="utf8"?><root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());
    }

    public function testCreateXmlDocumentFromStr5()
    {
        $xmlStr = '<?xml encoding="ascii"?><root/>';
        $xml = new XmlDocument($xmlStr);
        $this->assertEquals(self::XML_HEADER . "\n<root/>\n", $xml->toString());
    }

    public function testCreateDocumentFromNode()
    {
        $file = new File(__DIR__ . '/buggy.xml');
        $xml = new XmlDocument($file, preserveWhiteSpace: false);

        $node = $xml->selectSingleNode( '//subnode');

        $xmlFinal = new XmlDocument($node);
        $this->assertEquals(self::XML_HEADER . "\n<subnode>value</subnode>\n", $xmlFinal->toString());
    }

    public function testCreateFailed()
    {
        $this->expectException(XmlUtilException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML()');
        new XmlDocument('<a>1');
    }

    public function testSaveXmlDocument()
    {
        $file = new File(__DIR__ . '/buggy.xml');
        $xml = new XmlDocument($file, preserveWhiteSpace: false);

        $filename = sys_get_temp_dir() . '/save.xml';
        if (file_exists($filename)) {
            unlink($filename);
        }
        $this->assertFalse(file_exists($filename));

        $xml->save($filename);
        $this->assertTrue(file_exists($filename));

        $contents = file_get_contents($filename);
        $this->assertEquals(self::XML_HEADER . "\n<root><node><subnode>value</subnode></node></root>\n", $contents);

        unlink($filename);
    }

    public function testGetFormattedDocument()
    {
        $file = new File(__DIR__ . '/buggy.xml');
        $xml = new XmlDocument($file);

        $formatted = $xml->toString(true);

        $this->assertEquals(
            self::XML_HEADER . "\n<root>\n  <node>\n    <subnode>value</subnode>\n  </node>\n</root>\n" , $formatted
        );
    }
//
//    public function testAddNamespaceToDocument()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//            'This test has not been implemented yet.'
//        );
//    }
//
//    public function testAddNodeFromFile()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//            'This test has not been implemented yet.'
//        );
//    }
//
//    public function testAddNodeFromNode()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//            'This test has not been implemented yet.'
//        );
//    }
//
    public function testCreateChild()
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

    public function testAddTextNode()
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

    public function testAddAttribute()
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

    public function testSelectNodes()
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

    public function testInnerText()
    {
        $dom = new XmlDocument('<root><a><item arg="1"/><item arg="2"><b1/><b2/></item><item arg="3"/></a></root>');

        $node = $dom->selectSingleNode('a/item[@arg="2"]');

        $text = $node->toString();
        $this->assertEquals('<item arg="2"><b1/><b2/></item>', $text);

        $text = $node->innerText();
        $this->assertEquals("<b1/><b2/>", $text);
    }

    public function testRemoveNode()
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

    public function testXml2Array1()
    {
        $file = new File(__DIR__ . '/buggy.xml');
        $xml = new XmlDocument($file, preserveWhiteSpace: false);

        $array = $xml->toArray();
        $this->assertEquals([ "node" => [ "subnode" => "value"]], $array);
    }

    public function testXml2Array2()
    {
        $xml = new XmlDocument('<root><node param="pval">value</node></root>');

        $array = $xml->toArray();
        $this->assertEquals([ "node" => "value"], $array);
    }

    public function testSelectNodesNamespace()
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

    public function testSelectNodesNamespaceError()
    {
        $file = new File(__DIR__ . '/feed-atom.txt');
        $document = new XmlDocument($file);

        $this->expectException(XmlUtilException::class);
        $this->expectExceptionMessage('DOMXPath::query()');

        $nodes = $document->selectNodes(
            'ns:entry'
        );
    }

    public function testAddNamespace()
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
}
