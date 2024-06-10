<?php

namespace ByJG\Util;

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

}
