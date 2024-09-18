<?php

namespace ByJG\XmlUtil\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class XmlEntity
{

    private ?string $rootElementName;
    private array $namespaces;
    private bool $preserveCaseName;
    private bool $xmlDeclaration;
    private bool $addNamespaceRoot;
    private ?string $usePrefix;

    public function __construct(?string $rootElementName = null, array $namespaces = [], bool $preserveCaseName = false, bool $xmlDeclaration = true, bool $addNamespaceRoot = true, string $usePrefix = null)
    {
        $this->rootElementName = $rootElementName;
        $this->namespaces = $namespaces;
        $this->preserveCaseName = !is_null($rootElementName) || $preserveCaseName;
        $this->xmlDeclaration = $xmlDeclaration;
        $this->addNamespaceRoot = $addNamespaceRoot;
        $this->usePrefix = $usePrefix;
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

    public function getAddNamespaceRoot(): bool
    {
        return $this->addNamespaceRoot;
    }

    public function getUsePrefix(): ?string
    {
        return $this->usePrefix;
    }
}
