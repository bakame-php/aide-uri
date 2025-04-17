# A polyfill for ongoing PHP RFCFC 3986 and WHATWG compliant URI parsing support

This is a PHP polyfill for the ongoing [RFC](https://wiki.php.net/rfc/url_parsing_api) to add support for

- an RFC 3986 compliant URI parsing 
- an WHATWG compliant URI parsing

The polyfills use:

- [League URI Interface Package](https://github.com/thephpleague/uri-interfaces)
- [URL-Parser](https://github.com/TRowbotham/URL-Parser)

Please view the RFC document to understand the URI instance public API.

> [!WARNING]
> **The implementations are made to be correct not to be fast**
