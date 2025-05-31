<?php

declare(strict_types=1);

namespace WhatWg;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TypeError;
use Uri\WhatWg\InvalidUrlException;

final class InvalidUrlExceptionTest extends TestCase
{
    #[Test]
    public function it_can_not_be_instantiated_with_an_invalid_eerors(): void
    {
        $this->expectException(TypeError::class);

        new InvalidUrlException('message', ['error']);
    }
}
