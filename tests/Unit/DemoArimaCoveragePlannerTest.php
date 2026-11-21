<?php

namespace Tests\Unit;

use App\Services\DemoArimaCoveragePlanner;
use PHPUnit\Framework\TestCase;

class DemoArimaCoveragePlannerTest extends TestCase
{
    public function test_all_supported_cutoffs_are_covered_without_using_closed_days_and_rerunning_adds_nothing(): void
    {
        $values = array_fill(0, 365, 0);
        $open = array_map(fn ($i) => $i % 7 !== 0, range(0, 364));
        $values[3] = 4;
        $values[12] = 2;
        $planner = new DemoArimaCoveragePlanner;
        $additions = $planner->plan($values, $open);
        foreach ($additions as $index => $units) {
            self::assertTrue($open[$index]);
            $values[$index] += $units;
        }
        self::assertSame(4, $values[3]);
        self::assertSame(2, $values[12]);
        for ($cutoff = 89; $cutoff < 365; $cutoff++) {
            $window = array_slice($values, max(0, $cutoff - 179), min(180, $cutoff + 1));
            self::assertGreaterThanOrEqual(14, count(array_filter($window, fn ($v) => $v > 0)));
            self::assertGreaterThan(1, count(array_unique($window)));
        }
        self::assertSame([], $planner->plan($values, $open));
    }

    public function test_constant_series_receives_variation(): void
    {
        $values = array_fill(0, 90, 2);
        $planner = new DemoArimaCoveragePlanner;
        $additions = $planner->plan($values, array_fill(0, 90, true));
        self::assertCount(1, $additions);
        foreach ($additions as $i => $units) {
            $values[$i] += $units;
        }
        self::assertSame([], $planner->plan($values, array_fill(0, 90, true)));
    }

    public function test_recent_missing_history_is_rejected_instead_of_fabricating_calendar_completeness(): void
    {
        $values = array_fill(0, 180, 0);
        $values[100] = null;
        $this->expectException(\RuntimeException::class);
        (new DemoArimaCoveragePlanner)->plan($values, array_fill(0, 180, true), 179);
    }

    public function test_missing_day_before_the_complete_ninety_day_tail_is_allowed(): void
    {
        $values = array_fill(0, 180, 0);
        $values[89] = null;
        $additions = (new DemoArimaCoveragePlanner)->plan($values, array_fill(0, 180, true), 179);
        self::assertCount(14, $additions);
        self::assertGreaterThan(89, min(array_keys($additions)));
    }
}
