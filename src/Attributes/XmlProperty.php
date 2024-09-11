<?php

namespace ByJG\XmlUtil\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class XmlProperty
{

    private ?string $elementName;
    private array $namespaces;
    private bool $preserveCaseName;
    private bool $isAttribute;

    public function __construct(?string $elementName = null, array $namespaces = [], bool $preserveCaseName = false, bool $isAttribute = false)
    {
        $this->elementName = $elementName;
        $this->namespaces = $namespaces;
        $this->preserveCaseName = !is_null($elementName) || $preserveCaseName;
        $this->isAttribute = $isAttribute;
    }

    public function getElementName(): ?string
    {
        return $this->elementName;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    public function getPreserveCaseName(): bool
    {
        return $this->preserveCaseName;
    }

    public function getIsAttribute(): bool
    {
        return $this->isAttribute;
    }

}
