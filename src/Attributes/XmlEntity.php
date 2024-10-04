<?php

namespace ByJG\XmlUtil\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class XmlEntity
{

    private ?string $rootElementName;
    private array $namespaces;
    private bool $preserveCaseName;
    private bool $preserveCaseNameChild;

    private bool $addNamespaceRoot;
    private ?string $usePrefix;
    private bool $explicityMap = false;

    public function __construct(?string $rootElementName = null, array $namespaces = [], bool $preserveCaseName = false, bool $addNamespaceRoot = true, string $usePrefix = null, bool $explicityMap = false)
    {
        $this->rootElementName = $rootElementName;
        $this->namespaces = $namespaces;
        $this->preserveCaseNameChild = $preserveCaseName;
        $this->preserveCaseName = (!empty($rootElementName) || $preserveCaseName);
        $this->addNamespaceRoot = $addNamespaceRoot;
        $this->usePrefix = $usePrefix;
        $this->explicityMap = $explicityMap;
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

    public function getPreserveCaseNameChild(): bool
    {
        return $this->preserveCaseNameChild;
    }

    public function getAddNamespaceRoot(): bool
    {
        return $this->addNamespaceRoot;
    }

    public function getUsePrefix(): ?string
    {
        return $this->usePrefix;
    }

    public function getExplicityMap(): bool
    {
        return $this->explicityMap;
    }
}
