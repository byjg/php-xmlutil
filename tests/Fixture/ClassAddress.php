<?php

namespace Tests\Fixture;

use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;

#[XmlEntity(
    rootElementName: 'Address',
    namespaces: [ 'addr' => 'http://www.example.com/address'],
    preserveCaseName: true,
    xmlDeclaration: false,
    addNamespaceRoot: true,
    usePrefix: 'addr'
)]
class ClassAddress
{
    #[XmlProperty(elementName: 'Id', isAttribute: true)]
    private ?string $id;

    #[XmlProperty(elementName: 'Street')]
    private string $street;
    #[XmlProperty(elementName: 'Number')]
    private int $number;

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

}