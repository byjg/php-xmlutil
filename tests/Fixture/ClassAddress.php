<?php

namespace Tests\Fixture;

use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;

class ClassAddress
{
    #[XmlProperty(elementName: 'Street', isAttribute: true)]
    private string $street;
    #[XmlProperty(elementName: 'Number', isAttribute: true)]
    private int $number;

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