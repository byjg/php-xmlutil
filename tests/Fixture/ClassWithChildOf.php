<?php

namespace Tests\Fixture;

use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;

#[XmlEntity(
    rootElementName: 'Person',
    namespaces: [ '' => 'http://example.com', 'ns1' => 'http://www.example.com/person'],
    preserveCaseName: true
)]
class ClassWithChildOf
{
    #[XmlProperty(elementName: 'Name')]
    private string $name;
    #[XmlProperty(elementName: 'Address', isChildOf: "Name")]
    private ClassAddress $address;
    #[XmlProperty(elementName: 'Profession', isChildOf: "Name")]
    public string $profession = '';
    #[XmlProperty(elementName: 'Name', isChildOf: "//Profession")]
    private string $professionName;


    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setProfessionName(string $professionName): void
    {
        $this->professionName = $professionName;
    }

    public function getProfessionName(): string
    {
        return $this->professionName;
    }
}