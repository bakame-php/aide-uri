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

use OutOfBoundsException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

use function array_filter;
use function array_values;
use function is_scalar;
use function is_string;

/**
 * @internal
 *
 * This class allows accessing WHATWG errors
 * emitted by \Rowbot\URL\URL and convert them
 * into an array of \Uri\WhatWg\UrlValidationError instances
 *
 * This class IS NOT PART of the RFC public API
 * but is needed to implement the polyfill against
 * the \Rowbot\URL\URL package
 */
final class ValidationErrorLogger extends AbstractLogger
{
    /** @var list<UrlValidationError> */
    private array $errors = [];

    public function reset(): void
    {
        $this->errors = [];
    }

    /**
     * @return list<UrlValidationError>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return list<UrlValidationError>
     */
    public function recoverableErrors(): array
    {
        return array_values(
            array_filter(
                $this->errors,
                fn (UrlValidationError $error): bool => !$error->failure
            )
        );
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $errorContext = $context['input'] ?? null;
        if (is_scalar($errorContext) || $errorContext instanceof Stringable) {
            $errorContext = (string) $errorContext;
        }

        if (!is_string($errorContext)) {
            return;
        }

        $this->errors[] = new UrlValidationError(
            $errorContext,
            match ((string) $message) {
                'missing-scheme-non-relative-URL' => UrlValidationErrorType::MissingSchemeNonRelativeUrl,
                'special-scheme-missing-following-solidus' => UrlValidationErrorType::SpecialSchemeMissingFollowingSolidus,
                'invalid-credentials' => UrlValidationErrorType::InvalidCredentials,
                'invalid-reverse-solidus' => UrlValidationErrorType::InvalidReverseSoldius,
                'domain-to-ASCII' => UrlValidationErrorType::DomainToAscii,
                'domain-to-unicode' => UrlValidationErrorType::DomainToUnicode,
                'domain-invalid-code-point' => UrlValidationErrorType::DomainInvalidCodePoint,
                'host-invalid-code-point' => UrlValidationErrorType::HostInvalidCodePoint,
                'IPv4-part-empty' => UrlValidationErrorType::Ipv4EmptyPart,
                'IPv4-non-decimal-part' => UrlValidationErrorType::Ipv4NonDecimalPart,
                'IPv4-too-many-parts' => UrlValidationErrorType::Ipv4TooManyParts,
                'IPv4-non-numeric-part' => UrlValidationErrorType::Ipv4NonNumericPart,
                'IPv4-out-of-range-part' => UrlValidationErrorType::Ipv4OutOfRangePart,
                'IPv6-invalid-compression' => UrlValidationErrorType::Ipv6InvalidCompression,
                'IPv6-invalid-code-point' => UrlValidationErrorType::Ipv6InvalidCodePoint,
                'IPv6-multiple-compression' => UrlValidationErrorType::Ipv6MultipleCompression,
                'IPv6-too-few-pieces' => UrlValidationErrorType::Ipv6TooFewPieces,
                'IPv6-too-many-pieces' => UrlValidationErrorType::Ipv6TooManyPieces,
                'IPv6-unclosed' => UrlValidationErrorType::Ipv6Unclosed,
                'IPv4-in-IPv6-out-of-range-part' => UrlValidationErrorType::Ipv4InIpv6OutOfRangePart,
                'IPv4-in-IPv6-too-few-parts' => UrlValidationErrorType::Ipv4InIpv6TooFewParts,
                'IPv4-in-IPv6-invalid-code-point' => UrlValidationErrorType::Ipv4InIpv6InvalidCodePoint,
                'IPv4-in-IPv6-too-many-pieces' => UrlValidationErrorType::Ipv4InIpv6TooManyPieces,
                'host-missing' => UrlValidationErrorType::HostMissing,
                'port-out-of-range' => UrlValidationErrorType::PortOutOfRange,
                'port-invalid' => UrlValidationErrorType::PortInvalid,
                'invalid-URL-unit' => UrlValidationErrorType::InvalidUrlUnit,
                'file-invalid-Windows-drive-letter-host' => UrlValidationErrorType::FileInvalidWindowsDriveLetterHost,
                'file-invalid-Windows-drive-letter' => UrlValidationErrorType::FileInvalidWindowsDriveLetter,
                default  => throw new OutOfBoundsException('unknown error type:'.$message),
            },
            LogLevel::WARNING === $level,
        );
    }
}
