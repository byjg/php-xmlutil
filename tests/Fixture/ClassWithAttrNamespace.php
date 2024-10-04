<?php

namespace Tests\Fixture;

use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;

#[XmlEntity(
    rootElementName: 'p:Person',
    namespaces: [ 'p' => 'http://example.com' ],
    preserveCaseName: true
)]
class ClassWithAttrNamespace
{
    #[XmlProperty(elementName: 'Name')]
    private string $name;

    #[XmlProperty(ignoreEmpty: true)]
    private string $shouldNotAllowEmpty = '';


    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setShouldNotAllowEmpty(string $shouldNotAllowEmpty): void
    {
        $this->shouldNotAllowEmpty = $shouldNotAllowEmpty;
    }

    public function getShouldNotAllowEmpty(): string
    {
        return $this->shouldNotAllowEmpty;
    }
}