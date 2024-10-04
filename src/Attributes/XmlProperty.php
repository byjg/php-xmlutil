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
    private ?string $isAttributeOf;
    private bool $ignore;
    private bool $ignoreEmpty;

    public function __construct(?string $elementName = null, array $namespaces = [], bool $preserveCaseName = false, bool $isAttribute = false, ?string $isAttributeOf = null, bool $ignore = false, bool $ignoreEmpty = false)
    {
        $this->elementName = $elementName;
        $this->namespaces = $namespaces;
        $this->preserveCaseName = !is_null($elementName) || $preserveCaseName;
        $this->isAttributeOf = $isAttributeOf;
        $this->isAttribute = empty($this->isAttributeOf) && $isAttribute;
        $this->ignore = $ignore;
        $this->ignoreEmpty = $ignoreEmpty;
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

    public function getIsAttributeOf(): ?string
    {
        return $this->isAttributeOf;
    }

    public function getIgnore(): bool
    {
        return $this->ignore;
    }

    public function getIgnoreEmpty(): bool
    {
        return $this->ignoreEmpty;
    }
}
