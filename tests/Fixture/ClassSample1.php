<?php

namespace Tests\Fixture;

use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;

#[XmlEntity]
class ClassSample1
{
    #[XmlProperty]
    private string $Name;
    #[XmlProperty]
    private int $age;

    private \stdClass $address;


    public function setName(string $name): void
    {
        $this->Name = $name;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function setAddress(\stdClass $address): void
    {
        $this->address = $address;
    }

    public function getName(): string
    {
        return $this->Name;
    }

    public function getAge(): int
    {
        return $this->age;
    }


    public function getAddress(): \stdClass
    {
        return $this->address;
    }
}