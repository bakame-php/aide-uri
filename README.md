# A polyfill for PHP RFC 3986 and WHATWG compliant URI parsing support

This PHP polyfill provides:

- an [RFC 3986](https://www.rfc-editor.org/rfc/rfc3986) compliant URI parsing 
- an [WHATWG URL](https://url.spec.whatwg.org/) compliant parsing

For PHP version greater or equal to **PHP8.1**.

Documentation
-------

Full documentation can be found on the [PHP RFC: Add RFC 3986 and WHATWG compliant URI parsing support](https://wiki.php.net/rfc/url_parsing_api).

System Requirements
-------

To use the package you are required to use:

- **PHP >= 8.1** but the latest stable version of PHP is recommended
- [League URI Interface Package](https://github.com/thephpleague/uri-interfaces) and its dependencies
- [URL-Parser](https://github.com/TRowbotham/URL-Parser) and its dependencies

Testing
-------

The URI polyfill has:

- a [PHPUnit](https://phpunit.de) test suite
- a code analysis compliance test suite using [PHPStan](https://github.com/phpstan/phpstan).
- a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/).

To run the tests, run the following command from the project folder.

``` bash
$ composer test
```

Contributing
-------

Contributions are welcome and will be fully credited. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CONDUCT](.github/CODE_OF_CONDUCT.md) for details.

Security
-------

If you discover any security related issues, please email nyamsprod@gmail.com instead of using the issue tracker.

Changelog
-------

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

Credits
-------

- [Ignace Nyamagana Butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/bakame-php/aide-uri/graphs/contributors)

License
-------

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
