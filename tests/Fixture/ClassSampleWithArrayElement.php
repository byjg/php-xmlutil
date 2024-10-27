<?php

namespace Tests\Fixture;

use ByJG\XmlUtil\Attributes\XmlEntity;
use ByJG\XmlUtil\Attributes\XmlProperty;

#[XmlEntity]
class ClassSampleWithArrayElement
{
    #[XmlProperty]
    public string $Name;
    #[XmlProperty]
    public int $age;
    #[XmlProperty(ignoreEmpty: true)]
    public ?string $city = null;
    #[XmlProperty(ignoreEmpty: true)]
    public ?int $weight = null;
    #[XmlProperty(ignoreEmpty: true)]
    public ?array $addresses = null;
}