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

namespace Uri;

use PhpBench\Attributes as Bench;
use function parse_url;

final class PolyfillBench
{
    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 2097152'), Bench\Assert('mode(variant.time.avg) < 10000000')]
    public function benchParsingARegularUriWithParseUrl(): void
    {
        $uri = 'https://uri.thephpleague.com:1337/5.0?query=value1&query=value2#foobar';

        for ($i = 0; $i < 100_000; $i++) {
            $__ = parse_url($uri);
        }
    }

    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 2097152'), Bench\Assert('mode(variant.time.avg) < 10000000')]
    public function benchParsingWithRfc3986UriInstance(): void
    {
        $uri = 'https://uri.thephpleague.com:1337/5.0?query=value1&query=value2#foobar';

        for ($i = 0; $i < 100_000; $i++) {
            Rfc3986\Uri::parse($uri);
        }
    }

    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 3318320'), Bench\Assert('mode(variant.time.avg) < 10000000')]
    public function benchParsingWithWhatWgUrlInstance(): void
    {
        $uri = 'https://uri.thephpleague.com:1337/5.0?query=value1&query=value2#foobar';

        for ($i = 0; $i < 100_000; $i++) {
            WhatWg\Url::parse($uri);
        }
    }
}
