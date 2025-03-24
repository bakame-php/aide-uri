# A polyfill for ongoing PHP RFC 3986 URI parsing support

This is a PHP polyfill for RFC 3986 compliant URI parsing support based on the
ongoing [RFC](https://wiki.php.net/rfc/url_parsing_api)

The URI object uses [League URI Interface Package](https://github.com/thephpleague/uri-interfaces)

**The main difference is that the polyfill handles RFC3987 host resolution in addition to RFC3986.**

Please view the RFC document to understand the URI instance public API.
