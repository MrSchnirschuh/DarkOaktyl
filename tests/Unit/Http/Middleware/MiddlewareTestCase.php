<?php

namespace DarkOak\Tests\Unit\Http\Middleware;

use DarkOak\Tests\TestCase;
use DarkOak\Tests\Traits\Http\RequestMockHelpers;
use DarkOak\Tests\Traits\Http\MocksMiddlewareClosure;
use DarkOak\Tests\Assertions\MiddlewareAttributeAssertionsTrait;

abstract class MiddlewareTestCase extends TestCase
{
    use MiddlewareAttributeAssertionsTrait;
    use MocksMiddlewareClosure;
    use RequestMockHelpers;

    /**
     * Setup tests with a mocked request object and normal attributes.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->buildRequestMock();
    }
}

