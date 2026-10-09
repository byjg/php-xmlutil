# CHANGELOG 6.0

## Overview

Version 6.0 represents a major update to the PHP XML Util library, focusing on improved error handling, enhanced validation, better PHP 8.3+ compatibility, and comprehensive documentation. This release includes breaking changes that require attention when upgrading from 5.x.

## New Features

### Enhanced Error Handling and Validation
- Added comprehensive exception handling for null checks and invalid states throughout the library
- Improved validation of XML structures with stricter type checking
- Enhanced metadata and namespace processing with better error messages
- Sanitized node creation to prevent invalid XML node names
- Added null safety checks in `XmlNode`, `EntityParser`, and `XmlDocument` classes

### Improved XML Document Processing
- Refactored XML header parsing with better handling of malformed declarations
- Enhanced BOM (Byte Order Mark) removal using more efficient string operations
- Improved attribute parsing in XML declarations
- Better handling of encoding normalization (e.g., "utf8" → "utf-8")
- More robust handling of XML version validation (1.0 and 1.1)

### Anonymous Class Support
- Added support for processing anonymous classes in `EntityParser`
- Anonymous classes are processed by extracting properties using getter methods

### Enhanced Pattern Matching
- Improved `CleanDocument` regex patterns with null coalescing for stability
- Better handling of edge cases in content removal operations

### Comprehensive Test Suite
- Added comprehensive test suite for `XmlDocument` utility covering:
  - Array conversions
  - Basic operations
  - File handling
  - Namespace support
  - Node manipulation
  - Object conversions
  - XML validation

### Documentation Improvements
- Expanded and enhanced documentation across all features
- Added practical examples for querying, creating, and cleaning XML documents
- Introduced new documentation for:
  - File handling operations
  - XML validation against schemas
- Enhanced documentation for namespace handling and attribute-based mapping
- Added sponsor link and improved README clarity

### Developer Experience
- Added VSCode launch configuration for debugging
- Introduced dedicated GitHub Actions workflow for static analysis (Psalm)
- Added `#[Override]` attribute for better code clarity and maintenance

## Bug Fixes

- Fixed XML header formatting issues with malformed declarations
- Fixed BOM removal to use more efficient string operations instead of regex
- Fixed null reference issues in `XmlNode` operations (parent node checks)
- Fixed sanitization of node names to prevent invalid characters
- Fixed return type for `File::getContents()` to properly handle failure cases
- Improved type safety in `EntityParser` for scalar, array, and object processing

## Breaking Changes

| Component | Before (5.x) | After (6.0) | Description |
|-----------|-------------|-------------|-------------|
| **PHP Version** | `>=8.1 <8.4` | `>=8.3 <8.6` | Minimum PHP version increased to 8.3, added support for PHP 8.4 and 8.5 |
| **byjg/serializer** | `^5.0` | `^6.0` | Dependency upgraded to version 6.0 |
| **PHPUnit** | `^9.6` | `^10.5\|^11.5` | Upgraded to PHPUnit 10.5 or 11.5 for better PHP 8.3+ compatibility |
| **Psalm** | `^5.20` | `^5.9\|^6.13` | Updated to support Psalm 6.13 for improved static analysis |
| **File::getContents()** | Returns `string` | Returns `string\|false` | Method now properly indicates potential failure with `false` return value |
| **Error Handling** | Minimal validation | Strict validation | Many methods now throw `XmlUtilException` for null or invalid inputs that were previously handled loosely |
| **XmlDocument::removeBom()** | Uses `preg_replace()` | Uses `str_starts_with()` and `substr()` | Internal implementation change (should not affect usage) |
| **Node Creation** | Loose validation | Sanitized names | Invalid characters in node names are now replaced with underscores |
| **Namespace Processing** | Basic handling | Enhanced validation | Stricter validation of namespace URIs and prefixes with better error messages |

## Upgrade Path from 5.x to 6.x

### Step 1: Check PHP Version
Ensure your environment meets the new minimum requirements:

```bash
php -v  # Should be PHP 8.3 or higher
```

If you're running PHP 8.1 or 8.2, you'll need to upgrade your PHP version before upgrading to xmlutil 6.0.

### Step 2: Update Composer Dependencies

Update your `composer.json`:

```json
{
  "require": {
    "byjg/xmlutil": "^6.0"
  }
}
```

Then run:

```bash
composer update byjg/xmlutil
```

This will automatically upgrade `byjg/serializer` to version 6.0 as well.

### Step 3: Review File Operations

If you're using `File::getContents()`, update your code to handle the new return type:

**Before (5.x):**
```php
$file = new File('path/to/file.xml');
$contents = $file->getContents(); // Always returns string
```

**After (6.x):**
```php
$file = new File('path/to/file.xml');
$contents = $file->getContents(); // Returns string|false

if ($contents === false) {
    // Handle error case
    throw new Exception('Failed to read file');
}
```

### Step 4: Add Null Checks and Error Handling

Version 6.0 enforces stricter validation. Review your code for potential null values:

**Before (5.x):**
```php
$xml->appendChild('node')->parentNode()->appendChild('sibling');
// Might fail silently if parentNode() returns null
```

**After (6.x):**
```php
// Now throws XmlUtilException if parent is null
try {
    $xml->appendChild('node')->parentNode()->appendChild('sibling');
} catch (XmlUtilException $e) {
    // Handle error appropriately
}
```

### Step 5: Review Custom Node Names

If you're creating nodes with special characters, be aware they'll now be sanitized:

```php
// Version 6.0 automatically replaces invalid characters with underscores
$xml->appendChild('my-node@name'); // Creates node: my_node_name
```

### Step 6: Update Test Dependencies

If you have tests, update to PHPUnit 10.5+ or 11.5+:

```json
{
  "require-dev": {
    "phpunit/phpunit": "^10.5|^11.5"
  }
}
```

Update any deprecated PHPUnit assertions or methods according to the PHPUnit 10/11 migration guides.

### Step 7: Test Thoroughly

Run your test suite to ensure compatibility:

```bash
vendor/bin/phpunit
```

Run Psalm for static analysis:

```bash
vendor/bin/psalm
```

### Step 8: Review EntityParser Usage

If you're using `EntityParser` with custom objects, verify that:
- All objects have proper metadata via attributes
- Root element names are properly defined
- Namespace configurations are valid

The stricter validation will now throw exceptions for missing or invalid metadata.

## Additional Notes

- The library now includes comprehensive inline documentation for better IDE support
- All regex patterns have been reviewed for stability and edge cases
- GitHub Actions workflows have been updated for better CI/CD support
- The project description has been enhanced for better clarity

## Migration Timeline

We recommend the following approach for migration:

1. **Test in Development**: Upgrade in a development environment first
2. **Review Logs**: Check for any `XmlUtilException` instances in your logs
3. **Update Error Handling**: Add appropriate try-catch blocks where needed
4. **Test Edge Cases**: Pay special attention to file operations and null scenarios
5. **Deploy to Staging**: Test thoroughly before production deployment
6. **Monitor Production**: Watch for any runtime exceptions after deployment

## Support

If you encounter issues during migration:

- Check the enhanced documentation in the `docs/` directory
- Review the comprehensive test suite for usage examples
- Report issues on the GitHub repository: https://github.com/byjg/php-xmlutil/issues

## Credits

Special thanks to all contributors who helped make version 6.0 possible.
