<?php

namespace ByJG\XmlUtil;

use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;
use ByJG\XmlUtil\Exception\XmlUtilException;
use DOMException;
use ReflectionAttribute;
use ReflectionClass;
use stdClass;
use function Symfony\Component\String\s;

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

        $xml = new XmlDocument("<{$classMeta['name']}></{$classMeta['name']}>");
        foreach ($classMeta['namespace'] as $prefix => $uri) {
            $xml->addNamespace($prefix, $uri);
        }
        $this->arrayToXml($serializable, $xml);

        if (!$classMeta['header']) {
            return substr($xml->toString(), 39);
        }
        return $xml->toString();
    }

    /**
     * @param array|object $object
     * @return array
     * @throws XmlUtilException
     */
    protected function getReflectionClassMeta(array|object $object): array
    {
        $meta = [];

        if (is_array($object) || $object instanceof stdClass) {
            $meta['name'] = 'root';
            $meta['header'] = true;
            $meta['namespace'] = [];
            return $meta;
        }

        $reflection = new ReflectionClass($object);
        $attributes = $reflection->getAttributes(XmlEntity::class, ReflectionAttribute::IS_INSTANCEOF);
        if (count($attributes) > 1) {
            throw new XmlUtilException("Entity '{$reflection->getName()}' has more than one TableAttribute", 258);
        } elseif (count($attributes) == 1) {
            $tableAttribute = $attributes[0]->newInstance();
            $meta['name'] = $tableAttribute->getRootElementName() ?? $reflection->getShortName();
            $meta['header'] = $tableAttribute->getXmlDeclaration();
            $meta['namespace'] = $tableAttribute->getNamespaces();
            $meta['name'] = $tableAttribute->getPreserveCaseName() ? $meta['name'] : strtolower($meta['name']);
            return $meta;
        } else {
            $classParts = explode('\\', $reflection->getName());
            $meta['name'] = $reflection->isAnonymous() ? 'root' : strtolower(end($classParts));
            $meta['header'] = true;
            $meta['namespace'] = [];
            return $meta;
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
     * @throws DOMException
     * @throws XmlUtilException
     */
    public function arrayToXml(object|array $array, XmlNode $xml): void
    {
        /** @var XmlProperty[]|null $properties */
        $properties = null;
        if (is_object($array)) {
            $properties = $this->parseProperties($array);
            $array = (array)$array;
            foreach ($array as $key => $value) {
                $keyParts = explode("\0", $key);
                $keyNew = end($keyParts);
                if ($keyNew == $key) {
                    continue;
                }
                $array[$keyNew] = $value;
                unset($array[$key]);
            }
        }

        foreach ($array as $key => $value) {
            $name = isset($properties[$key]) ? ($properties[$key]->getElementName() ?? $key) : $key;
            $name = isset($properties[$key]) ? ($properties[$key]->getPreserveCaseName() ? $name : strtolower($name)) : strtolower($name);
            $isAttribute = isset($properties[$key]) && $properties[$key]->getIsAttribute();
                
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $xml->appendChild("$name");
                } else {
                    $subnode = $xml->appendChild("item"  . $name);
                }
                $this->arrayToXml($value, $subnode);
            } elseif (is_object($value)) {
                $subnode = $xml->appendChild("$name");
                $this->arrayToXml($value, $subnode);
            } else {
                if ($isAttribute) {
                    $xml->addAttribute($name, htmlspecialchars("$value"));
                } else {
                    $xml->appendChild("$name", htmlspecialchars("$value"));
                }       
            }
        }
    }

}