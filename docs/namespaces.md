---
sidebar_position: 2
---

# Working with XML Namespaces

Add a namespace to the document

```php title="Adding a namespace"
$xml->addNamespace('my', 'http://www.example.com/mytest/');
```

will produce

```xml
<?xml version="1.0" encoding="utf-8"?>
<root xmlns:my="http://www.example.com/mytest/">
    ...
</root>
```

Add a node with a namespace prefix

```php title="Adding node with namespace prefix"
$xml->appendChild('my:othernodens', 'teste');
```

Add a node with a namespace

```php title="Adding node with namespace URI"
$xml->appendChild('nodens', 'teste', 'http://www.example.com/mytest/');
```
