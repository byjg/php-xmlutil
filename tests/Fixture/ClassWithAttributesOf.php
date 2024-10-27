<?php

namespace Tests\Fixture;

use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;

#[XmlEntity(
    rootElementName: 'Person',
    namespaces: [ '' => 'http://example.com', 'ns1' => 'http://www.example.com/person'],
    preserveCaseName: true
)]
class ClassWithAttributesOf
{
    #[XmlProperty(elementName: 'Name')]
    private string $name;
    #[XmlProperty(elementName: 'Age', isAttributeOf: "Name")]
    private int $age;
    #[XmlProperty(elementName: 'Address')]
    private ClassAddress $address;


    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function setAddress(ClassAddress $address): void
    {
        $this->address = $address;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAge(): int
    {
        return $this->age;
    }


    public function getAddress(): ClassAddress
    {
        return $this->address;
    }

}