<?php

namespace ByJG\Util;

define('XMLUTIL_OPT_DONT_PRESERVE_WHITESPACE', 0x01);
define('XMLUTIL_OPT_FORMAT_OUTPUT', 0x02);
define('XMLUTIL_OPT_DONT_FIX_AMPERSAND', 0x04);

use ByJG\Util\Exception\XmlUtilException;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use SimpleXMLElement;

/**
 * Generic functions to manipulate XML nodes.
 * Note: This classes didn't inherit from \DOMDocument or \DOMNode
 */
class XmlUtil
{
    /**
     * XML document version
     * @var string
     */
    const XML_VERSION = "1.0";

    /**
     * XML document encoding
     * @var string
     */
    const XML_ENCODING = "utf-8";

    public static array $xmlNsPrefix = [];

    /**
     * Create an empty XmlDocument object with some default parameters
     *
     * @param int $docOptions
     * @return DOMDocument object
     */
    public static function createXmlDocument(int $docOptions = 0): DOMDocument
    {
        $xmldoc = new DOMDocument(self::XML_VERSION, self::XML_ENCODING);
        $xmldoc->preserveWhiteSpace =
            ($docOptions & XMLUTIL_OPT_DONT_PRESERVE_WHITESPACE) != XMLUTIL_OPT_DONT_PRESERVE_WHITESPACE
        ;
        $xmldoc->formatOutput = false;
        if (($docOptions & XMLUTIL_OPT_FORMAT_OUTPUT) == XMLUTIL_OPT_FORMAT_OUTPUT) {
            $xmldoc->preserveWhiteSpace = false;
            $xmldoc->formatOutput = true;
        }
        XmlUtil::$xmlNsPrefix[spl_object_hash($xmldoc)] = array();
        return $xmldoc;
    }

    /**
     * Create a XmlDocument object from a file saved on disk.
     *
     * @param string $filename
     * @param int $docOptions
     * @return DOMDocument
     * @throws XmlUtilException
     */
    public static function createXmlDocumentFromFile(string $filename, int $docOptions = XMLUTIL_OPT_DONT_FIX_AMPERSAND): DOMDocument
    {
        if (!file_exists($filename)) {
            throw new XmlUtilException("Xml document $filename not found.", 250);
        }
        $xml = file_get_contents($filename);
        return self::createXmlDocumentFromStr($xml, $docOptions);
    }

    /**
     * Create XML \DOMDocument from a string
     *
     * @param string $xml - XML string document
     * @param int $docOptions
     * @return DOMDocument
     * @throws XmlUtilException
     */
    public static function createXmlDocumentFromStr(string $xml, int $docOptions = XMLUTIL_OPT_DONT_FIX_AMPERSAND): DOMDocument
    {
        set_error_handler(function ($number, $error) {
            $matches = [];
            if (preg_match('/^DOMDocument::loadXML\(\): (.+)$/', $error, $matches) === 1) {
                throw new InvalidArgumentException("[Err #$number] ".$matches[1]);
            }
        });

        $xmldoc = self::createXmlDocument($docOptions);

        $xmlFixed = XmlUtil::fixXmlHeader($xml);
        if (($docOptions & XMLUTIL_OPT_DONT_FIX_AMPERSAND) != XMLUTIL_OPT_DONT_FIX_AMPERSAND) {
            $xmlFixed = str_replace("&amp;", "&", $xmlFixed);
        }

        $xmldoc->loadXML($xmlFixed);

        XmlUtil::extractNamespaces($xmldoc);

        restore_error_handler();

        return $xmldoc;
    }

    /**
     * Create a \DOMDocumentFragment from a node
     *
     * @param DOMNode $node
     * @param int $docOptions
     * @return DOMDocument
     */
    public static function createDocumentFromNode(DOMNode $node, int $docOptions = 0): DOMDocument
    {
        $xmldoc = self::createXmlDocument($docOptions);
        XmlUtil::$xmlNsPrefix[spl_object_hash($xmldoc)] = array();
        $root = $xmldoc->importNode($node, true);
        $xmldoc->appendChild($root);
        return $xmldoc;
    }

