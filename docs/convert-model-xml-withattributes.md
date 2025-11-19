---
sidebar_position: 5
---

# Convert a model to XML with Attributes

You can add PHP attributes to your model to help the EntityParser to convert the model to XML. This provides precise control over the XML serialization process.

Example:
```php title="Using PHP attributes to control XML output"
<?php

use ByJG\XmlUtil\EntityParser;
use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;

#[XmlEntity(
    rootElementName: 'Person',
)]
class MyModel
{
    #[XmlProperty(
        elementName: 'Name',
    )]
    public $name;

    #[XmlProperty(
        elementName: 'Age',
    )]
    public $age;

    #[XmlProperty(
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

// This will convert the model to XML following the attributes rules
$entityParser = new EntityParser();
$xml = $entityParser->parse($model);

echo $xml->toString(format: true);
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

## Attributes as XML Attributes

You can also map properties to XML attributes instead of elements:

```php title="Mapping properties to XML attributes"
#[XmlEntity(rootElementName: 'Person', preserveCaseName: true)]
class PersonWithAttributes
{
    #[XmlProperty(elementName: 'Name', preserveCaseName: true)]
    public $name;

    #[XmlProperty(isAttribute: true)]
    public $id;

    #[XmlProperty(isAttributeOf: 'Name')]
    public $type;
}

$person = new PersonWithAttributes();
$person->name = 'John Doe';
$person->id = '12345';
$person->type = 'full';

$xml = (new EntityParser())->parse($person);
```

Output:
```xml
<?xml version="1.0" encoding="utf-8"?>
<Person id="12345">
  <Name type="full">John Doe</Name>
</Person>
```

## The XmlEntity Attribute

Properties:

| Property           | Description                                                                                                         | Default        |
|--------------------|---------------------------------------------------------------------------------------------------------------------|----------------|
| `rootElementName`  | The name of the root element.                                                                                       | The class name |
| `preserveCaseName` | Preserve the case of the element name.                                                                              | false          |
| `namespaces`       | Array with the namespace of the element. Need to be an associative array with prefix as key and namespace as value. | []             |
| `addNamespaceRoot` | Add the namespace to the root element if the object is a child.                                                     | true           |
| `usePrefix`        | Force use the prefix in the element name.                                                                           | empty          |
| `explicityMap`     | If true, only the properties with XmlProperty will be mapped.                                                       | false          |

## The XmlProperty Attribute

Properties:

| Property           | Description                                                                                              | Default           |
|--------------------|----------------------------------------------------------------------------------------------------------|-------------------|
| `elementName`      | The name of the element different from the property name.                                                | The property name |
| `preserveCaseName` | Preserve the case of the element name, if false, all elements will be lowercase.                         | false             |
| `namespaces`       | The namespace of the element. Need to be an associative array with prefix as key and namespace as value. | empty             |
| `isAttribute`      | If the element is an attribute of the parent node instead of a node.                                     | false             |
| `isAttributeOf`    | The name of the sibling node that will receive the attribute.                                            | empty             |
| `isChildOf`        | The name of the sibling node that will receive the child node.                                           | empty             |
| `ignore`           | If true, the property will not be parsed.                                                                | false             | 
| `ignoreEmpty`      | If true will ignore empty strings                                                                        | false             |
