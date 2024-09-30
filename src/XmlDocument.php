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
     * @param string $string - XML string document
     * @return string - Return the string converted
     * @throws XmlUtilException
     */
    protected function fixXmlHeader(string $string): string
    {
        $string = $this->removeBom($string);

        if (str_contains($string, "<?xml")) {
            $xmlTagEnd = strpos($string, "?>");
            if ($xmlTagEnd !== false) {
                $xmlTagEnd += 2;
                $xmlHeader = substr($string, 0, $xmlTagEnd);

                if ($xmlHeader == "<?xml?>") {
                    $xmlHeader = "<?xml ?>";
                }
            } else {
                throw new XmlUtilException("XML header bad formatted.", 251);
            }

            // Complete header elements
            $count = 0;
            $xmlHeader = preg_replace(
                "/version=([\"'][\w\-.]+[\"'])/",
                "version=\"".self::XML_VERSION."\"",
                $xmlHeader,
                1,
                $count
            );
            if ($count == 0) {
                $xmlHeader = substr($xmlHeader, 0, 6)."version=\"".self::XML_VERSION."\" ".substr($xmlHeader, 6);
            }
            $count = 0;
            $xmlHeader = preg_replace(
                "/encoding=([\"'][\w\-.]+[\"'])/",
                "encoding=\"".self::XML_ENCODING."\"",
                $xmlHeader,
                1,
                $count
            );
            if ($count == 0) {
                $xmlHeader = substr($xmlHeader, 0, 6)."encoding=\"".self::XML_ENCODING."\" ".substr($xmlHeader, 6);
            }

            // Fix header position (first version, after encoding)
            $xmlHeader = preg_replace(
                "/<\?([\w\W]*)\s+(encoding=([\"'][\w\-.]+[\"']))\s+(version=([\"'][\w\-.]+[\"']))\s*\?>/",
                "<?\\1 \\4 \\2?>",
                $xmlHeader,
                1,
                $count
            );

            return $xmlHeader.substr($string, $xmlTagEnd);
        } else {
            $xmlHeader = '<?xml version="'.self::XML_VERSION.'" encoding="'.self::XML_ENCODING.'"?>';
            return $xmlHeader.$string;
        }
    }

    protected function removeBom(string $xmlStr): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $xmlStr);
    }

    /**
     *
     * @param string $filename
     * @throws XmlUtilException
     */
    public function save(string $filename, bool $format = false): void
    {
        try {
            file_put_contents($filename, $this->toString($format));
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
