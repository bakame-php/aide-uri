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

use Exception;
use Uri\InvalidUriException;

use function array_values;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    /**
     * This is a user-land polyfill to the native Uri\WhatWg\InvalidUrlException class proposed
     * in the PHP RFC: Add RFC 3986 and WHATWG compliant URI parsing support.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     */
    class InvalidUrlException extends InvalidUriException
    {
        /** @var list<UrlValidationError> */
        public readonly array $errors;

        /**
         * @param list<UrlValidationError> $errors
         */
        public function __construct(string $message, array $errors = [], int $code = 0, ?Exception $previous = null)
        {
            parent::__construct($message, $code, $previous);

            $filter = static fn (UrlValidationError ...$errors): array => $errors;

            $this->errors = array_values($filter(...$errors));
        }
    }
}
