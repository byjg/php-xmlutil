# Changelog - Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## Breaking Changes

- `CleanDocument` now removes more content than before, because of the bug fixes below:
  `removeContentByTag()` and `removeContentByTagWithoutProperty()` also match tags without
  attributes and content spanning several lines, and `removeContentByProperty()` matches tags
  of any name, not just one-letter tags.

- `XmlNode::toFullArray()` is now done by byjg/serializer (`XmlFormatter::parse()`, the
  reader behind `Serialize::fromXml()`), so XML read here and there gives the same array. The
  output is unchanged. The protected `_toFullArray()` was removed; a subclass that overrode
  it should override `toFullArray()`.

## Bug Fixes

- `CleanDocument::removeContentByTag()` did not match a tag without attributes (e.g. `<script>`)
  nor content spanning several lines. `removeContentByTagWithoutProperty()` had the same problem.
- `CleanDocument::removeContentByProperty()` only matched one-letter tags: `<a style>` was
  removed but `<div style>` was not.

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

### ByJG dependencies

- `byjg/serializer` is now `^7.0`.

While 7.0 is unreleased these resolve to `7.0.x-dev` from each component's
`7.0` branch, via `minimum-stability: dev` with `prefer-stable: true`.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm is installed as `psalm/phar` instead of `vimeo/psalm`.

  `vimeo/psalm` lists the PHP versions it supports and no published release includes
  8.6, so as a dev dependency it made `composer install` fail on the 8.6 build job
  before any test ran. `psalm/phar` requires only `php ^8.2` and bundles its own
  dependencies, so it installs on every PHP version in the matrix and cannot conflict
  with the project's. Psalm itself still refuses to *run* on 8.6, which is why the
  Psalm job uses 8.5. `composer psalm` runs it.

- PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, breaking the
  8.3 floor.

## Continuous Integration

- The build matrix now includes PHP 8.6.
- The Psalm job runs on PHP 8.5.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
- Removed `example.php`; the examples are in the README and `docs/`.
