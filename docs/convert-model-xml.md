---
sidebar_position: 4
---

# Convert a model to XML

You can convert any model to XML by using the class EntityParser. 

Example:
```php
<?php

use ByJG\XmlUtil\EntityParser;

class MyModel
{
    public $name;
    public $age;
    private $year;
    
    public function getYear()
    {
        return $this->year;
    }
    
    public function setYear($year)
    {
        $this->year = $year;
    }
}

$model = new MyModel();
$model->name = 'John Doe';
$model->age = 30;
$model->setYear(1990);

// This will convert the model to XML
$entityParser = new EntityParser();
$xml = $entityParser->parse($model);

echo $xml->toString(format: true);
```

The output will be:
```xml
<?xml version="1.0" encoding="utf-8"?>
<mymodel>
  <name>John Doe</name>
  <age>30</age>
  <year>1990</year>
</mymodel>
```

## Adding an Object using the API

You can add an object to the XML using the API.

Example:
```php
<?php
use ByJG\XmlUtil\XmlDocument;

$xml = new XmlDocument('<root />');

$myNode = $xml->appendChild('mynode');

$class = new MyModel();
$class->name = 'John Doe';
$class->age = 30;
$class->setYear(1990);

// This will add the object to the XML node `mynode`
$myNode->appendObject($class);
```
