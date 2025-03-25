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

use Exception;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    class InvalidUriException extends UriException
    {
        /** @var array<int, string> $errors */
        public readonly array $errors;

        /**
         * @param array<int, string> $errors
         */
        public function __construct(string $message, int $code = 0, ?Exception $previous = null, array $errors = [])
        {
            parent::__construct('URI parsing failed; '.$message, $code, $previous);

            $this->errors = $errors;
        }
    }
}
