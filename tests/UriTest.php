<?php

declare(strict_types=1);

namespace Bakame\Polyfill\Rfc3986;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
    #[Test]
    public function it_can_parse_an_uri(): void
    {
        $uri = Uri::parse('http://example.com');

        self::assertSame('http://example.com', $uri->toRawString());
        self::assertSame('http://example.com/', $uri->toString());
    }
}
