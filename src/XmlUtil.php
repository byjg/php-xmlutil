<?php

namespace ByJG\Util;

define('XMLUTIL_OPT_DONT_PRESERVE_WHITESPACE', 0x01);
define('XMLUTIL_OPT_FORMAT_OUTPUT', 0x02);
define('XMLUTIL_OPT_DONT_FIX_AMPERSAND', 0x04);

use ByJG\Util\Exception\XmlUtilException;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use SimpleXMLElement;

/**
* Generic functions to manipulate XML nodes.
* Note: This classes didn't inherits from \DOMDocument or \DOMNode
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

	public static $XMLNSPrefix = array();

	/**
	* Create an empty XmlDocument object with some default parameters
	*
	* @return DOMDocument object
	*/
	public static function createXmlDocument($docOptions = 0)
	{
		$xmldoc = new DOMDocument(self::XML_VERSION , self::XML_ENCODING );
		$xmldoc->preserveWhiteSpace = ($docOptions & XMLUTIL_OPT_DONT_PRESERVE_WHITESPACE) != XMLUTIL_OPT_DONT_PRESERVE_WHITESPACE;
		if (($docOptions & XMLUTIL_OPT_FORMAT_OUTPUT) == XMLUTIL_OPT_FORMAT_OUTPUT)
		{
			$xmldoc->preserveWhiteSpace = false;
			$xmldoc->formatOutput = true;
		}
		XmlUtil::$XMLNSPrefix[spl_object_hash($xmldoc)] = array();
		return $xmldoc;
	}

	/**
	* Create a XmlDocument object from a file saved on disk.
	* @param string $filename
	* @return DOMDocument
	*/
	public static function createXmlDocumentFromFile($filename, $docOptions = XMLUTIL_OPT_DONT_FIX_AMPERSAND)
	{
		if (!file_exists($filename)) {
			throw new XmlUtilException("Xml document $filename not found.", 250);
		}
		$xml = file_get_contents($filename);
		$xmldoc = self::createXmlDocumentFromStr($xml, true, $docOptions);
		return $xmldoc;
	}

	/**
	* Create XML \DOMDocument from a string
	* @param string $xml - XML string document
	* @return DOMDocument
	*/
	public static function createXmlDocumentFromStr($xml, $docOptions = XMLUTIL_OPT_DONT_FIX_AMPERSAND)
	{
		set_error_handler(function($number, $error){
			if (preg_match('/^DOMDocument::loadXML\(\): (.+)$/', $error, $m) === 1) {
				throw new \InvalidArgumentException($m[1]);
			}
		});

		$xmldoc = self::createXmlDocument($docOptions);

        $xml = XmlUtil::fixXmlHeader($xml);
		if (($docOptions & XMLUTIL_OPT_DONT_FIX_AMPERSAND) != XMLUTIL_OPT_DONT_FIX_AMPERSAND) {
            $xml = str_replace("&amp;", "&", $xml);
        }

        $xmldoc->loadXML($xml);

		XmlUtil::extractNameSpaces($xmldoc);

		restore_error_handler();

		return $xmldoc;
	}

	/**
	* Create a \DOMDocumentFragment from a node
	* @param DOMNode $node
	* @return DOMDocument
	*/
	public static function createDocumentFromNode($node, $docOptions = 0)
	{
		$xmldoc = self::createXmlDocument($docOptions);
		XmlUtil::$XMLNSPrefix[spl_object_hash($xmldoc)] = array();
		$root = $xmldoc->importNode($node, true);
		$xmldoc->appendChild($root);
		return $xmldoc;
	}

	protected static function extractNameSpaces($nodeOrDoc)
	{
		$doc = XmlUtil::getOwnerDocument($nodeOrDoc);

		$hash = spl_object_hash($doc);
		$root = $doc->documentElement;

		#--
		$xpath = new DOMXPath($doc);
		foreach( $xpath->query('namespace::*', $root) as $node )
		{
			XmlUtil::$XMLNSPrefix[$hash][$node->prefix] = $node->nodeValue;
		}
	}

	/**
	* Adjust xml string to the proper format
	* @param string $string - XML string document
	* @return string - Return the string converted
	*/
	public static function fixXmlHeader($string)
	{
		if(strpos($string, "<?xml") !== false)
		{
			$xmltagend = strpos($string, "?>");
			if ($xmltagend !== false)
			{
				$xmltagend += 2;
				$xmlheader = substr($string, 0, $xmltagend);
			}
			else
			{
				throw new XmlUtilException("XML header bad formatted.", 251);
			}

			// Complete header elements
			$count = 0;
			$xmlheader = preg_replace("/version=([\"'][\w\d\-\.]+[\"'])/", "version=\"" . self::XML_VERSION . "\"", $xmlheader, 1, $count);
			if ($count == 0)
			{
				$xmlheader = substr($xmlheader, 0, 6)  . "version=\"" . self::XML_VERSION . "\" " . substr($xmlheader, 6);
			}
			$count = 0;
			$xmlheader = preg_replace("/encoding=([\"'][\w\d\-\.]+[\"'])/", "encoding=\"" . self::XML_ENCODING . "\"", $xmlheader, 1, $count);
			if ($count == 0)
			{
				$xmlheader = substr($xmlheader, 0, 6)  . "encoding=\"" . self::XML_ENCODING . "\" " . substr($xmlheader, 6);
			}

			// Fix header position (first version, after encoding)
			$xmlheader = preg_replace(
				"/<\?([\w\W]*)\s+(encoding=([\"'][\w\d\-\.]+[\"']))\s+(version=([\"'][\w\d\-\.]+[\"']))\s*\?>/",
				"<?\\1 \\4 \\2?>", $xmlheader, 1, $count);

			return $xmlheader . substr($string, $xmltagend);
		}
		else
		{
			$xmlheader = '<?xml version="' . self::XML_VERSION  . '" encoding="' . self::XML_ENCODING  .'"?>';
			return $xmlheader . $string;
		}

	}

	/**
	 *
	 * @param DOMDocument $document
	 * @param string $filename
	 * @throws XmlUtilException
	 */
	public static function saveXmlDocument($document, $filename)
	{
		if (!($document instanceof DOMDocument))
		{
			throw new XmlUtilException("Object isn't a \DOMDocument.", 255); // Document não é um documento XML
		}
		else
		{
			$ret = $document->save($filename);
			if ($ret === false)
			{
				throw new XmlUtilException("Cannot save XML Document in $filename.", 256); // Não foi possível gravar o arquivo: PERMISSÂO ou CAMINHO não existe;
			}
		}
	}


	/**
	 * Get document without xml parameters
	 *
	 * @param DOMDocument $xml
	 * @return string
	 */
	public static function getFormattedDocument($xml)
	{
		$document = $xml->saveXML();
		$i = strpos($document, "&#");
		while ($i!=0)
		{
			$char = substr($document, $i, 5);
			$document = substr($document, 0, $i) . chr(hexdec($char)) . substr($document, $i+6);
			$i = strpos($document, "&#");
		}
		return $document;
	}


	/**
	 *
	 * @param type $nodeOrDoc
	 */
	public static function addNamespaceToDocument($nodeOrDoc, $prefix, $uri)
	{
		$doc = XmlUtil::getOwnerDocument($nodeOrDoc);

		if ($doc == null) {
            throw new XmlUtilException("Node or document is invalid.");
        }

        $hash = spl_object_hash($doc);
		$root = $doc->documentElement;

		if ($root == null) {
            throw new XmlUtilException("Node or document is invalid. Cannot retrieve 'documentElement'.");
        }

        $root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,"xmlns:$prefix", $uri);
		XmlUtil::$XMLNSPrefix[$hash][$prefix] = $uri;
	}

	/**
	* Add node to specific XmlNode from file existing on disk
	*
	* @param DOMNode $rootNode XmlNode receives node
	* @param string $filename File to import node
	* @param string $nodetoadd Node to be added
	*/
	public static function addNodeFromFile($rootNode, $filename, $nodetoadd)
	{
		if ($rootNode == null)
		{
			return;
		}
		if (!file_exists($filename))
		{
			return;
		}

		try
		{
			// \DOMDocument
			$source = XmlUtil::createXmlDocumentFromFile($filename);

			$nodes = $source->getElementsByTagName($nodetoadd)->item(0)->childNodes;

			foreach ($nodes as $node)
			{
				$newNode = $rootNode->ownerDocument->importNode($node, true);
				$rootNode->appendChild($newNode);
			}
		}
		catch (\Exception $ex)
		{
			throw $ex;
		}
	}

	/**
	* Attention: NODE MUST BE AN ELEMENT NODE!!!
	*
	* @param DOMElement $source
	* @param DOMElement $nodeToAdd
	*/
	public static function addNodeFromNode($source, $nodeToAdd)
	{
		if ($nodeToAdd->hasChildNodes())
		{
			$nodeList = $nodeToAdd->childNodes; // It is necessary because Zend Core For Oracle didn't support
			// access the property Directly.
			foreach ($nodeList as $node)
			{
				$owner = XmlUtil::getOwnerDocument($source);
				$newNode = $owner->importNode($node,TRUE);
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
	* @return DOMElement
	*/
	public static function createChild($rootNode, $nodeName, $nodeText="", $uri="")
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
	* @return DOMElement
	*/
	public static function createChildBefore($rootNode, $nodeName, $nodeText, $position = 0)
	{
		return self::createChildBeforeNode($nodeName, $nodeText, $rootNode->childNodes->item($position));
	}

	public static function createChildBeforeNode($nodeName, $nodeText, $node)
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
	public static function addTextNode($rootNode, $text, $escapeChars = false)
	{
		if (!empty($text) || is_numeric($text))
		{
			$owner = XmlUtil::getOwnerDocument($rootNode);
			if ($escapeChars)
			{
				$nodeworkingText = $owner->createCDATASection($text);
			}
			else
			{
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
	* @return DOMElement
	*/
	public static function addAttribute($rootNode, $name, $value)
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
	 * @param node $pNode
	 * @param string $xPath
	 * @param array $arNamespace
	 * @return DOMNodeList
	 */
	public static function selectNodes($pNode, $xPath, $arNamespace = null) // <- Retorna N&#65533;!
	{
        if (preg_match('~^/[^/]~', $xPath)) {
			$xPath = substr($xPath, 1);
        }

        $owner = XmlUtil::getOwnerDocument($pNode);
        $xp = new DOMXPath($owner);
        XmlUtil::registerNamespaceForFilter($xp, $arNamespace);
        $rNodeList = $xp->query($xPath, $pNode);

		return $rNodeList;
	}

	/**
	 * Returns a \DOMElement from a relative xPath from other \DOMNode
	 *
	 * @param DOMElement $pNode
	 * @param string $xPath - xPath string format
	 * @param array $arNamespace
	 * @return DOMElement
	 */
	public static function selectSingleNode($pNode, $xPath, $arNamespace = null) // <- Retorna
	{
        $rNodeList = self::selectNodes($pNode, $xPath, $arNamespace);

        return $rNodeList->item(0);
	}

	/**
	 *
	 * @param DOMXPath $xpath
	 * @param array $arNamespace
	 */
	public static function registerNamespaceForFilter($xpath, $arNamespace)
	{
		if (($arNamespace != null) && (is_array($arNamespace)))
		{
			foreach ($arNamespace as $prefix=>$uri)
			{
				$xpath->registerNamespace($prefix, $uri);
			}
		}
	}

	/**
	* Concat a xml string in the node
	* @param DOMNode $node
	* @param string $xmlstring
	* @return DOMNode
	*/
	public static function innerXML($node, $xmlstring)
	{
		$xmlstring = str_replace("<br>", "<br/>", $xmlstring);
		$len = strlen($xmlstring);
		$endText = "";
		$close = strrpos($xmlstring, '>');
		if ($close !== false && $close < $len-1)
		{
			$endText = substr($xmlstring, $close+1);
			$xmlstring = substr($xmlstring, 0, $close+1);
		}
		$open = strpos($xmlstring, '<');
		if($open === false)
		{
			$node->nodeValue .= $xmlstring;
		}
		else
		{
			if ($open > 0) {
				$text = substr($xmlstring, 0, $open);
				$xmlstring = substr($xmlstring, $open);
				$node->nodeValue .= $text;
			}
			$dom = XmlUtil::getOwnerDocument($node);
			$xmlstring = "<rootxml>$xmlstring</rootxml>";
			$sxe = @simplexml_load_string($xmlstring);
			if ($sxe === false)
			{
				throw new XmlUtilException("Cannot load XML string.", 252);
			}
			$dom_sxe = dom_import_simplexml($sxe);
			if (!$dom_sxe)
			{
				throw new XmlUtilException("XML Parsing error.", 253);
			}
			$dom_sxe = $dom->importNode($dom_sxe, true);
			$childs = $dom_sxe->childNodes->length;
			for ($i=0; $i<$childs; $i++)
			{
				$node->appendChild($dom_sxe->childNodes->item($i)->cloneNode(true));
			}
		}
		if (!empty($endText) && $endText != "")
		{
			$textNode = $dom->createTextNode($endText);
			$node->appendChild($textNode);
		}
		return $node->firstChild;
	}

	/**
	* Return the tree nodes in a simple text
	* @param DOMNode $node
	* @return DOMNode
	*/
	public static function innerText($node)
	{
		$doc = XmlUtil::createDocumentFromNode($node);
		return self::copyChildNodesFromNodeToString($doc);
	}

	/**
	* Return the tree nodes in a simple text
	* @param DOMNode $node
	* @return DOMNode
	*/
	public static function copyChildNodesFromNodeToString($node)
	{
		$xmlstring = "<rootxml></rootxml>";
		$doc = self::createXmlDocumentFromStr($xmlstring);
		$string = '';
		$root = $doc->firstChild;
		$childlist = $node->firstChild->childNodes; // It is necessary because Zend Core For Oracle didn't support
		// access the property Directly.
		foreach ($childlist as $child)
		{
			$cloned = $doc->importNode($child, true);
			$root->appendChild($cloned);
		}
		$string = $doc->saveXML();
		$string = str_replace('<?xml version="' . self::XML_VERSION . '" encoding="' . self::XML_ENCODING . '"?>', '', $string);
		$string = str_replace('<rootxml>', '', $string);
		$string = str_replace('</rootxml>', '', $string);
		return $string;
	}

	/**
	* Return the part node in xml document
	* @param DOMNode $node
	* @return string
	*/
	public static function saveXmlNodeToString($node)
	{
		$doc = XmlUtil::getOwnerDocument($node);
		$string = $doc->saveXML($node);
		return $string;
	}

	/**
	 * Convert <br/> to \n
	 *
	 * @param string $str
	 */
	public static function br2nl($str)
	{
		return str_replace("<br />", "\n", $str);
	}

	/**
	 * Assist you to Debug XMLs string documents. Echo in out buffer.
	 *
	 * @param string $val
	 */
	public static function showXml($val)
	{
		print "<pre>" . htmlentities($val) . "</pre>";
	}

	/**
	 * Remove a specific node
	 *
	 * @param DOMNode $node
	 */
	public static function removeNode($node)
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
	public static function removeTagName($domdocument, $tagname)
	{
		$nodeLista = $domdocument->getElementsByTagName($tagname);
		if ($nodeLista->length > 0)
		{
			$node = $nodeLista->item(0);
			XmlUtil::removeNode($node);
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function xml2Array($arr, $func = "")
	{
		if ($arr instanceof SimpleXMLElement)
		{
			return XmlUtil::xml2Array((array)$arr, $func);
		}

		if (($arr instanceof DOMElement) || ($arr instanceof DOMDocument))
		{
			return XmlUtil::xml2Array((array)simplexml_import_dom($arr), $func);
		}

		$newArr = array();
		if (!empty($arr))
		{
			foreach($arr AS $key => $value)
			{
				$newArr[$key] =
					(is_array($value) || ($value instanceof DOMElement) || ($value instanceof DOMDocument) || ($value instanceof SimpleXMLElement) ? XmlUtil::xml2Array($value, $func) : (
							!empty($func) ? $func($value) : $value
						)
					);
			}
		}

		return $newArr;
	}

	/**
	 *
	 * @param DOMNode $node
	 * @return DOMDocument
	 * @throws XmlUtilException
	 */
	protected static function getOwnerDocument( $node )
	{
		if (!($node instanceof DOMNode))
		{
			throw new XmlUtilException("Object isn't a \DOMNode. Found object class type: " . get_class($node), 257);
		}

		if ($node instanceof DOMDocument) {
            return $node;
        } else {
            return $node->ownerDocument;
        }
    }

	/**
	 *
	 * @param DOMNode $node
	 * @param string $name
	 * @param string $uri
	 * @return type
	 * @throws XmlUtilException
	 */
	protected static function createChildNode( $node, $name, $uri="" )
	{
		if ($uri == "") {
            XmlUtil::checkIfPrefixWasDefined($node, $name);
        }

        $owner = self::getOwnerDocument($node);

		if ($uri == "")
		{
			$newnode = $owner->createElement(preg_replace('/[^\w:]/', '_', $name));
		}
		else
		{
			$newnode = $owner->createElementNS($uri, $name);
			if ($owner == $node)
			{
				$tok = strtok($name, ":");
				if ($tok != $name) {
                    XmlUtil::$XMLNSPrefix[spl_object_hash($owner)][$xml2jsontok] = $uri;
                }
            }
		}

		if($newnode === false)
		{
			throw new XmlUtilException("Failed to create \DOMElement.", 258);
		}
		return $newnode;
	}

	/**
	 *
	 * @param type $node
	 * @param type $name
	 * @throws \Exception
	 */
	protected static function checkIfPrefixWasDefined( $node, $name )
	{
		$owner = self::getOwnerDocument($node);
		$hash = spl_object_hash($owner);

		$prefix = strtok($name, ":");
		if (($prefix != $name) && !array_key_exists($prefix, XmlUtil::$XMLNSPrefix[$hash]))
		{
			throw new XmlUtilException("You cannot create the node/attribute $name without define the URI. Try to use XmlUtil::AddNamespaceToDocument.");
		}
	}

}
