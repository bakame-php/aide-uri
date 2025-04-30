<?php

/**
 * Aide.Uri (https://https://github.com/bakame-php/aide-uri)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Uri\Rfc3986;

use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Uri\InvalidUriException;
use Uri\UriComparisonMode;

#[CoversClass(Uri::class)]
#[CoversClass(InvalidUriException::class)]
#[CoversClass(UriComparisonMode::class)]
final class UriTest extends TestCase
{
    #[Test]
    public function it_can_parse_an_uri(): void
    {
        $uri = Uri::parse('http://example.com');

        self::assertInstanceOf(Uri::class, $uri);
        self::assertSame('http://example.com', $uri->toString());
        self::assertSame('http://example.com', $uri->toNormalizedString());
    }

    #[Test]
    public function it_will_throw_an_error_if_the_instance_is_not_correctly_initialized(): void
    {
        $reflection = new ReflectionClass(Uri::class);
        $uri = $reflection->newInstanceWithoutConstructor();

        $this->expectException(Error::class);
        $uri->toString();
    }

    #[Test]
    public function it_will_throw_if_the_query_string_is_not_correctly_encoded(): void
    {
        $uri = new Uri('http://example.com');

        $this->expectException(InvalidUriException::class);
        $uri->withQuery('a[]=1');
    }

    #[Test]
    public function it_will_throw_if_the_path_string_is_not_correctly_encoded(): void
    {
        $uri = new Uri('http://example.com');

        $this->expectException(InvalidUriException::class);
        $uri->withPath('?#');
    }

    #[Test]
    public function it_will_throw_if_the_user_info_string_is_not_correctly_encoded(): void
    {
        $uri = new Uri('http://example.com');

        $this->expectException(InvalidUriException::class);
        $uri->withUserInfo('foo?:bar');
    }

    #[Test]
    public function it_will_throw_if_the_uri_can_not_be_parsed(): void
    {
        $this->expectException(InvalidUriException::class);

        new Uri(':/');
    }

    #[Test]
    public function it_will_return_null_if_the_uri_can_not_be_parsed(): void
    {
        self::assertNull(Uri::parse(':/'));
    }

    #[Test]
    public function it_will_throw_if_the_host_is_invalid(): void
    {
        $uri = new Uri('http://example.com');

        $this->expectException(InvalidUriException::class);
        $uri->withHost(':/');
    }

    #[Test]
    public function it_will_throw_if_the_port_is_invalid(): void
    {
        $uri = new Uri('http://example.com');

        $this->expectException(InvalidUriException::class);
        $uri->withPort(65536);
    }

    #[Test]
    public function it_will_throw_if_the_fragment_is_invalid(): void
    {
        $uri = new Uri('http://example.com');

        $this->expectException(InvalidUriException::class);
        $uri->withFragment('toto le héros');
    }

    #[Test]
    public function it_will_normalize_the_uri_according_to_rfc3986(): void
    {
        $uri = new Uri("https://[2001:0db8:0001:0000:0000:0ab9:C0A8:0102]/?foo=bar%26baz%3Dqux");

        self::assertSame('[2001:0db8:0001:0000:0000:0ab9:C0A8:0102]', $uri->getHost());
        self::assertSame('[2001:0db8:0001:0000:0000:0ab9:c0a8:0102]', $uri->getNormalizedHost());

        self::assertSame('foo=bar%26baz%3Dqux', $uri->getQuery());
        self::assertSame('foo=bar%26baz%3Dqux', $uri->getNormalizedQuery());

        self::assertSame('/', $uri->getPath());
        self::assertSame('', $uri->getNormalizedPath());
    }

    #[Test]
    public function it_exposes_raw_and_normalizes_uri_and_components(): void
    {
        $uri = new Uri("https://%61pple:p%61ss@b%C3%A9b%C3%A9.be:433/foob%61r?%61bc=%61bc#%61bc");

        self::assertSame('https', $uri->getScheme());
        self::assertSame('https', $uri->getNormalizedScheme());

        self::assertSame('%61pple:p%61ss', $uri->getUserInfo());
        self::assertSame('apple:pass', $uri->getNormalizedUserInfo());

        self::assertSame('%61pple', $uri->getUsername());
        self::assertSame('apple', $uri->getNormalizedUsername());

        self::assertSame('p%61ss', $uri->getPassword());
        self::assertSame('pass', $uri->getNormalizedPassword());

        self::assertSame('b%C3%A9b%C3%A9.be', $uri->getHost());
        self::assertSame('b%C3%A9b%C3%A9.be', $uri->getNormalizedHost());

        self::assertSame(433, $uri->getPort());

        self::assertSame('/foob%61r', $uri->getPath());
        self::assertSame('/foobar', $uri->getNormalizedPath());

        self::assertSame('%61bc=%61bc', $uri->getQuery());
        self::assertSame('abc=abc', $uri->getNormalizedQuery());

        self::assertSame('%61bc', $uri->getFragment());
        self::assertSame('abc', $uri->getNormalizedFragment());

        self::assertSame("https://%61pple:p%61ss@b%C3%A9b%C3%A9.be:433/foob%61r?%61bc=%61bc#%61bc", $uri->toString());
        self::assertSame("https://apple:pass@b%C3%A9b%C3%A9.be:433/foobar?abc=abc#abc", $uri->toNormalizedString());
    }

    #[Test]
    public function it_will_normalize_uri(): void
    {
        $uri = new Uri("HTTPS://EXAMPLE.COM/foo/../bar/");

        self::assertSame('HTTPS', $uri->getScheme());
        self::assertSame('https', $uri->getNormalizedScheme());

        self::assertSame('EXAMPLE.COM', $uri->getHost());
        self::assertSame('example.com', $uri->getNormalizedHost());

        self::assertSame('/foo/../bar/', $uri->getPath());
        self::assertSame('/bar/', $uri->getNormalizedPath());

        self::assertSame("HTTPS://EXAMPLE.COM/foo/../bar/", $uri->toString());
        self::assertSame("https://example.com/bar/", $uri->toNormalizedString());
    }

    #[Test]
    public function it_can_be_unserialized(): void
    {
        $uri = new Uri("HTTPS://EXAMPLE.COM/foo/../bar/");
        $uriB = unserialize(serialize($uri));

        self::assertSame($uri->toString(), $uriB->toString());
        self::assertTrue($uriB->equals($uri));
    }

    #[Test]
    public function it_will_return_null_on_invalid_uri_parsing(): void
    {
        self::assertNull(Uri::parse("/foo", ".com"));
    }

    #[Test]
    public function it_can_be_check_for_equivalent(): void
    {
        $uri1 = new Uri('http://example.com#foobar');
        $uri2 = new Uri('http://example.com');

        self::assertTrue($uri1->equals($uri2));
        self::assertFalse($uri1->equals($uri2, UriComparisonMode::IncludeFragment));
    }

    #[Test]
    public function it_can_resolve_uri(): void
    {
        self::assertSame(
            "https://example.com/foo",
            (new Uri("https://example.com"))->resolve("/foo")->toNormalizedString()
        );
    }

    #[Test]
    public function it_can_be_modified_using_its_components(): void
    {
        $uri = new Uri("https://%61pple:p%61ss@b%C3%A9b%C3%A9.be:433/foob%61r?%61bc=%61bc#%61bc");
        $uriBis = $uri
            ->withScheme('https')
            ->withUserInfo('apple:pass')
            ->withHost('b%C3%A9b%C3%A9.be')
            ->withPort(433)
            ->withPath('/foobar')
            ->withQuery('abc=abc')
            ->withFragment('abc');

        self::assertTrue($uriBis->equals($uri));
        self::assertNotSame($uri->toString(), $uriBis->toString());
        self::assertSame([
            'scheme' => 'https',
            'username' => 'apple',
            'password' => 'pass',
            'host' => 'b%C3%A9b%C3%A9.be',
            'port' => 433,
            'path' => '/foobar',
            'query' => 'abc=abc',
            'fragment' => 'abc',
        ], $uriBis->__debugInfo());
    }

    #[Test]
    public function it_can_normalize_uri(): void
    {
        $uri = new Uri("https://%61pple:p%61ss@ex%61mple.com:433/foob%61r?%61bc=%61bc#%61bc");

        self::assertSame("https://%61pple:p%61ss@ex%61mple.com:433/foob%61r?%61bc=%61bc#%61bc", $uri->toString());
        self::assertSame("https://apple:pass@example.com:433/foobar?abc=abc#abc", $uri->toNormalizedString());
    }

    #[Test]
    public function it_will_use_the_punycode_form_on_host_normalization(): void
    {
        $uri = new Uri("https://www.b%C3%A9b%C3%A9.be#foobar");

        self::assertSame('www.b%C3%A9b%C3%A9.be', $uri->getHost());
        self::assertSame('www.b%C3%A9b%C3%A9.be', $uri->getNormalizedHost());
    }

    #[Test]
    public function it_fails_to_parse_uri_with_invalid_characters(): void
    {
        $this->expectException(InvalidUriException::class);

        new Uri("https://www.bébé.be#foobar");
    }

    #[Test]
    public function it_fails_to_update_the_uri_if_the_component_is_invalid(): void
    {
        $this->expectException(InvalidUriException::class);

        (new Uri(""))->withPath(':/')->toNormalizedString();
    }

    #[Test]
    public function it_can_update_the_uri_scheme(): void
    {
        $uri = new Uri("https://www.b%C3%A9b%C3%A9.be#foobar");
        $newUri = $uri->withScheme('FoO');

        self::assertSame('FoO', $newUri->getScheme());
        self::assertSame('foo', $newUri->getNormalizedScheme());
    }

    #[Test]
    public function it_can_update_the_user_info_component(): void
    {
        $uri1 = new Uri('http://example.com#foobar');
        $uriWithUser = $uri1->withUserInfo('apple');

        self::assertSame('apple', $uriWithUser->getNormalizedUserInfo());
        self::assertSame('apple', $uriWithUser->getNormalizedUsername());
        self::assertNull($uriWithUser->getNormalizedPassword());
        self::assertNull($uriWithUser->getPassword());

        $uriWithUserAndPassword = $uriWithUser->withUserInfo('banana:cream');
        self::assertSame('banana:cream', $uriWithUserAndPassword->getNormalizedUserInfo());
        self::assertSame('banana', $uriWithUserAndPassword->getNormalizedUsername());
        self::assertSame('cream', $uriWithUserAndPassword->getPassword());
        self::assertSame('cream', $uriWithUserAndPassword->getNormalizedPassword());

        $uriStripped = $uriWithUserAndPassword->withUserInfo(null);
        self::assertTrue($uriStripped->equals($uri1));
        self::assertTrue($uriStripped->withUserInfo(null)->equals($uriStripped));
    }

    #[Test]
    public function it_can_noemalize_ip_v6_host(): void
    {
        $uri = new Uri("https://[2001:0db8:0001:0000:0000:0ab9:C0A8:0102]/?foo=bar%26baz%3Dqux");

        self::assertSame(
            "https://[2001:0db8:0001:0000:0000:0ab9:c0a8:0102]?foo=bar%26baz%3Dqux",
            $uri->toNormalizedString()
        );
    }

    #[Test]
    public function it_can_not_update_invalid_path_according_to_rfc3986(): void
    {
        $this->expectException(InvalidUriException::class);

        (new Uri("foo/bar"))->withPath('//foo');
    }

    #[Test]
    public function it_can_not_update_invalid_host_according_to_rfc3986(): void
    {
        $this->expectException(InvalidUriException::class);

        (new Uri("foo/bar"))->withHost('ex%61mple.com');
    }

    #[Test]
    public function it_parses_an_uri_but_does_not_enforces_http_specific_validation(): void
    {
        self::assertSame(
            'https:example.com',
            (new Uri("example.com"))->withScheme('https')->toNormalizedString()
        );
    }

    #[Test]
    public function it_fails_parsing_an_malformed_uri_with_invalid_query_encoding(): void
    {
        $this->expectException(InvalidUriException::class);

        new Uri("https://[2001:0db8:0001:0000:0000:0ab9:C0A8:0102]/?foo[]=1&foo[]=2");
    }

    #[Test]
    public function it_handles_differently_raw_and_normalized_components(): void
    {
        $uri = new Uri("https://[2001:0db8:0001:0000:0000:0ab9:C0A8:0102]/foo/bar%3Fbaz?foo=bar%26baz%3Dqux");

        self::assertSame('[2001:0db8:0001:0000:0000:0ab9:C0A8:0102]', $uri->getHost());
        self::assertSame('[2001:0db8:0001:0000:0000:0ab9:c0a8:0102]', $uri->getNormalizedHost());
        self::assertSame('/foo/bar%3Fbaz', $uri->getPath());
        self::assertSame('/foo/bar%3Fbaz', $uri->getNormalizedPath());
        self::assertSame('foo=bar%26baz%3Dqux', $uri->getQuery());
        self::assertSame('foo=bar%26baz%3Dqux', $uri->getNormalizedQuery());
    }

    #[Test]
    public function it_will_convert_to_unicode_the_host_in_the_uri_while_preserving_uri_construction(): void
    {
        $uri = new Uri("HTTPS://ex%61mple.com:443/foo/../bar/./baz?#fragment");

        self::assertSame("HTTPS://ex%61mple.com:443/foo/../bar/./baz?#fragment", $uri->toString());
        self::assertSame("https://example.com:443/bar/baz?#fragment", $uri->toNormalizedString());
    }
}
