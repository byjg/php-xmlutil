---
sidebar_position: 7
---

# File Handling

XmlUtil provides a simple File class for handling XML files.

## Creating a File instance

```php title="Creating File instances"
<?php
use ByJG\XmlUtil\File;

// Create a File instance for an existing file
$file = new File('/path/to/file.xml');

// Create a File instance for a new file (that doesn't exist yet)
$file = new File('/path/to/newfile.xml', true);
```

## Reading a file

```php title="Reading XML from file"
<?php
use ByJG\XmlUtil\File;
use ByJG\XmlUtil\XmlDocument;

$file = new File('/path/to/file.xml');

// Get the file contents (returns string|bool)
$contents = $file->getContents();
if ($contents === false) {
    // Handle error
}

// Create an XmlDocument from the file
$xml = new XmlDocument($file);
```

## Saving a file

```php title="Saving XML to file"
<?php
use ByJG\XmlUtil\File;
use ByJG\XmlUtil\XmlDocument;

// Save XML to a file
$xml = new XmlDocument('<root><node>value</node></root>');
$xml->save('/path/to/file.xml');

// Or use the File class directly
$file = new File('/path/to/file.xml', true);
$file->save($xml->toString());
```

## Getting the filename

```php title="Getting the filename"
<?php
use ByJG\XmlUtil\File;

$file = new File('/path/to/file.xml');
$filename = $file->getFilename();
``` 