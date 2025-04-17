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
use Stringable;

use function is_scalar;
use function is_string;
use function strtr;

/**
 * @internal
 *
 * This class allows accessing WHATWG errors
 * emitted by \Rowbot\URL\URL and convert them
 * into \Uri\WhatWg\UrlValidationError instances
 *
 * This class IS NOT PART of the RFC public API
 * but is needed to implement the polyfill.
 */
final class ValidationErrorLog extends AbstractLogger
{
    /** @var array<int, UrlValidationError> */
    private array $recoverableErrors = [];
    /** @var array<int, UrlValidationError> */
    private array $errors = [];

    /**
     * @return array<int, UrlValidationError>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<int, UrlValidationError>
     */
    public function recoverableErrors(): array
    {
        return $this->recoverableErrors;
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $errorContext = $context['input'] ?? null;
        if ($errorContext instanceof Stringable) {
            $errorContext = (string) $errorContext;
        }

        if (!is_string($errorContext)) {
            return;
        }

        $validationError = new UrlValidationError(
            $errorContext,
            $this->messageMapper($this->interpolate($message, $context)),
            'warning' === $level
        );

        $this->errors[] = $validationError;
        if ('notice' === $level) {
            $this->recoverableErrors[] = $validationError;
        }
    }

    public function reset(): void
    {
        $this->recoverableErrors = [];
        $this->errors = [];
    }

    private function messageMapper(string $message): UrlValidationErrorType
    {
        return match ($message) {
            // recoverable errors
            'special-scheme-missing-following-solidus' => UrlValidationErrorType::SpecialSchemeMissingFollowingSolidus,
            'invalid-URL-unit' => UrlValidationErrorType::InvalidUrlUnit,
            'IPv4-part-empty' => UrlValidationErrorType::Ipv4EmptyPart,
            'IPv4-non-decimal-part' => UrlValidationErrorType::Ipv4NonDecimalPart,
            'invalid-credentials' => UrlValidationErrorType::InvalidCredentials,
            'file-invalid-Windows-drive-letter-host' => UrlValidationErrorType::FileInvalidWindowsDriveLetterHost,
            'invalid-reverse-solidus' => UrlValidationErrorType::InvalidReverseSoldius,
            'file-invalid-Windows-drive-letter' => UrlValidationErrorType::FileInvalidWindowsDriveLetter,
            // unrecoverable errors
            'IPv6-unclosed' => UrlValidationErrorType::Ipv6Unclosed,
            'missing-scheme-non-relative-URL' => UrlValidationErrorType::MissingSchemeNonRelativeUrl,
            'domain-invalid-code-point' => UrlValidationErrorType::DomainInvalidCodePoint,
            'domain-to-ASCII' => UrlValidationErrorType::DomainToAscii,
            'host-invalid-code-point' => UrlValidationErrorType::HostInvalidCodePoint,
            'IPv4-too-many-parts' => UrlValidationErrorType::Ipv4TooManyParts,
            'IPv4-non-numeric-part' => UrlValidationErrorType::Ipv4NonNumericPart,
            'IPv6-invalid-compression' => UrlValidationErrorType::Ipv6InvalidCompression,
            'IPv6-too-many-pieces' => UrlValidationErrorType::Ipv6TooManyPieces,
            'IPv6-multiple-compression' => UrlValidationErrorType::Ipv6MultipleCompression,
            'IPv4-in-IPv6-invalid-code-point' => UrlValidationErrorType::Ipv4InIpv6InvalidCodePoint,
            'IPv4-in-IPv6-too-many-pieces' => UrlValidationErrorType::Ipv4InIpv6TooManyPieces,
            'IPv6-invalid-code-point' => UrlValidationErrorType::Ipv6InvalidCodePoint,
            'IPv6-too-few-pieces' => UrlValidationErrorType::Ipv6TooFewPieces,
            'IPv4-in-IPv6-out-of-range-part' => UrlValidationErrorType::Ipv4InIpv6OutOfRangePart,
            'IPv4-in-IPv6-too-few-parts' => UrlValidationErrorType::Ipv4InIpv6TooFewParts,
            'host-missing' => UrlValidationErrorType::HostMissing,
            'port-out-of-range' => UrlValidationErrorType::PortOutOfRange,
            'port-invalid' => UrlValidationErrorType::PortInvalid,
            default  => throw new OutOfBoundsException('unknown error type:'.$message),
        };
    }

    /**
     * @param array<array-key, mixed> $context
     */
    private function interpolate(string|Stringable $message, array $context = []): string
    {
        $replacements = [];
        foreach ($context as $key => $val) {
            if (is_scalar($val) || $val instanceof Stringable) {
                $replacements['{'.$key.'}'] = $val;
            }
        }

        return strtr((string) $message, $replacements);
    }
}
