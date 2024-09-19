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
        $classMeta = $this->getReflectionClassMeta($serializable);

        $xml = new XmlDocument("<{$classMeta->getRootElementName()}></{$classMeta->getRootElementName()}>");
        foreach ($classMeta->getNamespaces() as $prefix => $uri) {
            $xml->addNamespace($prefix, $uri);
        }
        $this->arrayToXml($serializable, $xml, $classMeta);

        if (!$classMeta->getXmlDeclaration()) {
            return substr($xml->toString(), 39);
        }
        return $xml->toString();
    }

    /**
     * @param array|object $object
     * @return XmlEntity
     * @throws XmlUtilException
     */
    protected function getReflectionClassMeta(array|object $object): XmlEntity
    {
        $meta = [];

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
            foreach ($rootMetadata->getNamespaces() as $prefix => $uri) {
                $xml->addNamespace($prefix, $uri);
            }
            if (!empty($rootMetadata->getUsePrefix())) {
                $xml->renameNode($rootMetadata->getUsePrefix() . $xml->DOMNode()->nodeName);
            }
        }

        $transformer = function (?XmlProperty $property, $parsedValue, $propertyName) use ($xml, $rootMetadata) {
            $name = $property?->getElementName() ?? $propertyName;
            $name = ($property?->getPreserveCaseName() ?? false) ? $name : strtolower($name);
            $isAttribute = $property?->getIsAttribute();

            if (is_array($parsedValue)) {
                if (!is_numeric($propertyName)) {
                    $subnode = $xml->appendChild("$name");
                } else {
                    $subnode = $xml->appendChild("item"  . $name);
                }
                $this->arrayToXml($parsedValue, $subnode);
            } elseif (is_object($parsedValue)) {
                $subnode = $xml->appendChild("$name");
                $this->arrayToXml($parsedValue, $subnode);
            } else {
                if ($isAttribute) {
                    $xml->addAttribute($name, htmlspecialchars("$parsedValue"));
                } else {
                    $xml->appendChild($rootMetadata->getUsePrefix() . $name, htmlspecialchars("$parsedValue"));
                }
            }
        };

        Serialize::from($array)
            ->withStopAtFirstLevel()
            ->parseAttributes(
                XmlProperty::class,
                ReflectionAttribute::IS_INSTANCEOF,
                $transformer
            );
    }

}