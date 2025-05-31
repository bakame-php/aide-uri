<?php

declare(strict_types=1);

namespace WhatWg;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Uri\WhatWg\InvalidUrlException;
use ValueError;

final class InvalidUrlExceptionTest extends TestCase
{

    #[Test]
    public function it_can_not_be_instantiated_with_an_array_which_is_not_a_list(): void
    {
        $this->expectException(ValueError::class);

        new InvalidUrlException('message', ['foo' => 'bar']);
    }

    #[Test]
    public function it_can_not_be_instantiated_with_a_list_which_contains_other_than_url_validation_error_instances(): void
    {
        $this->expectException(ValueError::class);

        new InvalidUrlException('message', ['error']);
    }
}
