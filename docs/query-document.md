---
sidebar_position: 3
---

# Querying a Document

XmlUtil provides powerful XPath functionality to query XML documents.

## Select a single node based on XPath

```php title="Selecting a single node with XPath"
$node = $xml->selectSingleNode('//subnode');
```

## Select all nodes based on XPath

```php title="Selecting multiple nodes with XPath"
$nodeList = $xml->selectNodes('//subnode');

// Iterate through the results
foreach ($nodeList as $node) {
    // Create an XmlNode instance from each DOMNode
    $xmlNode = new \ByJG\XmlUtil\XmlNode($node);

    // Now you can use all XmlNode methods
    echo $xmlNode->innerText();
}
```

## Working with namespaces in XPath queries

If your XML document uses namespaces, you can include them in your XPath queries:

```php title="XPath queries with namespaces"
// Create a namespace array
$namespaces = [
    'ns1' => 'http://www.example.com/namespace1',
    'ns2' => 'http://www.example.com/namespace2'
];

// Query using namespaces
$node = $xml->selectSingleNode('//ns1:element', $namespaces);
$nodeList = $xml->selectNodes('//ns2:element', $namespaces);
```

## Working with selected nodes

After selecting a node, you can manipulate it using all XmlNode methods:

```php title="Manipulating selected nodes"
// Get a node
$node = $xml->selectSingleNode('//element');

// Get text content
$text = $node->innerText();

// Add a child node to the selected node
$node->appendChild('child', 'text content');

// Add an attribute
$node->addAttribute('name', 'value');

// Convert the node to array
$array = $node->toArray();

// Remove the node
$node->removeNode();

// Rename the node
$node->renameNode('newName');
```

## Complex queries

You can create more complex XPath queries:

```php title="Complex XPath examples"
// Find all elements with a specific attribute
$nodes = $xml->selectNodes('//element[@attr="value"]');

// Find elements with specific text
$nodes = $xml->selectNodes('//element[text()="specific text"]');

// Find the first child element
$node = $xml->selectSingleNode('./child::*[1]');

// Combine conditions
$nodes = $xml->selectNodes('//element[@attr1="value1" and @attr2="value2"]');
```
