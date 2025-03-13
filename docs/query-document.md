---
sidebar_position: 3
---

# Querying a Document

## Select a single node based on XPath

```php
$node = $xml->selectSingleNode('//subnode');
```

## Select all nodes based on XPath

```php
$nodeList = $xml->selectNodes('//subnode');
```
