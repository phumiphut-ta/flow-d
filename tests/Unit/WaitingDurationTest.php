<?php

namespace Tests\Unit;

use App\Models\DocumentMovement;
use Carbon\Carbon;
use Tests\TestCase;

class WaitingDurationTest extends TestCase
{
    public function test_duration_under_a_minute(): void
    {
        $movement = new DocumentMovement;
        $movement->in_datetime = Carbon::parse('2026-10-29 10:00:00');
        $movement->out_datetime = Carbon::parse('2026-10-29 10:00:25'); // 25 seconds

        $this->assertEquals('0 นาที', $movement->waiting_duration);
    }

    public function test_duration_minutes_only(): void
    {
        $movement = new DocumentMovement;
        $movement->in_datetime = Carbon::parse('2026-10-29 10:00:00');
        $movement->out_datetime = Carbon::parse('2026-10-29 10:45:30');

        $this->assertEquals('45 นาที', $movement->waiting_duration);
    }

    public function test_duration_hours_and_minutes(): void
    {
        $movement = new DocumentMovement;
        $movement->in_datetime = Carbon::parse('2026-10-29 08:00:00');
        $movement->out_datetime = Carbon::parse('2026-10-29 10:15:00');

        $this->assertEquals('2 ชั่วโมง 15 นาที', $movement->waiting_duration);
    }

    public function test_duration_days_hours_and_minutes(): void
    {
        $movement = new DocumentMovement;
        $movement->in_datetime = Carbon::parse('2026-10-25 08:00:00');
        $movement->out_datetime = Carbon::parse('2026-10-28 13:20:00');

        $this->assertEquals('3 วัน 5 ชั่วโมง 20 นาที', $movement->waiting_duration);
    }

    public function test_duration_months_days_hours_and_minutes(): void
    {
        $movement = new DocumentMovement;
        $movement->in_datetime = Carbon::parse('2026-08-10 08:00:00');
        $movement->out_datetime = Carbon::parse('2026-10-15 14:30:00');

        $this->assertStringContainsString('เดือน', $movement->waiting_duration);
        $this->assertStringContainsString('วัน', $movement->waiting_duration);
        $this->assertStringContainsString('ชั่วโมง', $movement->waiting_duration);
        $this->assertStringContainsString('นาที', $movement->waiting_duration);
        $this->assertStringNotContainsString('วินาที', $movement->waiting_duration);
        $this->assertStringNotContainsString('.', $movement->waiting_duration);
    }
}
