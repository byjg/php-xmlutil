<?php

namespace ByJG\XmlUtil;

use ByJG\Serializer\Serialize;
use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;
use ByJG\XmlUtil\Exception\FileException;
use ByJG\XmlUtil\Exception\XmlUtilException;
use DOMException;
use ReflectionAttribute;
use ReflectionClass;
use stdClass;

class EntityParser
{
    protected array $properties = [];

    protected bool $explicityMap = false;

    /**
     * @param object|array $serializable
     * @return XmlDocument
     * @throws DOMException
     * @throws FileException
     * @throws XmlUtilException
     */
    public function parse(object|array $serializable): XmlDocument
    {
        /** @var XmlDocument $xml */
        $metadata = $this->getReflectionClassMeta($serializable);
        $list = $metadata->getNamespaces();
        $nodeRootParts = explode(':', $metadata->getRootElementName());
        if (count($nodeRootParts) === 1) {
            $namespace = null;
        } else if (isset($list[$nodeRootParts[0]])) {
            $namespace = $list[$nodeRootParts[0]];
            unset($list[$nodeRootParts[0]]);
        }
        $xml = XmlDocument::emptyDocument($metadata->getRootElementName(), $namespace);
        foreach ($list as $prefix => $uri) {
            $xml->addNamespace($prefix, $uri);
        }

        $this->explicityMap = $metadata->getExplicityMap();

        $this->arrayToXml($serializable, $xml, $metadata);

        return $xml;
    }

    /**
     * @param array|object $object
     * @return XmlEntity
     * @throws XmlUtilException
     */
    protected function getReflectionClassMeta(array|object $object): ?XmlEntity
    {
        if (is_array($object) || $object instanceof stdClass) {
            return new XmlEntity(rootElementName: 'root', namespaces: [], usePrefix: null);
        }

        $reflection = new ReflectionClass($object);
        $attributes = $reflection->getAttributes(XmlEntity::class, ReflectionAttribute::IS_INSTANCEOF);
        if (count($attributes) > 1) {
            throw new XmlUtilException("Entity '{$reflection->getName()}' has more than one TableAttribute", 258);
        } elseif (count($attributes) == 1) {
            /** @var XmlEntity $tableAttribute */
            $tableAttribute = $attributes[0]->newInstance();
            $name = $tableAttribute->getRootElementName() ?? $reflection->getShortName();
            return new XmlEntity(
                rootElementName: $tableAttribute->getPreserveCaseName() ? $name : strtolower($name),
                namespaces: $tableAttribute->getNamespaces(),
                preserveCaseName: $tableAttribute->getPreserveCaseName(),
                usePrefix: !empty($tableAttribute->getUsePrefix()) ? $tableAttribute->getUsePrefix() . ":" : null,
                explicityMap: $tableAttribute->getExplicityMap()
            );
        } else {
            $classParts = explode('\\', $reflection->getName());
            return new XmlEntity(rootElementName: $reflection->isAnonymous() ? 'root' : strtolower(end($classParts)), namespaces: [], usePrefix: null);
        }
    }

    protected function addNamespaces(XmlNode $xml, XmlEntity $metadata): void
    {
        foreach ($metadata->getNamespaces() as $prefix => $uri) {
            $xml->addNamespace($prefix, $uri);
        }
        if (!empty($metadata->getUsePrefix())) {
            $currentName = $xml->DOMNode()->nodeName;
            if (str_contains($currentName, ':')) {
                if (!str_starts_with($currentName, $metadata->getUsePrefix())) {
                    $xml->renameNode($metadata->getUsePrefix() . substr($currentName, strpos($currentName, ':') + 1));
                }
            } else {
                $xml->renameNode($metadata->getUsePrefix() . $currentName);
            }
        }
    }

    /**
     * @param object|array $array $array
     * @param XmlNode $xml
     * @param XmlEntity|null $rootMetadata
     * @throws DOMException
     * @throws XmlUtilException
     */
    public function arrayToXml(object|array $array, XmlNode $xml, XmlEntity $rootMetadata = null): void
    {
        // The main Root document is not empty
        if (empty($rootMetadata)) {
            $rootMetadata = $this->getReflectionClassMeta($array);
            if (empty($rootMetadata)) {
                throw new XmlUtilException("The root element must be defined", 258);
            }
            $this->addNamespaces($xml, $rootMetadata);
        }

        $transformer = function (?XmlProperty $property, $parsedValue, $propertyName) use ($xml, $rootMetadata) {
            $name = $property?->getElementName() ?? $propertyName;
            $name = (($property?->getPreserveCaseName() ?? false) || $rootMetadata->getPreserveCaseNameChild()) ? $name : strtolower($name);
            $isAttribute = $property?->getIsAttribute();
            $isAttributeOf = $property?->getIsAttributeOf();
            $hasAttribute = !is_null($property?->getElementName());

            if ($property?->getIgnore() ?? false) {
                return false;
            }
            if ($property?->getIgnoreEmpty() ?? false) {
                if (!is_numeric($parsedValue) && empty(trim($parsedValue))) {
                    return false;
                }
            }
            if (!$hasAttribute && $this->explicityMap) {
                return false;
            }

            return $this->processArray($parsedValue, $propertyName, $name, $xml)
                || $this->processObject($parsedValue, $name, $xml)
                || $this->processScalar($parsedValue, $rootMetadata, $isAttribute, $isAttributeOf, $name, $xml);
        };

        Serialize::from($array)
            ->withStopAtFirstLevel()
            ->parseAttributes(
                $transformer,
                XmlProperty::class
            );
    }

    protected function processArray($parsedValue, $propertyName, $name, XmlNode $xml): bool
    {
        if (!is_array($parsedValue)) {
            return false;
        }
        if (!is_numeric($propertyName)) {
            $subnode = $xml->appendChild("$name");
        } else {
            $subnode = $xml;
        }
        $createAgain = false;
        foreach ($parsedValue as $key => $value) {
            if (is_scalar($value) && !is_numeric($key)) {
                $subnode->appendChild($key, htmlspecialchars("$value"));
            } elseif (is_scalar($value) && is_numeric($key)) {
                if ($createAgain) {
                    $subnode = $subnode->parentNode()->appendChild($subnode->DOMNode()->nodeName);
                }
                $subnode->addText(htmlspecialchars("$value"));
                $createAgain = true;
            } elseif (is_array($value)) {
                $this->processArray($value, $key, $key, $subnode);
            } else {
                $metadata = $this->getReflectionClassMeta($value);
                $subnodeArray = $subnode->appendChild("{$metadata->getRootElementName()}");
                $this->arrayToXml($value, $subnodeArray);
            }
        }
        return true;
    }

    protected function processObject($parsedValue, $name, $xml): bool
    {
        if (!is_object($parsedValue)) {
            return false;
        }
        $subnode = $xml->appendChild("$name");
        $this->arrayToXml($parsedValue, $subnode);

        return true;
    }

    protected function processScalar($parsedValue, $rootMetadata, $isAttribute, $isAttributeOf, $name, XmlNode $xml): bool
    {
        if ($isAttribute === true) {
            $xml->addAttribute($name, htmlspecialchars("$parsedValue"));
        } else if (!empty($isAttributeOf)) {
            $xml->selectSingleNode($isAttributeOf)->addAttribute($name, htmlspecialchars("$parsedValue"));
        } else {
            $xml->appendChild($rootMetadata->getUsePrefix() . $name, htmlspecialchars("$parsedValue"));
        }

        return true;
    }

}
