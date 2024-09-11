# Convert a model to XML with Attributes

You can add PHP attributes to your model to help the EntityParser to convert the model to XML. 

Example:
```php
<?php

#[XmlEntity(
    rootElementName: 'Person',
)]
class MyModel
{
    #[XmlElement(
        elementName: 'Name',
    )]
    public $name;
    
    #[XmlElement(
        elementName: 'Age',
    )]
    public $age;
    
    #[XmlElement(
        elementName: 'Year',
    )]
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

$entityParser = new \ByJG\XmlUtil\EntityParser();
$xml = $entityParser->parse($model);

echo $xml->toString();
```

The output will be:
```xml
<?xml version="1.0" encoding="utf-8"?>
<Person>
  <Name>John Doe</Name>
  <Age>30</Age>
  <Year>1990</Year>
</Person>
```

## The XmlEntity Attribute

Properties:

- `rootElementName`: The name of the root element. Default is the class name.
- `preserveCase`: Preserve the case of the element name. Default is false.
- `namespace`: The namespace of the element. Need to an associative array with prefix as key and namespace as value. Default is empty.
- `xmlDeclaration`: Add the XML declaration. Default is true.

## The XmlElement Attribute

Properties:

- `elementName`: The name of the element. Default is the property name.
- `preserveCase`: Preserve the case of the element name. Default is false.
- `namespace`: The namespace of the element. Need to an associative array with prefix as key and namespace as value. Default is empty.
- `isAttribute`: If the element is an attribute instead of a node. Default is false.
