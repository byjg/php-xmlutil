<?php

namespace Tests\Fixture;

use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;

#[XmlEntity(
    rootElementName: 'Person',
    namespaces: [ '' => 'http://example.com', 'ns1' => 'http://www.example.com/person'],
    preserveCaseName: true,
    explicityMap: true
)]
class ClassWithAttributesExplicity
{
    #[XmlProperty(elementName: 'Name')]
    private string $name;
    private int $age;


    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAge(): int
    {
        return $this->age;
    }
}