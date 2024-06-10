<?php

namespace ByJG\Util;

use ByJG\Util\Exception\XmlUtilException;
use DOMDocument;
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
     * @param DOMNode $node
     * @param string $name
     * @param string $uri
     * @return DOMNode
     * @throws XmlUtilException|DOMException
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
     * @param DOMNode $rootNode Parent node
     * @param string $nodeName Node to add string
     * @param string $nodeText Text to add string
     * @param string $uri
     * @return DOMNode
     * @throws DOMException
     * @throws XmlUtilException
     */
    public function createChild(string $nodeName, string $nodeText = "", string $uri = ""): XmlNode
    {
        $nodeWorking = $this->createChildNode($nodeName, $uri);
        $nodeWorking->addTextNode($nodeText);
        $this->DOMNode()->appendChild($nodeWorking->DOMNode());
        return $nodeWorking;
    }


    /**
     * Create child node on the top from specific node and add text
     *
     * @param DOMNode $rootNode Parent node
     * @param string $nodeName Node to add string
     * @param string $nodeText Text to add string
     * @param int $position
     * @return DOMNode
     * @throws DOMException
     * @throws XmlUtilException
     */
    public function createChildBefore(string $nodeName, string $nodeText, int $position = 0): XmlNode
    {
        $nodeWorking = $this->createChildNode($nodeName);
        $nodeWorking->addTextNode($nodeText);
        $rootNode = $this->DOMNode()->parentNode;
        $rootNode->insertBefore($nodeWorking->DOMNode(), $this->DOMNode());
        return $nodeWorking;
    }

    /**
     * Add text to node
     *
     * @param DOMNode $rootNode Parent node
     * @param string $text Text to add String
     * @param bool $escapeChars (True create CData instead Text node)
     */
    public function addTextNode(string $text, bool $escapeChars = false): XmlNode
    {
        if (empty($text)) {
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
     * @param DOMElement $rootNode Node to receive attribute
     * @param string $name Attribute name string
     * @param string $value Attribute value string
     * @return DOMNode
     * @throws DOMException
     * @throws XmlUtilException
     */
    public function addAttribute(string $name, string $value): XmlNode
    {
        // @todo: check prefix

        $owner = $this->DOMDocument();

        $attrNode = $owner->createAttribute($name);
        $attrNode->value = $value;
        $this->DOMNode()->setAttributeNode($attrNode);

        return $this;
    }

    /**
     * Returns a \DOMNodeList from a relative xPath from other \DOMNode
     *
     * @param DOMNode $pNode
     * @param string $xPath
     * @param array $arNamespace
     * @return DOMNodeList
     */
    public function selectNodes(string $xPath, array $arNamespace = []): DOMNodeList
    {
        if (preg_match('~^/[^/]~', $xPath)) {
            $xPath = substr($xPath, 1);
        }

        $owner = $this->DOMDocument();
        $xpath = new DOMXPath($owner);
        foreach ($arNamespace as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }

        $this->errorHandler();
        try {
            return $xpath->query($xPath, $this->DOMNode());
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Returns a \DOMElement from a relative xPath from other \DOMNode
     *
     * @param DOMNode $pNode
     * @param string $xPath xPath string format
     * @param array $arNamespace
     * @return DOMNode
     */
    public function selectSingleNode(string $xPath, array $arNamespace = []): XmlNode
    {
        $rNodeList = $this->selectNodes($xPath, $arNamespace);

        return new XmlNode($rNodeList->item(0));
    }

    /**
     * Concat a xml string in the node
     *
     * @param DOMNode $node
     * @param string $xmlString
     * @return DOMNode
     * @throws XmlUtilException
     */
    public function innerXML(string $xmlString): XmlNode
    {
        $xmlString = str_replace("<br>", "<br/>", $xmlString);
        $len = strlen($xmlString);
        $endText = "";
        $close = strrpos($xmlString, '>');
        if ($close !== false && $close < $len - 1) {
            $endText = substr($xmlString, $close + 1);
            $xmlString = substr($xmlString, 0, $close + 1);
        }
        $open = strpos($xmlString, '<');
        if ($open === false) {
            $this->DOMNode()->nodeValue .= $xmlString;
        } else {
            if ($open > 0) {
                $text = substr($xmlString, 0, $open);
                $xmlString = substr($xmlString, $open);
                $this->DOMNode()->nodeValue .= $text;
            }
            $dom = $this->DOMDocument();
            $xmlString = "<rootxml>$xmlString</rootxml>";
            $sxe = @simplexml_load_string($xmlString);
            if ($sxe === false) {
                throw new XmlUtilException("Cannot load XML string.", 252);
            }
            $domSimpleXml = dom_import_simplexml($sxe);
            $domSimpleXml = $dom->importNode($domSimpleXml, true);
            $children = $domSimpleXml->childNodes->length;
            for ($i = 0; $i < $children; $i++) {
                $this->DOMNode()->appendChild($domSimpleXml->childNodes->item($i)->cloneNode(true));
            }

            if (!empty($endText) && $endText != "") {
                $textNode = $dom->createTextNode($endText);
                $this->DOMNode()->appendChild($textNode);
            }
        }
        return new XmlNode($this->DOMNode()->firstChild);
    }

    /**
     * Return the tree nodes in a simple text
     *
     * @param DOMNode $node
     * @return string
     * @throws XmlUtilException
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
     * @param DOMNode $node
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
     * @param DOMDocument $dom
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


    public function toArray(\Closure $func = null): array
    {
        return $this->_toArray($this->DOMNode(), $func);
    }

    protected function _toArray(SimpleXMLElement|DOMNode|array $arr, \Closure|null $func): array
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
     * @param string $prefix
     * @param string $uri
     */
    public function addNamespace(string $prefix, string $uri): void
    {
        $this->DOMDocument()->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', "xmlns:$prefix", $uri);
    }

    public function importNodes(DOMNode|File $source, string $nodeToAdd)
    {
        $sourceDoc = new XmlDocument($source);

        $nodes = $sourceDoc->DOMDocument()->getElementsByTagName($nodeToAdd)->item(0)->childNodes;
        foreach ($nodes as $node) {
            $newNode = $this->DOMDocument()->importNode($node, true);
            $this->DOMNode()->appendChild($newNode);
        }
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

    protected function errorHandler()
    {
        set_error_handler(function ($number, $error) {
            throw new XmlUtilException($error);
        });

    }

}
