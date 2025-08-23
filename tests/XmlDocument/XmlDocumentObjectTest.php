<?php

namespace Tests\XmlDocument;

use ByJG\XmlUtil\XmlDocument;
use PHPUnit\Framework\TestCase;
use Tests\Fixture\ClassSample1;
use Tests\Fixture\ClassSampleWithArrayElement;

class XmlDocumentObjectTest extends TestCase
{
    const XML_HEADER = '<?xml version="1.0" encoding="utf-8"?>';

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

    public function testShouldAcceptPropertyIgnoreEmptyOnArray(): void
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