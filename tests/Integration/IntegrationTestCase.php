<?php

namespace DarkOak\Tests\Integration;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DarkOak\Tests\TestCase;
use DarkOak\Events\ActivityLogged;
use Illuminate\Support\Facades\Event;
use DarkOak\Tests\Assertions\AssertsActivityLogged;
use DarkOak\Tests\Traits\Integration\CreatesTestModels;

abstract class IntegrationTestCase extends TestCase
{
    use CreatesTestModels;
    use AssertsActivityLogged;

    protected $defaultHeaders = [
        'Accept' => 'application/json',
    ];

    public function setUp(): void
    {
        parent::setUp();

        Event::fake(ActivityLogged::class);
    }

    /**
     * Return an ISO-8601 formatted timestamp to use in the API response.
     */
    protected function formatTimestamp(string $timestamp): string
    {
        return CarbonImmutable::createFromFormat(CarbonInterface::DEFAULT_TO_STRING_FORMAT, $timestamp)
            ->setTimezone('UTC')
            ->toAtomString();
    }
}

