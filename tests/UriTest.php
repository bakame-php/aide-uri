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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
    #[Test]
    public function it_can_parse_an_uri(): void
    {
        $uri = Uri::parse('http://example.com');

        self::assertInstanceOf(Uri::class, $uri);
        self::assertSame('http://example.com', $uri->toRawString());
        self::assertSame('http://example.com/', $uri->toString());
    }

    #[Test]
    public function it_will_throw_an_error_if_the_instance_is_not_correctly_initialized(): void
    {
        $reflection = new ReflectionClass(Uri::class);
        $uri = $reflection->newInstanceWithoutConstructor();

        $this->expectException(UninitializedUriError::class);
        $uri->toRawString();
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
        self::assertSame('foo=bar%26baz%3Dqux', $uri->getQuery());
        self::assertSame('foo=bar%26baz%3Dqux', $uri->getRawQuery());
    }

    #[Test]
    public function it_exposes_raw_and_normalizes_uri_and_components(): void
    {
        $uri = new Uri("https://%61pple:p%61ss@ex%61mple.com:433/foob%61r?%61bc=%61bc#%61bc");

        self::assertSame('https', $uri->getRawScheme());
        self::assertSame('https', $uri->getScheme());

        self::assertSame('%61pple:p%61ss', $uri->getRawUserInfo());
        self::assertSame('apple:pass', $uri->getUserInfo());

        self::assertSame('%61pple', $uri->getRawUser());
        self::assertSame('apple', $uri->getUser());

        self::assertSame('p%61ss', $uri->getRawPassword());
        self::assertSame('pass', $uri->getPassword());

        self::assertSame('ex%61mple.com', $uri->getRawHost());
        self::assertSame('example.com', $uri->getHost());

        self::assertSame(433, $uri->getPort());

        self::assertSame('/foob%61r', $uri->getRawPath());
        self::assertSame('/foobar', $uri->getPath());

        self::assertSame('%61bc=%61bc', $uri->getRawQuery());
        self::assertSame('abc=abc', $uri->getQuery());

        self::assertSame('%61bc', $uri->getRawFragment());
        self::assertSame('abc', $uri->getFragment());

        self::assertSame("https://%61pple:p%61ss@ex%61mple.com:433/foob%61r?%61bc=%61bc#%61bc", $uri->toRawString());
        self::assertSame("https://apple:pass@example.com:433/foobar?abc=abc#abc", $uri->toString());
    }

    #[Test]
    public function it_will_normalize_uri(): void
    {
        $uri = new Uri("HTTPS://EXAMPLE.COM/foo/../bar/");

        self::assertSame('HTTPS', $uri->getRawScheme());
        self::assertSame('https', $uri->getScheme());

        self::assertSame('EXAMPLE.COM', $uri->getRawHost());
        self::assertSame('example.com', $uri->getHost());

        self::assertSame('/foo/../bar/', $uri->getRawPath());
        self::assertSame('/bar/', $uri->getPath());

        self::assertSame("HTTPS://EXAMPLE.COM/foo/../bar/", $uri->toRawString());
        self::assertSame("https://example.com/bar/", $uri->toString());
    }

    #[Test]
    public function it_can_be_unserialized(): void
    {
        $uri = new Uri("HTTPS://EXAMPLE.COM/foo/../bar/");
        $uriB = unserialize(serialize($uri));

        self::assertSame($uri->toRawString(), $uriB->toRawString());
        self::assertTrue($uriB->equals($uri));
    }

    #[Test]
    public function it_can_be_check_for_equivalent(): void
    {
        $uri1 = new Uri('http://example.com#foobar');
        $uri2 = new Uri('http://example.com');

        self::assertTrue($uri1->equals($uri2, true));
        self::assertFalse($uri1->equals($uri2, false));
    }

    #[Test]
    public function it_can_resolve_uri(): void
    {
        $uri = new Uri("https://example.com");

        self::assertSame("https://example.com/foo", $uri->resolve("/foo")->toString());
    }

    #[Test]
    public function it_can_be_modified_using_its_components(): void
    {
        $uri = new Uri("https://%61pple:p%61ss@ex%61mple.com:433/foob%61r?%61bc=%61bc#%61bc");
        $uriBis = $uri
            ->withScheme('https')
            ->withUserInfo('apple:pass')
            ->withHost('example.com')
            ->withPort(433)
            ->withPath('/foobar')
            ->withQuery('abc=abc')
            ->withFragment('abc');

        self::assertTrue($uriBis->equals($uri));
        self::assertNotSame($uri->toRawString(), $uriBis->toRawString());
        self::assertSame([
            'scheme' => 'https',
            'user' => 'apple',
            'password' => 'pass',
            'host' => 'example.com',
            'port' => 433,
            'path' => '/foobar',
            'query' => 'abc=abc',
            'fragment' => 'abc',
        ], $uriBis->__debugInfo());
    }
}
