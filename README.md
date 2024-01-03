# XmlUtil

[![Build Status](https://github.com/byjg/php-xmlutil/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-xmlutil/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-xmlutil/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-xmlutil.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/php-xmlutil.svg)](https://github.com/byjg/php-xmlutil/releases/)

A utility class to make easy work with XML in PHP

## Create a new XML Document and add nodes

```php
use ByJG\Util\XmlUtil;

$xml = XmlUtil::createXmlDocumentFromStr('<root />');

$myNode = XmlUtil::createChild($xml->documentElement, 'mynode');
XmlUtil::createChild($myNode, 'subnode', 'text');
XmlUtil::createChild($myNode, 'subnode', 'more text');
$otherNode = XmlUtil::createChild($myNode, 'othersubnode', 'other text');
XmlUtil::addAttribute($otherNode, 'attr', 'value');
```

will produce the follow xml

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

## Convert to array

```php
$array = XmlUtil::xml2Array($xml);
```

## Select a single node based on XPath

```php
$node = XmlUtil::selectSingleNode($xml, '//subnode');
```

## Select all nodes based on XPath

```php
$nodeList = XmlUtil::selectNodes($myNode, '//subnode');
```

## Working with xml namespaces

Add a namespace to the document

```php
XmlUtil::addNamespaceToDocument($xml, 'my', 'http://www.example.com/mytest/');
```

will produce

```xml
<?xml version="1.0" encoding="utf-8"?>
<root xmlns:my="http://www.example.com/mytest/"> 
    ...
</root>
``````

Add a node with a namespace prefix

```php
XmlUtil::createChild($xml->documentElement, 'my:othernodens', 'teste');
```

Add a node with a namespace

```php
XmlUtil::createChild($xml->documentElement, 'nodens', 'teste', 'http://www.example.com/mytest/');
```

## Bonus - CleanDocument

XmlUtil have a class for selectively remove specific marks (tags)
from the document or remove all marks.

Example:

```php
<?php

$document = new \ByJG\Util\CleanDocument($documentXmlOrHtml);

$document
    ->removeContentByTag('a', 'name')
    ->removeContentByProperty('src')
    ->stripTagsExcept(['img'])
    ->get();

```

## Install

```bash
composer require "byjg/xmlutil"
```

## Running the Tests

```bash
vendor/bin/phpunit
```

## Dependencies

```mermaid
flowchart TD
    byjg/xmlutil --> ext-xml
```


----
[Open source ByJG](http://opensource.byjg.com)