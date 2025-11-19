<?php

namespace Tests\XmlDocument;

use ByJG\XmlUtil\XmlDocument;
use ByJG\XmlUtil\XmlNode;
use PHPUnit\Framework\TestCase;

class XmlDocumentNodeTest extends TestCase
{
    const XML_HEADER = '<?xml version="1.0" encoding="utf-8"?>';

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
        $node->addText('Text');

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

        $node = XmlNode::instance($nodeList->item(1))->selectSingleNode('b2');
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

        $dom->selectSingleNode('subject')->removeNode();

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
} 