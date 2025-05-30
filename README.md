# URI parsing polyfill for PHP8.1+

````php
$uri = new Uri\Rfc3986\Uri("HTTPS://ex%61mpLE.com:443/foo/../bar/./baz?#fragment");
$uri->toRawString(); // returns "HTTPS://ex%61mpLE.com:443/foo/../bar/./baz?#fragment"
$uri->toString();    // returns "https://example.com:443/bar/baz?#fragment"

$url = new Uri\WhatWg\Url("HTTPS://🐘.com:443/foo/../bar/./baz?#fragment");
echo $url->toAsciiString();   // returns "https://xn--go8h.com/bar/baz?#fragment"
echo $url->toUnicodeString(); // returns "https://🐘.com/bar/baz?#fragment"
````

This package provides a polyfill for the new native PHP URI
parsing features to be included in **PHP8.5**. The polyfill
works for PHP versions greater or equal to **PHP8.1**

## System Requirements

To use the package, you require:

- **PHP >= 8.1** but the latest stable version of PHP is recommended
- [league/uri-interfaces](https://github.com/thephpleague/uri-interfaces)
- [rowbot/url](https://github.com/TRowbotham/URL-Parser)

> [!TIP]
> If you are using **PHP 8.1**, you **SHOULD** install `symfony/polyfill-php82` to use its `SensitiveParameter` polyfill 

## Install

Install the package using Composer.

```bash
composer require bakame/aide-uri:dev-main
```

## Documentation

The RFC introduces:

- the `Uri\Rfc3986\Uri` class, an [RFC 3986](https://www.rfc-editor.org/rfc/rfc3986) compliant URI parser
- the `Uri\WhatWg\Url` class, an [WHATWG](https://url.spec.whatwg.org/) compliant URL parser

Full documentation can be found on the [Add RFC 3986 and WHATWG compliant URI parsing support RFC](https://wiki.php.net/rfc/url_parsing_api).

## Testing

The package has:

- a [PHPUnit](https://phpunit.de) test suite
- a code analysis compliance test suite using [PHPStan](https://github.com/phpstan/phpstan).
- a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/).
- a benchmark using [PHP Bench](https://github.com/phpbench/phpbench).

To run the tests, run the following command from the project root folder.

``` bash
composer test
```

You can run the benchmark separately using the following command:

``` bash
composer benchmark
```

## Contributing

Contributions are welcome and will be fully credited. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CONDUCT](.github/CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email nyamsprod@gmail.com instead of using the issue tracker.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Ignace Nyamagana Butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/bakame-php/aide-uri/graphs/contributors)

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