    /**
     * @param DOMNode $nodeOrDoc
     */
    protected static function extractNamespaces(DOMNode $nodeOrDoc): void
    {
        $doc = XmlUtil::getOwnerDocument($nodeOrDoc);

        $hash = spl_object_hash($doc);
        $root = $doc->documentElement;

        #--
        $xpath = new DOMXPath($doc);
        foreach ($xpath->query('namespace::*', $root) as $node) {
            XmlUtil::$xmlNsPrefix[$hash][$node->prefix] = $node->nodeValue;
        }
    }

    /**
     * Adjust xml string to the proper format
     *
     * @param string $string - XML string document
     * @return string - Return the string converted
     * @throws XmlUtilException
     */
    public static function fixXmlHeader(string $string): string
    {
        $string = XmlUtil::removeBom($string);

        if (str_contains($string, "<?xml")) {
            $xmltagend = strpos($string, "?>");
            if ($xmltagend !== false) {
                $xmltagend += 2;
                $xmlheader = substr($string, 0, $xmltagend);

                if ($xmlheader == "<?xml?>") {
                    $xmlheader = "<?xml ?>";
                }
            } else {
                throw new XmlUtilException("XML header bad formatted.", 251);
            }

            // Complete header elements
            $count = 0;
            $xmlheader = preg_replace(
                "/version=([\"'][\w\-.]+[\"'])/",
                "version=\"".self::XML_VERSION."\"",
                $xmlheader,
                1,
                $count
            );
            if ($count == 0) {
                $xmlheader = substr($xmlheader, 0, 6)."version=\"".self::XML_VERSION."\" ".substr($xmlheader, 6);
            }
            $count = 0;
            $xmlheader = preg_replace(
                "/encoding=([\"'][\w\-.]+[\"'])/",
                "encoding=\"".self::XML_ENCODING."\"",
                $xmlheader,
                1,
                $count
            );
            if ($count == 0) {
                $xmlheader = substr($xmlheader, 0, 6)."encoding=\"".self::XML_ENCODING."\" ".substr($xmlheader, 6);
            }

            // Fix header position (first version, after encoding)
            $xmlheader = preg_replace(
                "/<\?([\w\W]*)\s+(encoding=([\"'][\w\-.]+[\"']))\s+(version=([\"'][\w\-.]+[\"']))\s*\?>/",
                "<?\\1 \\4 \\2?>",
                $xmlheader,
                1,
                $count
            );

            return $xmlheader.substr($string, $xmltagend);
        } else {
            $xmlheader = '<?xml version="'.self::XML_VERSION.'" encoding="'.self::XML_ENCODING.'"?>';
            return $xmlheader.$string;
        }
    }

    /**
     *
     * @param DOMDocument $document
     * @param string $filename
     * @throws XmlUtilException
     */
    public static function saveXmlDocument(DOMDocument $document, string $filename): void
    {
        $ret = $document->save($filename);
        if ($ret === false) {
            throw new XmlUtilException("Cannot save XML Document in $filename.", 256);
        }
    }

    /**
     * Get document without xml parameters
     *
     * @param DOMDocument $xml
     * @return string
     */
    public static function getFormattedDocument(DOMDocument $xml): string
    {
        $oldValue = $xml->preserveWhiteSpace;
        $oldFormatOutput = $xml->formatOutput;

        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $document = $xml->saveXML();

        $xml->preserveWhiteSpace = $oldValue;
        $xml->formatOutput = $oldFormatOutput;

        return $document;
    }

    /**
     * @param DOMNode $nodeOrDoc
     * @param string $prefix
     * @param string $uri
     * @throws XmlUtilException
     */
    public static function addNamespaceToDocument(DOMNode $nodeOrDoc, string $prefix, string $uri): void
    {
        $doc = XmlUtil::getOwnerDocument($nodeOrDoc);

        $hash = spl_object_hash($doc);
        $root = $doc->documentElement;

        if ($root === null) {
            throw new XmlUtilException("Node or document is invalid. Cannot retrieve 'documentElement'.");
        }

        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', "xmlns:$prefix", $uri);
        XmlUtil::$xmlNsPrefix[$hash][$prefix] = $uri;
    }

