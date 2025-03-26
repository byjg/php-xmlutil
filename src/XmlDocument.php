<?php

namespace ByJG\XmlUtil;

use ByJG\XmlUtil\Exception\FileException;
use ByJG\XmlUtil\Exception\XmlUtilException;
use DOMDocument;
use DOMException;
use DOMNode;

class XmlDocument extends XmlNode
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

    protected DOMDocument $document;

    /**
     * @param string|DOMNode|File|XmlNode|null $source
     * @param bool $preserveWhiteSpace
     * @param bool $formatOutput
     * @param bool $fixAmpersand
     * @throws FileException
     * @throws XmlUtilException
     */
    public function __construct(string|DOMNode|File|XmlNode|null $source = null, bool $preserveWhiteSpace = false, bool $formatOutput = false, bool $fixAmpersand = false)
    {
        $xmlDoc = new DOMDocument(self::XML_VERSION, self::XML_ENCODING);
        $xmlDoc->preserveWhiteSpace = $preserveWhiteSpace;
        $xmlDoc->formatOutput = $formatOutput;

        if ($source instanceof File || is_string($source)) {
            $contents = $source;
            if (!is_string($contents)) {
                $contents = $source->getContents();
            }
            $xmlFixed = $this->fixXmlHeader($contents);
            if ($fixAmpersand) {
                $xmlFixed = str_replace("&amp;", "&", $xmlFixed);
            }
            $this->executeLibXmlCommand("Error loading XML Document.", function () use ($xmlDoc, $xmlFixed) {
                $xmlDoc->loadXML($xmlFixed);
            });
        } else if ($source instanceof DOMDocument) {
            $xmlDoc = $source;
        } else if ($source instanceof XmlNode) {
            $root = $xmlDoc->importNode($source->DOMNode(), true);
            $xmlDoc->appendChild($root);
        } else if ($source instanceof DOMNode) {
            $root = $xmlDoc->importNode($source, true);
            $xmlDoc->appendChild($root);
        }

        $this->document = $xmlDoc;

        parent::__construct($this->document);
    }

    /**
     * @throws DOMException
     * @throws XmlUtilException
     * @throws FileException
     */
    public static function emptyDocument(string $name, ?string $namespace = null): XmlDocument
    {
        $xmlDoc = new DOMDocument(self::XML_VERSION, self::XML_ENCODING);

        if (empty($namespace)) {
            $element = $xmlDoc->createElement($name);
        } else {
            $element = $xmlDoc->createElementNS($namespace, $name);
        }
        $xmlDoc->appendChild($element);

        return new XmlDocument($xmlDoc);
    }


    /**
     * Adjust xml string to the proper format
     *
     * @param string $string XML string document
     * @return string Return the string converted
     * @throws XmlUtilException
     */
    protected function fixXmlHeader(string $string): string
    {
        $string = $this->removeBom($string);

        // If no XML declaration exists, add one
        if (strncmp($string, '<?xml', 5) !== 0) {
            return '<?xml version="' . self::XML_VERSION . '" encoding="' . self::XML_ENCODING . '"?>' . $string;
        }

        // Find the end of XML declaration
        $xmlTagEnd = strpos($string, "?>");
        if ($xmlTagEnd === false) {
            throw new XmlUtilException("XML header bad formatted.", 251);
        }
        $xmlTagEnd += 2;

        // Extract header content
        $xmlHeader = substr($string, 0, $xmlTagEnd);

        // Handle empty XML declaration
        if ($xmlHeader === '<?xml?>') {
            return '<?xml version="' . self::XML_VERSION . '" encoding="' . self::XML_ENCODING . '"?>' . substr($string, $xmlTagEnd);
        }

        // Parse all attributes
        $attributes = [];
        $attributeOrder = [];
        
        // Match all attributes and their order, handling malformed declarations
        if (preg_match_all('/(\w+)\s*=\s*(["\']?)([^"\'\s?>]+)\\2/', $xmlHeader, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = $match[1];
                $value = $match[3];
                
                if ($name === 'version') {
                    // Only allow version 1.0 or 1.1
                    $attributes[$name] = ($value === '1.0' || $value === '1.1') ? $value : self::XML_VERSION;
                } elseif ($name === 'encoding') {
                    // Normalize encoding value
                    $value = strtolower($value);
                    $attributes[$name] = ($value === 'utf8') ? 'utf-8' : $value;
                } elseif ($name === 'standalone') {
                    // Handle standalone attribute
                    $value = strtolower($value);
                    if ($value === 'yes' || $value === 'no') {
                        $attributes[$name] = $value;
                    }
                }
                
                // Keep track of attribute order
                $attributeOrder[] = $name;
            }
        }

        // If no attributes were found in a malformed declaration, use defaults
        if (empty($attributes)) {
            return '<?xml version="' . self::XML_VERSION . '" encoding="' . self::XML_ENCODING . '"?>' . substr($string, $xmlTagEnd);
        }

        // Build the header preserving original attribute order
        $headerParts = [];

        // Always add version first
        $headerParts[] = 'version="' . (isset($attributes['version']) ? $attributes['version'] : self::XML_VERSION) . '"';

        // Add encoding if not present
        if (!isset($attributes['encoding'])) {
            $headerParts[] = 'encoding="' . self::XML_ENCODING . '"';
        } else {
            $headerParts[] = 'encoding="' . $attributes['encoding'] . '"';
        }

        // Add standalone if it was present in the original
        if (isset($attributes['standalone'])) {
            $headerParts[] = 'standalone="' . $attributes['standalone'] . '"';
        }

        $xmlHeader = '<?xml ' . implode(' ', $headerParts) . '?>';

        return $xmlHeader . substr($string, $xmlTagEnd);
    }

    /**
     * Remove BOM from XML string if present
     *
     * @param string $xmlStr XML string to process
     * @return string Processed string without BOM
     */
    protected function removeBom(string $xmlStr): string
    {
        return str_starts_with($xmlStr, "\xEF\xBB\xBF") ? substr($xmlStr, 3) : $xmlStr;
    }

    /**
     *
     * @param string $filename
     * @param bool $format
     * @param bool $noHeader
     * @throws XmlUtilException
     */
    public function save(string $filename, bool $format = false, bool $noHeader = false): void
    {
        try {
            file_put_contents($filename, $this->toString($format, $noHeader));
        } catch (\Exception $ex) {
            throw new XmlUtilException("Cannot save XML Document in $filename.", 256, $ex);
        }
    }

    /**
     * @param string $xsdFilename
     * @param bool $throwError
     * @return ?array
     * @throws XmlUtilException
     */
    public function validate(string $xsdFilename, bool $throwError = true): ?array
    {
        return $this->executeLibXmlCommand(
            "XML Document is not valid according to $xsdFilename.",
            function() use ($xsdFilename) {
                $this->document->schemaValidate($xsdFilename);
            },
            $throwError
        );
    }
}
