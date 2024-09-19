<?php

namespace Tests;

use ByJG\XmlUtil\EntityParser;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Fixture\ClassAddress;
use Tests\Fixture\ClassSample1;
use Tests\Fixture\ClassSample2;
use Tests\Fixture\ClassWithAttributes;

class EntityParserTest extends TestCase
{
    public function testStdClass()
    {
        $entity = new stdClass();
        $entity->name = "John";
        $entity->age = 30;

        $parser = new EntityParser();
        $result = $parser->parse($entity);

        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root><name>John</name><age>30</age></root>\n",
            $result
        );
    }

    public function testStdClass2()
    {
        $entity = new stdClass();
        $entity->name = 'John';
        $entity->age = 30;
        $entity->address = new stdClass();
        $entity->address->street = 'Main St';
        $entity->address->number = 123;

        $parser = new EntityParser();
        $result = $parser->parse($entity);

        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root><name>John</name><age>30</age><address><street>Main St</street><number>123</number></address></root>\n",
            $result
        );
    }

    public function testArray()
    {
        $entity = [
            'name' => 'John',
            'age' => 30,
            'address' => [
                'street' => 'Main St',
                'number' => 123
            ]
        ];

        $parser = new EntityParser();
        $result = $parser->parse($entity);

        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root><name>John</name><age>30</age><address><street>Main St</street><number>123</number></address></root>\n",
            $result
        );
    }

    public function testClassSample1()
    {
        $entity = new ClassSample1();
        $entity->setName('John');
        $entity->setAge(30);
        $entity->setAddress((object)['street' => 'Main St', 'number' => 123]);

        $parser = new EntityParser();
        $result = $parser->parse($entity);

        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<classsample1><name>John</name><age>30</age><address><street>Main St</street><number>123</number></address></classsample1>\n",
            $result
        );
    }

    public function testClassSample2()
    {
        $entity = new ClassSample2();
        $entity->setName('John');
        $entity->setAge(30);
        $entity->setAddress((object)['street' => 'Main St', 'number' => 123]);

        $parser = new EntityParser();
        $result = $parser->parse($entity);

        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<classsample2><name>John</name><age>30</age><address><street>Main St</street><number>123</number></address></classsample2>\n",
            $result
        );
    }

    public function testAnonymous()
    {
        $entity = new class {
            private $name;
            private $age;

            public function setName($name) {
                $this->name = $name;
            }

            public function setAge($age) {
                $this->age = $age;
            }

            public function getName() {
                return $this->name;
            }

            public function getAge() {
                return $this->age;
            }
        };

        $entity->setName('John');
        $entity->setAge(30);

        $parser = new EntityParser();
        $result = $parser->parse($entity);

        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root><name>John</name><age>30</age></root>\n",
            $result
        );
    }

    public function testClassWithAttributes()
    {
        $entity = new ClassWithAttributes();
        $entity->setName('John');
        $entity->setAge(30);

        $address = new ClassAddress();
        $address->setStreet('Main St');
        $address->setNumber(123);
        $address->setId('1234');

        $entity->setAddress($address);

        $parser = new EntityParser();
        $result = $parser->parse($entity);

        $this->assertEquals(
            "<Person xmlns=\"http://example.com\" xmlns:ns1=\"http://www.example.com/person\" xmlns:addr=\"http://www.example.com/address\" Age=\"30\">"
                        . "<Name>John</Name>"
                        . "<addr:Address Id=\"1234\">"
                            . "<addr:Street>Main St</addr:Street>"
                            . "<addr:Number>123</addr:Number>"
                        . "</addr:Address>"
                    . "</Person>\n",
            $result
        );
    }

    public function testWithListOfAddresses()
    {
        $entity = new stdClass();
        $entity->name = 'John';
        $entity->age = 30;
        $entity->addresses = [
            new ClassAddress(1, 'Main St', 123),
            new ClassAddress(2, 'Second St', 456),
        ];

        $result = (new EntityParser())->parse($entity);

        $this->assertEquals(
    '<?xml version="1.0" encoding="utf-8"?>' . "\n"
                . '<root xmlns:addr="http://www.example.com/address">'
                    . '<name>John</name>'
                    . '<age>30</age>'
                    . '<addresses>'
                        . '<addr:Address Id="1">'
                            . '<addr:Street>Main St</addr:Street>'
                            . '<addr:Number>123</addr:Number>'
                        . '</addr:Address>'
                        . '<addr:Address Id="2">'
                            . '<addr:Street>Second St</addr:Street>'
                            . '<addr:Number>456</addr:Number>'
                        . '</addr:Address>'
                    . '</addresses>'
                . '</root>' . "\n",
            $result
        );
    }

    public function testWithListAssociativeArray()
    {
        $entity = new stdClass();
        $entity->name = 'John';
        $entity->age = 30;
        $entity->list = [
            [ "a" => 1, "b" => 2 ],
            [ "a" => 3, "b" => 4 ],
        ];

        $result = (new EntityParser())->parse($entity);

        $this->assertEquals(
        '<?xml version="1.0" encoding="utf-8"?>' . "\n"
                . '<root>'
                    . '<name>John</name>'
                    . '<age>30</age>'
                    . '<list>'
                        . '<item0>'
                            . '<a>1</a>'
                            . '<b>2</b>'
                        . '</item0>'
                        . '<item1>'
                            . '<a>3</a>'
                            . '<b>4</b>'
                        . '</item1>'
                    . '</list>'
                . '</root>' . "\n",
            $result
        );
    }

    public function testWithListArray()
    {
        $entity = new stdClass();
        $entity->name = 'John';
        $entity->age = 30;
        $entity->list = [ 'a', 'b', 'c' ];

        $result = (new EntityParser())->parse($entity);

        $this->assertEquals(
            '<?xml version="1.0" encoding="utf-8"?>' . "\n"
            . '<root>'
                . '<name>John</name>'
                . '<age>30</age>'
                . '<list>'
                    . '<item>a</item>'
                    . '<item>b</item>'
                    . '<item>c</item>'
                . '</list>'
            . '</root>' . "\n",
            $result
        );
    }
}