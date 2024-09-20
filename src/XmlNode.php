<?php

namespace ByJG\XmlUtil;

use ByJG\XmlUtil\Exception\XmlUtilException;
use Closure;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use SimpleXMLElement;

class XmlNode
{
    protected DOMNode $node;

    public function __construct(DOMNode $node)
    {
        $this->node = $node;
    }

    public static function instance(DOMNode $node): XmlNode
    {
        return new self($node);
    }

    /**
     * @param string $name
     * @param string $uri
     * @return XmlNode
     * @throws XmlUtilException
     * @throws DOMException
     */
    protected function createChildNode(string $name, string $uri = ""): XmlNode
    {
        $owner = $this->DOMDocument();

        if (empty($uri)) {
            // @todo: check if namespace is defined.
            $newNode = $owner->createElement(preg_replace('/[^\w:]/', '_', $name));
        } else {
            $newNode = $owner->createElementNS($uri, $name);
        }

        if ($newNode === false) {
            throw new XmlUtilException("Failed to create \DOMElement.", 258);
        }

        return new XmlNode($newNode);
    }

    /**
     * Append child node from specific node and add text
     *
     * @param string $nodeName Node to add string
     * @param string $nodeText Text to add string
     * @param string $uri
     * @return XmlNode
     * @throws DOMException
     * @throws XmlUtilException
     */
    public function appendChild(string $nodeName, string $nodeText = "", string $uri = ""): XmlNode
    {
        $nodeWorking = $this->createChildNode($nodeName, $uri);
        $nodeWorking->addText($nodeText);
        $this->DOMNode()->appendChild($nodeWorking->DOMNode());
        return $nodeWorking;
    }

    /**
     * @param array|object $object
     * @throws DOMException
     * @throws XmlUtilException
     */
    public function appendObject(array|object $object): void
    {
        $parser = new EntityParser();
        $parser->arrayToXml($object, $this);
    }


    /**
     * Create child node on the top from specific node and add text
     *
     * @param string $nodeName Node to add string
     * @param string $nodeText Text to add string
     * @param int $position
     * @return XmlNode
     * @throws DOMException
     * @throws XmlUtilException
     */
    public function insertBefore(string $nodeName, string $nodeText, int $position = 0): XmlNode
    {
        $nodeWorking = $this->createChildNode($nodeName);
        $nodeWorking->addText($nodeText);
        $rootNode = $this->DOMNode()->parentNode;
        $rootNode->insertBefore($nodeWorking->DOMNode(), $this->DOMNode());
        return $nodeWorking;
    }

    /**
     * Add text to node
     *
     * @param string $text Text to add String
     * @param bool $escapeChars (True create CData instead Text node)
     * @return XmlNode
     */
    public function addText(string $text, bool $escapeChars = false): XmlNode
    {
        if (trim($text) === "") {
            return $this;
        }

        $owner = $this->DOMDocument();

        if ($escapeChars) {
            $nodeWorkingText = $owner->createCDATASection($text);
        } else {
            $nodeWorkingText = $owner->createTextNode($text);
        }
        $this->DOMNode()->appendChild($nodeWorkingText);

        return $this;
    }

    /**
     * Add a attribute to specific node
     *
     * @param string $name Attribute name string
     * @param string $value Attribute value string
     * @return XmlNode
     * @throws DOMException
     */
    public function addAttribute(string $name, string $value): XmlNode
    {
        // @todo: check prefix

        $owner = $this->DOMDocument();

        $attrNode = $owner->createAttribute($name);
        $attrNode->value = $value;

        /** @var DOMElement $node */
        $node = $this->DOMNode();
        $node->setAttributeNode($attrNode);

        return $this;
    }

    /**
     * Returns a \DOMNodeList from a relative xPath from other \DOMNode
     *
     * @param string $path
     * @param array $arNamespace
     * @return DOMNodeList
     * @throws XmlUtilException
     */
    public function selectNodes(string $path, array $arNamespace = []): DOMNodeList
    {
        if (preg_match('~^/[^/]~', $path)) {
            $path = substr($path, 1);
        }

        $owner = $this->DOMDocument();
        $xpath = new DOMXPath($owner);
        foreach ($arNamespace as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }

        $response = false;
        $this->executeLibXmlCommand("Error selecting nodes.", function () use ($xpath, $path, &$response) {
            $response = $xpath->query($path, $this->DOMNode());
        });
        return $response;
    }

    /**
     * Returns a \DOMElement from a relative xPath from other \DOMNode
     *
     * @param string $xPath xPath string format
     * @param array $arNamespace
     * @return XmlNode
     * @throws XmlUtilException
     */
    public function selectSingleNode(string $xPath, array $arNamespace = []): XmlNode
    {
        $rNodeList = $this->selectNodes($xPath, $arNamespace);

        return new XmlNode($rNodeList->item(0));
    }

    /**
     * Return the tree nodes in a simple text
     *
     * @return string
     */
    public function innerText(): string
    {
        $result = "";
        $childNodes = $this->DOMNode()->childNodes;

        foreach ($childNodes as $node) {
            $result .= XmlNode::instance($node)->toString();
        }

        return $result;
    }

