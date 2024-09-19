<?php

namespace ByJG\XmlUtil;

use ByJG\Serializer\Serialize;
use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;
use ByJG\XmlUtil\Exception\XmlUtilException;
use DOMException;
use ReflectionAttribute;
use ReflectionClass;
use stdClass;

class EntityParser
{
    protected array $properties = [];

    /**
     * @param object|array $serializable
     * @return string
     * @throws XmlUtilException
     * @throws DOMException
     */
    public function parse(object|array $serializable): string
    {
        /** @var XmlDocument $xml */
        $metadata = $this->getReflectionClassMeta($serializable);
        $xml = new XmlDocument("<{$metadata->getRootElementName()}></{$metadata->getRootElementName()}>");
        $this->addNamespaces($xml, $metadata);

        $this->arrayToXml($serializable, $xml, $metadata);

        if (!$metadata->getXmlDeclaration()) {
            return substr($xml->toString(), 39);
        }
        return $xml->toString();
    }

    /**
     * @param array|object $object
     * @param XmlNode|null $xml
     * @return XmlEntity
     * @throws DOMException
     * @throws XmlUtilException
     */
    protected function getReflectionClassMeta(array|object $object): XmlEntity
    {
        if (is_array($object) || $object instanceof stdClass) {
            return new XmlEntity(rootElementName: 'root', namespaces: [], xmlDeclaration: true, usePrefix: null);
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
                xmlDeclaration: $tableAttribute->getXmlDeclaration(),
                usePrefix: !empty($tableAttribute->getUsePrefix()) ? $tableAttribute->getUsePrefix() . ":" : null
            );
        } else {
            $classParts = explode('\\', $reflection->getName());
            return new XmlEntity(rootElementName: $reflection->isAnonymous() ? 'root' : strtolower(end($classParts)), namespaces: [], xmlDeclaration: true, usePrefix: null);
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

    protected function parseProperties(object $object): ?array
    {
        if ($object instanceof stdClass) {
            return null;
        }

        if (isset($this->properties[get_class($object)])) {
            return $this->properties[get_class($object)];
        }

        $this->properties[get_class($object)] = [];

        $reflection = new ReflectionClass($object);
        if ($reflection->isAnonymous()) {
            return null;
        }

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(XmlProperty::class, ReflectionAttribute::IS_INSTANCEOF);
            if (count($attributes) == 0) {
                continue;
            }
            $this->properties[get_class($object)][$property->getName()] = $attributes[0]->newInstance();
        }

        return $this->properties[get_class($object)];
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
        if (empty($rootMetadata)) {
            $rootMetadata = $this->getReflectionClassMeta($array);
            $this->addNamespaces($xml, $rootMetadata);
        }

        $transformer = function (?XmlProperty $property, $parsedValue, $propertyName) use ($xml, $rootMetadata) {
            $name = $property?->getElementName() ?? $propertyName;
            $name = ($property?->getPreserveCaseName() ?? false) ? $name : strtolower($name);
            $isAttribute = $property?->getIsAttribute();

            $this->processArray($parsedValue, $propertyName, $name, $xml)
                || $this->processObject($parsedValue, $name, $xml)
                || $this->processScalar($parsedValue, $rootMetadata, $isAttribute, $name, $xml);
        };

        Serialize::from($array)
            ->withStopAtFirstLevel()
            ->parseAttributes(
                XmlProperty::class,
                ReflectionAttribute::IS_INSTANCEOF,
                $transformer
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
            $subnode = $xml->appendChild("item"  . $name);
        }
        foreach ($parsedValue as $key => $value) {
            if (is_scalar($value)) {
                $subnode->appendChild((!is_numeric($key) ? $key : "item"), htmlspecialchars("$value"));
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

    protected function processScalar($parsedValue, $rootMetadata, $isAttribute, $name, XmlNode $xml): bool
    {
        if ($isAttribute) {
            $xml->addAttribute($name, htmlspecialchars("$parsedValue"));
        } else {
            $xml->appendChild($rootMetadata->getUsePrefix() . $name, htmlspecialchars("$parsedValue"));
        }

        return true;
    }

}