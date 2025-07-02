---
sidebar_position: 1
---

# Using the API

The XmlUtil library provides a simple but powerful API for working with XML documents. Here's how to get started.

## Creating XML Documents

### From a string

```php
<?php
use ByJG\XmlUtil\XmlDocument;

// Create from an XML string
$xml = new XmlDocument('<root />');
```

### From a file

```php
<?php
use ByJG\XmlUtil\XmlDocument;
use ByJG\XmlUtil\File;

// Create from a file
$file = new File('/path/to/file.xml');
$xml = new XmlDocument($file);
```

### Create an empty document

```php
<?php
use ByJG\XmlUtil\XmlDocument;

// Create an empty document with a specific root element
$xml = XmlDocument::emptyDocument('root');

// Create with a namespace
$xml = XmlDocument::emptyDocument('root', 'http://www.example.com/ns');
```

## Building XML Structure

```php
<?php
use ByJG\XmlUtil\XmlDocument;

$xml = new XmlDocument('<root />');

// Add child nodes
$myNode = $xml->appendChild('mynode');
$myNode->appendChild('subnode', 'text');
$myNode->appendChild('subnode', 'more text');
$otherNode = $myNode->appendChild('othersubnode', 'other text');

// Add attributes
$otherNode->addAttribute('attr', 'value');

// Output formatted XML
echo $xml->toString(format: true);
```

Output:

```xml
<?xml version="1.0" encoding="utf-8"?>
<root>
  <mynode>
    <subnode>text</subnode>
    <subnode>more text</subnode>
    <othersubnode attr="value">other text</othersubnode>
  </mynode>
</root>
```

## Working with Nodes

### Adding Text

```php
$node = $xml->appendChild('node');
$node->addText('Some text');

// Add CDATA section
$node->addText('This is <CDATA> content', true);
```

### Inserting Nodes

```php
// Insert a node before the current node
$newNode = $node->insertBefore('newnode', 'new node text');
```

### Getting the Parent Node

```php
$parent = $node->parentNode();
```

### Renaming Nodes

```php
// Rename the current node
$node->renameNode('newName');

// Rename with namespace prefix
$node->renameNode('prefix:newName');
```

### Removing Nodes

```php
// Remove the current node
$node->removeNode();

// Remove all nodes with a specific tag name
$xml->removeTagName('tagToRemove');
```

### Adding objects

```php
// Add a PHP object or array to the XML structure
$object = new stdClass();
$object->property = 'value';
$node->appendObject($object);
```

### Importing nodes from another document

```php
// Import nodes from another document or file
$node->importNodes($otherXmlNode, 'nodeToImport');
```

## Converting to Other Formats

### Convert to Array

```php
$array = $xml->toArray();
```

### Convert to String

```php
// Default output
$string = $xml->toString();

// Formatted output
$string = $xml->toString(format: true);

// Without XML header
$string = $xml->toString(noHeader: true);
```

## Accessing DOM Objects

```php
// Get the underlying DOMNode
$domNode = $xml->DOMNode();

// Get the underlying DOMDocument
$domDocument = $xml->DOMDocument();
```

## Saving XML

```php
// Save to a file
$xml->save('/path/to/output.xml');

// Save with formatting
$xml->save('/path/to/output.xml', format: true);

// Save without XML header
$xml->save('/path/to/output.xml', format: true, noHeader: true);
```

## Class Diagram

```mermaid
classDiagram
    XmlNode <|-- XmlDocument

    class XmlNode{
        appendChild()
        insertBefore()
        addText()
        addAttribute()
        selectNodes()
        selectSingleNode()
        innerText()
        removeNode()
        removeTagName()
        toArray()
        addNamespace()
        importNodes()
        renameNode()
        DOMNode()
        DOMDocument()
        toString()
    }
    class XmlDocument{
        save()
        validate()
    }
```
