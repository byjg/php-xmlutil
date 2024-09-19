<?php

namespace Tests\Fixture;

use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;

#[XmlEntity(
    rootElementName: 'p:Person',
    namespaces: [ 'p' => 'http://example.com' ],
    preserveCaseName: true,
    xmlDeclaration: false
)]
class ClassWithAttrNamespace
{
    #[XmlProperty(elementName: 'Name')]
    private string $name;


    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}