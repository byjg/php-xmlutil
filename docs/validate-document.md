---
sidebar_position: 8
---

# Validating XML Documents

XmlUtil provides a way to validate XML documents against XSD schemas.

## Validating an XML document

```php title="Validating against XSD schema"
<?php
use ByJG\XmlUtil\XmlDocument;

$xml = new XmlDocument('<root><node>value</node></root>');

// Validate against an XSD schema
try {
    $xml->validate('/path/to/schema.xsd');
    echo "Document is valid!";
} catch (\ByJG\XmlUtil\Exception\XmlUtilException $e) {
    echo "Document is not valid: " . $e->getMessage();
}
```

## Getting validation errors without throwing exceptions

```php title="Getting validation errors"
<?php
use ByJG\XmlUtil\XmlDocument;

$xml = new XmlDocument('<root><node>value</node></root>');

// Validate and get errors without throwing an exception
$errors = $xml->validate('/path/to/schema.xsd', false);

if (empty($errors)) {
    echo "Document is valid!";
} else {
    echo "Document is not valid:";
    foreach ($errors as $error) {
        echo "- " . $error . "\n";
    }
}
``` 