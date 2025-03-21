---
sidebar_position: 6
---

# Clean Document

XmlUtil provides a dedicated `CleanDocument` class for selectively removing specific tags or content from XML or HTML documents. This is useful for cleaning up documents before processing or display.

## Basic Usage

```php
<?php

$document = new \ByJG\XmlUtil\CleanDocument($documentXmlOrHtml);

$document
    ->removeContentByTag('a', 'name')
    ->removeContentByProperty('src')
    ->stripTagsExcept(['img'])
    ->get();
```

## Available Methods

### stripAllTags()

Removes all HTML/XML tags from the document.

```php
$document = new \ByJG\XmlUtil\CleanDocument($html);
$plainText = $document->stripAllTags();
```

### stripTagsExcept(array $allowedTags)

Strips all HTML/XML tags except those specified in the array.

```php
$document = new \ByJG\XmlUtil\CleanDocument($html);
$cleanHtml = $document->stripTagsExcept(['p', 'div', 'span'])->get();
```

### removeContentByProperty(string $property)

Removes content from any tag that contains the specified property.

```php
$document = new \ByJG\XmlUtil\CleanDocument($html);
// Removes all tags containing the "style" property and their content
$cleanHtml = $document->removeContentByProperty('style')->get();
```

### removeContentByTag(string $tag, string $property = '')

Removes content from the specified tag, optionally filtering by a property.

```php
$document = new \ByJG\XmlUtil\CleanDocument($html);
// Removes all <script> tags and their content
$cleanHtml = $document->removeContentByTag('script')->get();

// Removes all <a> tags that have an "onclick" property
$cleanHtml = $document->removeContentByTag('a', 'onclick')->get();
```

### removeContentByTagWithoutProperty(string $tag, string $property)

Removes content from the specified tag that does NOT have the specified property.

```php
$document = new \ByJG\XmlUtil\CleanDocument($html);
// Removes all <a> tags that don't have an "href" property
$cleanHtml = $document->removeContentByTagWithoutProperty('a', 'href')->get();
```

### get()

Returns the cleaned document as a string.

```php
$document = new \ByJG\XmlUtil\CleanDocument($html);
// Apply cleaning operations
$document->removeContentByTag('script');
// Get the final result
$cleanHtml = $document->get();
```
