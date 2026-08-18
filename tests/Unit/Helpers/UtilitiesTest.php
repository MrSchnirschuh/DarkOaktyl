<?php

namespace DarkOak\Tests\Unit\Helpers;

use DarkOak\Helpers\Utilities;
use PHPUnit\Framework\TestCase;

final class UtilitiesTest extends TestCase
{
    public function test_get_schedule_next_run_date_returns_carbon_instance(): void
    {
        $date = Utilities::getScheduleNextRunDate('0', '0', '*', '*', '*');
        $this->assertInstanceOf(\Carbon\Carbon::class, $date);
    }

    public function test_random_string_with_special_characters_has_requested_length(): void
    {
        $string = Utilities::randomStringWithSpecialCharacters(16);
        $this->assertSame(16, strlen($string));
    }
}
