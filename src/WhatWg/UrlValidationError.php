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

namespace Uri\WhatWg;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    /**
     * This is a user-land polyfill to the native Uri\WhatWg\UrlValidationError class proposed
     * in the PHP RFC: Add RFC 3986 and WHATWG compliant URI parsing support.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     */
    class UrlValidationError
    {
        public function __construct(
            public readonly string $context,
            public readonly UrlValidationErrorType $type,
            public readonly bool $failure
        ) {
        }
    }
}
