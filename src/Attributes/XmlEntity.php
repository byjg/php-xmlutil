<?php

namespace ByJG\XmlUtil\Attributes;

use Attribute;
use Closure;

#[Attribute(Attribute::TARGET_CLASS)]
class XmlEntity
{

    private ?string $rootElementName;
    private array $namespaces;
    private bool $preserveCaseName;
    private bool $xmlDeclaration;

    public function __construct(?string $rootElementName = null, array $namespaces = [], bool $preserveCaseName = false, bool $xmlDeclaration = true)
    {
        $this->rootElementName = $rootElementName;
        $this->namespaces = $namespaces;
        $this->preserveCaseName = !is_null($rootElementName) || $preserveCaseName;
        $this->xmlDeclaration = $xmlDeclaration;
    }

    public function getRootElementName(): ?string
    {
        return $this->rootElementName;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    public function getPreserveCaseName(): bool
    {
        return $this->preserveCaseName;
    }

    public function getXmlDeclaration(): bool
    {
        return $this->xmlDeclaration;
    }

}
