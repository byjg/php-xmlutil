---
sidebar_position: 2
---

# Working with xml namespaces

Add a namespace to the document

```php
$xml->addNamespace('my', 'http://www.example.com/mytest/');
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
$xml->appendChild('my:othernodens', 'teste');
```

Add a node with a namespace

```php
$xml->appendChild('nodens', 'teste', 'http://www.example.com/mytest/');
```