    /**
     * Add node to specific XmlNode from file existing on disk
     *
     * @param DOMNode $rootNode XmlNode receives node
     * @param string $filename File to import node
     * @param string $nodetoadd Node to be added
     * @throws XmlUtilException
     * @throws Exception
     */
    public static function addNodeFromFile(DOMNode $rootNode, string $filename, string $nodetoadd): void
    {
        if (!file_exists($filename)) {
            throw new Exception("File $filename not found");
        }

        $source = XmlUtil::createXmlDocumentFromFile($filename);

        $nodes = $source->getElementsByTagName($nodetoadd)->item(0)->childNodes;

        foreach ($nodes as $node) {
            $newNode = $rootNode->ownerDocument->importNode($node, true);
            $rootNode->appendChild($newNode);
        }
    }

    /**
     * Attention: NODE MUST BE AN ELEMENT NODE!!!
     *
     * @param DOMNode $source
     * @param DOMNode $nodeToAdd
     */
    public static function addNodeFromNode(DOMNode $source, DOMNode $nodeToAdd): void
    {
        if ($nodeToAdd->hasChildNodes()) {
            $nodeList = $nodeToAdd->childNodes; // It is necessary because Zend Core For Oracle didn't support
            // access the property Directly.
            foreach ($nodeList as $node) {
                $owner = XmlUtil::getOwnerDocument($source);
                $newNode = $owner->importNode($node, true);
                $source->appendChild($newNode);
            }
        }
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
    public static function createChild(DOMNode $rootNode, string $nodeName, string $nodeText = "", string $uri = ""): DOMNode
    {
        if (empty($nodeName)) {
            throw new XmlUtilException("Node name must be a string.");
        }

        $nodeworking = XmlUtil::createChildNode($rootNode, $nodeName, $uri);
        self::addTextNode($nodeworking, $nodeText);
        $rootNode->appendChild($nodeworking);
        return $nodeworking;
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
    public static function createChildBefore(DOMNode $rootNode, string $nodeName, string $nodeText, int $position = 0): DOMNode
    {
        return self::createChildBeforeNode($nodeName, $nodeText, $rootNode->childNodes->item($position));
    }

    /**
     * @param string $nodeName
     * @param string $nodeText
     * @param DOMNode $node
     * @return DOMNode
     * @throws DOMException
     * @throws XmlUtilException
     */
    public static function createChildBeforeNode(string $nodeName, string $nodeText, DOMNode $node): DOMNode
    {
        $rootNode = $node->parentNode;
        $nodeworking = XmlUtil::createChildNode($rootNode, $nodeName);
        self::addTextNode($nodeworking, $nodeText);
        $rootNode->insertBefore($nodeworking, $node);
        return $nodeworking;
    }

    /**
     * Add text to node
     *
     * @param DOMNode $rootNode Parent node
     * @param string $text Text to add String
     * @param bool $escapeChars (True create CData instead Text node)
     */
    public static function addTextNode(DOMNode $rootNode, string $text, bool $escapeChars = false): void
    {
        if (!empty($text) || is_numeric($text)) {
            $owner = XmlUtil::getOwnerDocument($rootNode);
            if ($escapeChars) {
                $nodeworkingText = $owner->createCDATASection($text);
            } else {
                $nodeworkingText = $owner->createTextNode($text);
            }
            $rootNode->appendChild($nodeworkingText);
        }
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
    public static function addAttribute(DOMElement $rootNode, string $name, string $value): DOMNode
    {
        XmlUtil::checkIfPrefixWasDefined($rootNode, $name);

        $owner = XmlUtil::getOwnerDocument($rootNode);
        $attrNode = $owner->createAttribute($name);
        $attrNode->value = $value;
        $rootNode->setAttributeNode($attrNode);
        return $rootNode;
    }

    /**
     * Returns a \DOMNodeList from a relative xPath from other \DOMNode
     *
     * @param DOMNode $pNode
     * @param string $xPath
     * @param array $arNamespace
     * @return DOMNodeList
     */
    public static function selectNodes(DOMNode $pNode, string $xPath, array $arNamespace = []): DOMNodeList
    {
        if (preg_match('~^/[^/]~', $xPath)) {
            $xPath = substr($xPath, 1);
        }

        $owner = XmlUtil::getOwnerDocument($pNode);
        $xpath = new DOMXPath($owner);
        XmlUtil::registerNamespaceForFilter($xpath, $arNamespace);
        return $xpath->query($xPath, $pNode);
    }

    /**
     * Returns a \DOMElement from a relative xPath from other \DOMNode
     *
     * @param DOMNode $pNode
     * @param string $xPath xPath string format
     * @param array $arNamespace
     * @return DOMElement
     */
    public static function selectSingleNode(DOMNode $pNode, string $xPath, array $arNamespace = []): DOMNode
    {
        $rNodeList = self::selectNodes($pNode, $xPath, $arNamespace);

        return $rNodeList->item(0);
    }

    /**
     *
     * @param DOMXPath $xpath
     * @param array $arNamespace
     */
    public static function registerNamespaceForFilter(DOMXPath $xpath, array $arNamespace = []): void
    {
        foreach ($arNamespace as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }
    }

    /**
     * Concat a xml string in the node
     *
     * @param DOMNode $node
     * @param string $xmlstring
     * @return DOMNode
     * @throws XmlUtilException
     */
    public static function innerXML(DOMNode $node, string $xmlstring): DOMNode
    {
        $xmlstring = str_replace("<br>", "<br/>", $xmlstring);
        $len = strlen($xmlstring);
        $endText = "";
        $close = strrpos($xmlstring, '>');
        if ($close !== false && $close < $len - 1) {
            $endText = substr($xmlstring, $close + 1);
            $xmlstring = substr($xmlstring, 0, $close + 1);
        }
        $open = strpos($xmlstring, '<');
        if ($open === false) {
            $node->nodeValue .= $xmlstring;
        } else {
            if ($open > 0) {
                $text = substr($xmlstring, 0, $open);
                $xmlstring = substr($xmlstring, $open);
                $node->nodeValue .= $text;
            }
            $dom = XmlUtil::getOwnerDocument($node);
            $xmlstring = "<rootxml>$xmlstring</rootxml>";
            $sxe = @simplexml_load_string($xmlstring);
            if ($sxe === false) {
                throw new XmlUtilException("Cannot load XML string.", 252);
            }
            $domSimpleXml = dom_import_simplexml($sxe);
            $domSimpleXml = $dom->importNode($domSimpleXml, true);
            $childs = $domSimpleXml->childNodes->length;
            for ($i = 0; $i < $childs; $i++) {
                $node->appendChild($domSimpleXml->childNodes->item($i)->cloneNode(true));
            }
        }
        if (!empty($endText) && $endText != "") {
            $textNode = $dom->createTextNode($endText);
            $node->appendChild($textNode);
        }
        return $node->firstChild;
    }

    /**
     * Return the tree nodes in a simple text
     *
     * @param DOMNode $node
     * @return string
     * @throws XmlUtilException
     */
    public static function innerText(DOMNode $node): string
    {
        $doc = XmlUtil::createDocumentFromNode($node);
        return self::copyChildNodesFromNodeToString($doc);
    }

    /**
     * Return the tree nodes in a simple text
     *
     * @param DOMNode $node
     * @return string
     * @throws XmlUtilException
     */
    public static function copyChildNodesFromNodeToString(DOMNode $node): string
    {
        $xmlstring = "<rootxml></rootxml>";
        $doc = self::createXmlDocumentFromStr($xmlstring);

        $root = $doc->firstChild;
        $childlist = $node->firstChild->childNodes; // It is necessary because Zend Core For Oracle didn't support
        // access the property Directly.
        foreach ($childlist as $child) {
            $cloned = $doc->importNode($child, true);
            $root->appendChild($cloned);
        }
        $string = $doc->saveXML();
        $string = str_replace('<?xml version="'.self::XML_VERSION.'" encoding="'.self::XML_ENCODING.'"?>', '', $string);
        $string = str_replace('<rootxml>', '', $string);
        return str_replace('</rootxml>', '', $string);
    }

    /**
     * Return the part node in xml document
     *
     * @param DOMNode $node
     * @return string
     */
    public static function saveXmlNodeToString(DOMNode $node): string
    {
        $doc = XmlUtil::getOwnerDocument($node);
        return $doc->saveXML($node);
    }

    /**
     * Convert <br/> to \n
     *
     * @param string $str
     * @return mixed
     */
    public static function br2nl(string $str): string
    {
        return str_replace("<br />", "\n", $str);
    }

    /**
     * Assist you to Debug XMLs string documents. Echo in out buffer.
     *
     * @param string $val
     * @return string
     */
    public static function showXml(string $val): string
    {
        return "<pre>".htmlentities($val)."</pre>";
    }

    /**
     * Remove a specific node
     *
     * @param DOMNode $node
     */
    public static function removeNode(DOMNode $node): void
    {
        $nodeParent = $node->parentNode;
        $nodeParent->removeChild($node);
    }

    /**
     * Remove a node specified by your tag name. You must pass a \DOMDocument ($node->ownerDocument);
     *
     * @param DOMDocument $domdocument
     * @param string $tagname
     * @return bool
     */
    public static function removeTagName(DOMDocument $domdocument, string $tagname): bool
    {
        $nodeLista = $domdocument->getElementsByTagName($tagname);
        if ($nodeLista->length > 0) {
            $node = $nodeLista->item(0);
            XmlUtil::removeNode($node);
            return true;
        } else {
            return false;
        }
    }

    public static function xml2Array(SimpleXMLElement|DOMNode|array $arr, string $func = ""): array
    {
        if ($arr instanceof SimpleXMLElement) {
            return XmlUtil::xml2Array((array) $arr, $func);
        }

        if ($arr instanceof DOMNode) {
            return XmlUtil::xml2Array((array) simplexml_import_dom($arr), $func);
        }

        $newArr = array();
        if (!empty($arr)) {
            foreach ($arr as $key => $value) {
                $newArr[$key] =
                    (
                        is_array($value)
                        || ($value instanceof DOMNode)
                        || ($value instanceof SimpleXMLElement)
                        ? XmlUtil::xml2Array($value, $func)
                        : (!empty($func) ? $func($value) : $value)
                    );
            }
        }

        return $newArr;
    }

    /**
     *
     * @param mixed $node
     * @return DOMDocument
     */
    protected static function getOwnerDocument(DOMNode $node): DOMDocument
    {
        if ($node instanceof DOMDocument) {
            return $node;
        } else {
            return $node->ownerDocument;
        }
    }

    /**
     * @param DOMNode $node
     * @param string $name
     * @param string $uri
     * @return DOMNode
     * @throws XmlUtilException|DOMException
     */
    protected static function createChildNode(DOMNode $node, string $name, string $uri = ""): DOMNode
    {
        if ($uri == "") {
            XmlUtil::checkIfPrefixWasDefined($node, $name);
        }

        $owner = self::getOwnerDocument($node);

        if ($uri == "") {
            $newnode = $owner->createElement(preg_replace('/[^\w:]/', '_', $name));
        } else {
            $newnode = $owner->createElementNS($uri, $name);
            if ($owner === $node) {
                $tok = strtok($name, ":");
                if ($tok != $name) {
                    XmlUtil::$xmlNsPrefix[spl_object_hash($owner)][$tok] = $uri;
                }
            }
        }

        if ($newnode === false) {
            throw new XmlUtilException("Failed to create \DOMElement.", 258);
        }
        return $newnode;
    }

    /**
     * @param DOMNode $node
     * @param string $name
     * @throws XmlUtilException
     */
    protected static function checkIfPrefixWasDefined(DOMNode $node, string $name): void
    {
        $owner = self::getOwnerDocument($node);
        $hash = spl_object_hash($owner);

        $prefix = strtok($name, ":");
        if (($prefix != $name) && !array_key_exists($prefix, XmlUtil::$xmlNsPrefix[$hash])) {
            throw new XmlUtilException(
                "You cannot create the node/attribute $name without define the URI. "
                . "Try to use XmlUtil::AddNamespaceToDocument."
            );
        }
    }

    public static function removeBom(string $xmlStr): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $xmlStr);
    }
}
