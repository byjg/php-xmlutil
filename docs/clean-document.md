# Clean Document

XmlUtil have a class for selectively remove specific marks (tags)
from the document or remove all marks.

Example:

```php
<?php

$document = new \ByJG\XmlUtil\CleanDocument($documentXmlOrHtml);

$document
    ->removeContentByTag('a', 'name')
    ->removeContentByProperty('src')
    ->stripTagsExcept(['img'])
    ->get();
```
