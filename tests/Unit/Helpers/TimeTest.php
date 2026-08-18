<?php

namespace DarkOak\Tests\Unit\Helpers;

use DarkOak\Helpers\Time;
use PHPUnit\Framework\TestCase;

final class TimeTest extends TestCase
{
    public function test_get_mysql_timezone_offset_returns_valid_offset(): void
    {
        $offset = Time::getMySQLTimezoneOffset('UTC');
        $this->assertMatchesRegularExpression('/^[+-]\d{2}:\d{2}$/', $offset);
        $this->assertSame('+00:00', $offset);
    }

    public function test_get_mysql_timezone_offset_for_berlin(): void
    {
        $offset = Time::getMySQLTimezoneOffset('Europe/Berlin');
        // Europe/Berlin is either CET (+01:00) or CEST (+02:00) depending on DST.
        $this->assertContains($offset, ['+01:00', '+02:00']);
    }
}
