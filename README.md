[![GitHub Workflow Status][ico-tests]][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

------

Mint is a unified identifier generation library for Laravel that provides a fluent API for generating, parsing, and validating various types of unique identifiers. Whether you need time-ordered UUIDs for database performance, compact NanoIDs for URLs, or type-prefixed TypeIDs for self-documenting APIs, Mint has you covered.

## Requirements

> **Requires [PHP 8.4+](https://php.net/releases/) and Laravel 12+**

## Installation

```bash
composer require cline/mint
```

## Documentation

- **[Getting Started](https://docs.cline.sh/mint/getting-started)** - Installation and basic concepts
- **[UUID](https://docs.cline.sh/mint/uuid)** - Universally Unique Identifiers (RFC 4122/9562)
- **[ULID](https://docs.cline.sh/mint/ulid)** - Lexicographically Sortable Identifiers
- **[Snowflake](https://docs.cline.sh/mint/snowflake)** - Twitter-style 64-bit time-ordered identifiers
- **[NanoID](https://docs.cline.sh/mint/nanoid)** - Compact, URL-safe random identifiers
- **[Sqid](https://docs.cline.sh/mint/sqid)** - Encode/decode integers to short strings
- **[Hashid](https://docs.cline.sh/mint/hashid)** - Encode/decode integers with salt
- **[TypeID](https://docs.cline.sh/mint/typeid)** - Type-prefixed UUIDv7 identifiers
- **[KSUID](https://docs.cline.sh/mint/ksuid)** - K-Sortable Unique Identifiers
- **[Other Identifiers](https://docs.cline.sh/mint/other-identifiers)** - CUID2, ObjectID, and more

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form][link-security] rather than the issue queue.

## Credits

- [Brian Faust][link-maintainer]
- [All Contributors][link-contributors]

## License

The MIT License. Please see [License File](LICENSE.md) for more information.

[ico-tests]: https://git.cline.sh/faustbrian/mint/actions/workflows/quality-assurance.yaml/badge.svg
[ico-version]: https://img.shields.io/packagist/v/cline/mint.svg
[ico-license]: https://img.shields.io/badge/License-MIT-green.svg
[ico-downloads]: https://img.shields.io/packagist/dt/cline/mint.svg

[link-tests]: https://git.cline.sh/faustbrian/mint/actions
[link-packagist]: https://packagist.org/packages/cline/mint
[link-downloads]: https://packagist.org/packages/cline/mint
[link-security]: https://git.cline.sh/faustbrian/mint/security
[link-maintainer]: https://git.cline.sh/faustbrian
[link-contributors]: ../../contributors
