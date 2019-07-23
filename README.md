# magic-property-extractor    

This package adds an additional extractor for the [symfony/property-info](https://symfony.com/doc/current/components/property_info.html) package which is able to 
interpret `@property`, `@property-read` and `@property-write` tags documented in the DocComment of a class.

### Installation

```
composer require hartmann/magic-property-extractor
```

### Usage

```php
use new \Hartmann\PropertyInfo\Extractor\PhpDocMagicExtractor

$magicExtractor = new PhpDocMagicExtractor();
$properties = $magicExtractor->getProperties(\Foo::class);
```

or [create a new PropertyInfoExtractor instance and provide it with a set of information extractors](https://symfony.com/doc/current/components/property_info.html#usage)

### [Extractable Information](https://symfony.com/doc/current/components/property_info.html#extractable-information)

This Extractor implements the following interfaces:
- `PropertyDescriptionExtractorInterface`
- `PropertyTypeExtractorInterface`
- `PropertyAccessExtractorInterface`
- `PropertyListExtractorInterface`

### Planned features

- [ ] Support magic accessors and mutators