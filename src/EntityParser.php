<?php
/**
 * This file contains the EntityParser class for converting PHP objects and arrays to XML.
 * 
 * @package ByJG\XmlUtil
 * @author Joao Gilberto Magalhaes
 * @copyright Copyright (c) 2023 ByJG
 * @license https://opensource.org/licenses/MIT MIT
 */

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

/**
 * Class for converting PHP objects and arrays to XML.
 * 
 * This class provides methods to transform PHP objects and arrays into XML structures,
 * respecting property attributes and metadata for customizing the XML output.
 */
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
     * Converts an array or object to XML nodes
     * 
     * @param object|array $array The data to convert to XML
     * @param XmlNode $xml The XML node to append data to
     * @param XmlEntity|null $rootMetadata Metadata for the root element
     * @throws DOMException
     * @throws XmlUtilException
     */
    public function arrayToXml(object|array $array, XmlNode $xml, ?XmlEntity $rootMetadata = null): void
    {
        // Initialize root metadata if not provided
        if (empty($rootMetadata)) {
            $rootMetadata = $this->getReflectionClassMeta($array);
            if (empty($rootMetadata)) {
                throw new XmlUtilException("The root element must be defined", 258);
            }
            $this->addNamespaces($xml, $rootMetadata);
        }

        // Handle anonymous classes as a special case
        if (is_object($array) && (new ReflectionClass($array))->isAnonymous()) {
            $this->processAnonymousClass($array, $xml, $rootMetadata);
            return;
        }

        // Define property transformer function
        $transformer = function (?XmlProperty $property, $parsedValue, $propertyName) use ($xml, $rootMetadata) {
            // Skip processing if needed
            if ($this->shouldSkipProperty($property, $parsedValue)) {
                return false;
            }

            // Get normalized property name
            $name = $this->getNormalizedPropertyName($property, $propertyName, $rootMetadata);
            
            // Process the value based on its type
            return $this->processPropertyValue(
                $parsedValue, 
                $propertyName, 
                $name, 
                $xml, 
                $rootMetadata, 
                $property
            );
        };

        // Process object/array properties
        Serialize::from($array)
            ->withStopAtFirstLevel()
            ->parseAttributes($transformer, XmlProperty::class);
    }

    /**
     * Determine if a property should be skipped during XML conversion
     * 
     * @param XmlProperty|null $property
     * @param mixed $parsedValue
     * @return bool
     */
    private function shouldSkipProperty(?XmlProperty $property, $parsedValue): bool
    {
        // Skip if explicitly ignored
        if ($property?->getIgnore() ?? false) {
            return true;
        }
        
        // Skip empty values if ignoreEmpty is set
        if ($property?->getIgnoreEmpty() ?? false) {
            if (!is_numeric($parsedValue)) {
                if (is_string($parsedValue)) {
                    $parsedValue = trim($parsedValue);
                }

                if (empty($parsedValue)) {
                    return true;
                }
            }
        }
        
        // Skip if explicity mapping is required but no attribute name is set
        if (!($property?->getElementName()) && $this->explicityMap) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get the normalized property name for XML conversion
     * 
     * @param XmlProperty|null $property
     * @param string $propertyName
     * @param XmlEntity $rootMetadata
     * @return string
     */
    private function getNormalizedPropertyName(?XmlProperty $property, string $propertyName, XmlEntity $rootMetadata): string
    {
        $name = $property?->getElementName() ?? $propertyName;
        $preserveCase = ($property?->getPreserveCaseName() ?? false) || $rootMetadata->getPreserveCaseNameChild();
        
        return $preserveCase ? $name : strtolower($name);
    }
    
    /**
     * Process a property value and add it to the XML structure
     * 
     * @param mixed $parsedValue
     * @param string $propertyName
     * @param string $name
     * @param XmlNode $xml
     * @param XmlEntity $rootMetadata
     * @param XmlProperty|null $property
     * @return bool
     * @throws DOMException
     * @throws XmlUtilException
     */
    private function processPropertyValue(
        mixed $parsedValue, 
        string $propertyName, 
        string $name, 
        XmlNode $xml, 
        XmlEntity $rootMetadata, 
        ?XmlProperty $property
    ): bool {
        $isAttribute = $property?->isAttribute();
        $isAttributeOf = $property?->getIsAttributeOf();
        $childOf = $property?->getChildOf();
        
        // Process value based on its type
        return $this->processArray($parsedValue, $propertyName, $name, $xml)
            || $this->processObject($parsedValue, $name, $childOf, $xml)
            || $this->processScalar($parsedValue, $rootMetadata, $isAttribute, $isAttributeOf, $name, $childOf, $xml);
    }

    /**
     * Process an anonymous class and extract its properties using getters
     *
     * @param object $anonymousClass
     * @param XmlNode $xml
     * @param XmlEntity $rootMetadata
     * @throws DOMException
     * @throws XmlUtilException
     */
    private function processAnonymousClass(object $anonymousClass, XmlNode $xml, XmlEntity $rootMetadata): void
    {
        // Get all methods to find getters
        $methods = get_class_methods($anonymousClass);
        
        foreach ($methods as $method) {
            // Only process getter methods
            if (str_starts_with($method, 'get') && strlen($method) > 3) {
                $propertyName = lcfirst(substr($method, 3));
                $value = $anonymousClass->$method();
                
                // Skip null values
                if ($value !== null) {
                    // Add property values directly to XML nodes
                    $this->processScalar($value, $rootMetadata, false, null, $propertyName, null, $xml);
                }
            }
        }
    }

    /**
     * Process an array value and convert it to XML structure
     * 
     * @param mixed $parsedValue The array to process
     * @param string $propertyName The property name
     * @param string $name The XML node name
     * @param XmlNode $xml The parent XML node
     * @return bool Whether the value was processed
     * @throws DOMException
     * @throws XmlUtilException
     */
    protected function processArray(mixed $parsedValue, string $propertyName, string $name, XmlNode $xml): bool
    {
        // Only process array values
        if (!is_array($parsedValue)) {
            return false;
        }
        
        // Create container node for array values if needed
        $subnode = is_numeric($propertyName) ? $xml : $xml->appendChild("$name");
        $createAgain = false;
        
        foreach ($parsedValue as $key => $value) {
            if (is_scalar($value)) {
                $this->processScalarArrayItem($key, $value, $subnode, $createAgain);
                // Update flag for consecutive scalar values with numeric keys
                $createAgain = is_numeric($key);
            } elseif (is_array($value)) {
                $this->processArray($value, $key, $key, $subnode);
            } else {
                // Handle objects in array
                $metadata = $this->getReflectionClassMeta($value);
                $subnodeArray = $subnode->appendChild("{$metadata->getRootElementName()}");
                $this->arrayToXml($value, $subnodeArray);
            }
        }
        
        return true;
    }

    /**
     * Process a scalar value within an array
     *
     * @param mixed $key Array key
     * @param mixed $value Array value
     * @param XmlNode $subnode Parent XML node
     * @param bool $createAgain Whether to create a new node
     * @return void
     * @throws DOMException
     * @throws XmlUtilException
     */
    private function processScalarArrayItem(mixed $key, mixed $value, XmlNode $subnode, bool &$createAgain): void
    {
        $safeValue = htmlspecialchars("$value");
        
        if (!is_numeric($key)) {
            // For associative arrays, create key-named elements
            $subnode->appendChild($key, $safeValue);
        } else {
            // For numeric keys, add content to parent or create new element
            if ($createAgain) {
                $subnode = $subnode->parentNode()->appendChild($subnode->DOMNode()->nodeName);
            }
            $subnode->addText($safeValue);
        }
    }

    /**
     * Process an object value and convert it to XML
     *
     * @param mixed $parsedValue The object to process
     * @param string $name The XML node name
     * @param string|null $childOf Parent node selector if it should be a child of another element
     * @param XmlNode $xml The parent XML node
     * @return bool Whether the value was processed
     * @throws XmlUtilException
     * @throws DOMException
     */
    protected function processObject(mixed $parsedValue, string $name, ?string $childOf, XmlNode $xml): bool
    {
        if (!is_object($parsedValue)) {
            return false;
        }

        // Determine the parent node based on childOf attribute
        $parentNode = !empty($childOf) ? $xml->selectSingleNode($childOf) : $xml;
        
        // Create and populate the node
        $subnode = $parentNode->appendChild($name);
        $this->arrayToXml($parsedValue, $subnode);

        return true;
    }

    /**
     * Process a scalar value and add it to XML
     *
     * @param mixed $parsedValue The scalar value to process
     * @param XmlEntity $rootMetadata Root element metadata
     * @param bool|null $isAttribute Whether the value should be an attribute
     * @param string|null $isAttributeOf Parent node selector if it should be an attribute of another element
     * @param string $name The XML node name or attribute name
     * @param string|null $isChildOf Parent node selector if it should be a child of another element
     * @param XmlNode $xml The parent XML node
     * @return bool Always returns true
     * @throws DOMException
     * @throws XmlUtilException
     */
    protected function processScalar(
        mixed $parsedValue, 
        XmlEntity $rootMetadata, 
        ?bool $isAttribute, 
        ?string $isAttributeOf, 
        string $name, 
        ?string $isChildOf, 
        XmlNode $xml
    ): bool {
        $safeValue = htmlspecialchars("$parsedValue");
        
        if ($isAttribute === true) {
            // Add as an attribute of the current node
            $xml->addAttribute($name, $safeValue);
        } elseif (!empty($isChildOf)) {
            // Add as a child element of a specified node
            $xml->selectSingleNode($isChildOf)->appendChild($name, $safeValue);
        } elseif (!empty($isAttributeOf)) {
            // Add as an attribute of a specified node
            $xml->selectSingleNode($isAttributeOf)->addAttribute($name, $safeValue);
        } else {
            // Add as a normal child element with optional namespace prefix
            $xml->appendChild($rootMetadata->getUsePrefix() . $name, $safeValue);
        }

        return true;
    }
}
