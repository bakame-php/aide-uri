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

        self::assertInstanceOf(Uri::class, $uri);
        self::assertSame('http://example.com', $uri->toRawString());
        self::assertSame('http://example.com/', $uri->toString());
    }
}
