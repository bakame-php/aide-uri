<?php

declare(strict_types=1);

namespace Uri\WhatWg;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    #[Test]
    public function it_will_fail_parse_an_invalid_url(): void
    {
        $errors = [];
        $url = Url::parse("invalid url", null, $errors);

        self::assertNull($url);
        self::assertNotEmpty($errors);
        self::assertInstanceOf(UrlValidationError::class, $errors[0]);
        self::assertSame(UrlValidationErrorType::MissingSchemeNonRelativeUrl, $errors[0]->type);
    }

    #[Test]
    public function it_will_return_soft_errors_when_uri_is_parsed_with_errors(): void
    {
        $softErrors = [];
        $url = new Url(" https://example.org", null, $softErrors);

        self::assertSame('https://example.org/', $url->toAsciiString());
        self::assertNotEmpty($softErrors);
        self::assertInstanceOf(UrlValidationError::class, $softErrors[0]);
        self::assertSame(UrlValidationErrorType::InvalidUrlUnit, $softErrors[0]->type);
    }
}