    /**
     * Remove a specific node
     *
     * @return XmlNode
     */
    public function removeNode(): XmlNode
    {
        $nodeParent = $this->DOMNode()->parentNode;
        $nodeParent->removeChild($this->DOMNode());

        return new XmlNode($nodeParent);
    }

    /**
     * Remove a node specified by your tag name. You must pass a \DOMDocument ($node->ownerDocument);
     *
     * @param string $tagName
     * @return bool
     */
    public function removeTagName(string $tagName): bool
    {
        $nodeList = $this->DOMDocument()->getElementsByTagName($tagName);
        if ($nodeList->length > 0) {
            XmlNode::instance($nodeList->item(0))->removeNode();
            return true;
        } else {
            return false;
        }
    }


    public function toArray(Closure $func = null): array
    {
        return $this->_toArray($this->DOMNode(), $func);
    }

    protected function _toArray(SimpleXMLElement|DOMNode|array $arr, Closure|null $func): array
    {
        if ($arr instanceof SimpleXMLElement) {
            return $this->_toArray((array) $arr, $func);
        }

        if ($arr instanceof DOMNode) {
            return $this->_toArray((array) simplexml_import_dom($arr), $func);
        }

        $newArr = array();
        if (!empty($arr)) {
            foreach ($arr as $key => $value) {
                $newArr[$key] =
                    (
                    is_array($value)
                    || ($value instanceof DOMNode)
                    || ($value instanceof SimpleXMLElement)
                        ? $this->_toArray($value, $func)
                        : (!empty($func) ? $func($value) : $value)
                    );
            }
        }

        return $newArr;
    }

    /**
     * @param string|null $prefix
     * @param string $uri
     */
    public function addNamespace(?string $prefix, string $uri): void
    {
        if (empty($prefix)) {
            $prefix = "xmlns";
        } elseif (is_numeric($prefix)) {
            throw new XmlUtilException("The prefix must be a string or a null element", 256);
        } else {
            $prefix = "xmlns:$prefix";
        }

        $this->DOMDocument()->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', $prefix, $uri);
    }

    /**
     * @param DOMNode|File $source
     * @param string $nodeToAdd
     * @throws XmlUtilException
     */
    public function importNodes(DOMNode|File $source, string $nodeToAdd): void
    {
        $sourceDoc = new XmlDocument($source);

        $nodes = $sourceDoc->DOMDocument()->getElementsByTagName($nodeToAdd)->item(0)->childNodes;
        foreach ($nodes as $node) {
            $newNode = $this->DOMDocument()->importNode($node, true);
            $this->DOMNode()->appendChild($newNode);
        }
    }

    /**
     * @return XmlNode
     * @throws DOMException
     */
    public function renameNode(string $newName): XmlNode
    {
        $newNode = $this->node->ownerDocument->createElement($newName);
        if ($this->node->attributes->length) {
            foreach ($this->node->attributes as $attribute) {
                $newNode->setAttribute($attribute->nodeName, $attribute->nodeValue);
            }
        }
        while ($this->node->firstChild) {
            $newNode->appendChild($this->node->firstChild);
        }
        $this->node->parentNode->replaceChild($newNode, $this->node);

        $this->node = $newNode;
        return $this;
    }


    public function DOMNode(): DOMNode
    {
        if ($this->node instanceof DOMDocument) {
            return $this->node->documentElement;
        }
        return $this->node;
    }

    public function DOMDocument(): DOMDocument
    {
        if ($this->node instanceof DOMDocument) {
            return $this->node;
        } else {
            return $this->node->ownerDocument;
        }
    }

    /**
     * Get document without xml parameters
     *
     * @param bool $format
     * @return string
     */
    public function toString(bool $format = false): string
    {
        if ($this->node instanceof DOMDocument) {
            return $this->_toString($this->node, $format);
        }

        return $this->_toString($this->DOMDocument(), $format, $this->node);
    }

    protected function _toString(DOMDocument $domDocument, bool $format = false, DOMNode $node = null): string
    {
        if (!$format) {
            return $domDocument->saveXML($node);
        }

        $oldValue = $domDocument->preserveWhiteSpace;
        $oldFormatOutput = $domDocument->formatOutput;

        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = true;
        $str = $domDocument->saveXML($node);

        $domDocument->preserveWhiteSpace = $oldValue;
        $domDocument->formatOutput = $oldFormatOutput;

        return $str;
    }

    /**
     * @param string $errorMessage
     * @param Closure $function
     * @param bool $throwError
     * @return array
     * @throws XmlUtilException
     */
    protected function executeLibXmlCommand(string $errorMessage, Closure $function, bool $throwError = true): ?array
    {
        $restore = libxml_use_internal_errors(true);

        $function();

        $errorList = [];
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            $errorList[] = $error->message;
        }
        libxml_clear_errors();
        libxml_use_internal_errors($restore);

        if (empty($errorList)) {
            return null;
        }

        if ($throwError) {
            throw new XmlUtilException("$errorMessage\n - " . implode("\n - ", $errorList), 257);
        }

        return $errorList;
    }
}
