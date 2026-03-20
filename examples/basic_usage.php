<?php

declare(strict_types=1);

/**
 * Carbon — Basic Usage Examples
 *
 * Demonstrates creation, formatting, arithmetic, comparison, and localisation.
 * Run: php examples/basic_usage.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Carbon\Constants\Format;

// ── 1. Creating instances ────────────────────────────────────────────────────

$now    = Carbon::now();
$today  = Carbon::today();
$past   = Carbon::parse('2020-03-15 09:30:00');
$future = CarbonImmutable::parse('next Monday');

echo "Now:    {$now->toDateTimeString()}\n";
echo "Today:  {$today->toDateString()}\n";
echo "Past:   {$past->toDateTimeString()}\n";
echo "Future: {$future->toDateTimeString()}\n";

// ── 2. Formatting ────────────────────────────────────────────────────────────

echo "\nFormatting:\n";
echo "  ISO 8601:  " . $now->toIso8601String() . "\n";
echo "  RFC 7231:  " . $now->format(Format::RFC7231_FORMAT) . "\n";
echo "  Human:     " . $now->toDayDateTimeString() . "\n";

// ── 3. Date arithmetic ───────────────────────────────────────────────────────

$next = $now->copy()->addDays(7)->addHours(3)->subMinutes(30);
echo "\nIn 7 days 3 hours minus 30 minutes: {$next->toDateTimeString()}\n";

// CarbonImmutable always returns a new instance
$base     = CarbonImmutable::parse('2025-01-01');
$modified = $base->addMonths(3);
echo "Base unchanged: {$base->toDateString()}, modified: {$modified->toDateString()}\n";

// ── 4. Differences ───────────────────────────────────────────────────────────

$a = Carbon::parse('2025-01-01');
$b = Carbon::parse('2025-06-15');

echo "\nDifference: " . $a->diffInDays($b) . " days\n";
echo "Diff for humans: " . $a->diffForHumans($b) . "\n";

// ── 5. Comparisons ───────────────────────────────────────────────────────────

$before = Carbon::parse('2020-01-01');
$after  = Carbon::parse('2030-01-01');

echo "\nIs past: " . ($before->isPast() ? 'yes' : 'no') . "\n";
echo "Is future: " . ($after->isFuture() ? 'yes' : 'no') . "\n";
echo "Is weekend: " . ($now->isWeekend() ? 'yes' : 'no') . "\n";

// ── 6. Start / end of period ─────────────────────────────────────────────────

echo "\nStart of month: " . $now->copy()->startOfMonth()->toDateString() . "\n";
echo "End of year:    " . $now->copy()->endOfYear()->toDateString() . "\n";
echo "Start of week:  " . $now->copy()->startOfWeek()->toDateString() . "\n";

// ── 7. Localisation ──────────────────────────────────────────────────────────

$fr = Carbon::parse('2025-03-20');
$fr->locale('fr');
echo "\nFrench: " . $fr->isoFormat('dddd D MMMM YYYY') . "\n";

$de = Carbon::parse('2025-03-20');
$de->locale('de');
echo "German: " . $de->diffForHumans() . "\n";

// ── 8. CarbonInterval ────────────────────────────────────────────────────────

$interval = CarbonInterval::days(3)->hours(4)->minutes(30);
echo "\nInterval: {$interval->forHumans()}\n";
echo "In minutes: " . $interval->totalMinutes . "\n";

// ── 9. Time mocking for tests ────────────────────────────────────────────────

Carbon::setTestNow('2025-01-15 12:00:00');
echo "\nMocked now: " . Carbon::now()->toDateTimeString() . "\n";
Carbon::setTestNow(); // clear mock
echo "Restored now is approximate current time: "
    . (Carbon::now()->diffInSeconds(Carbon::now()) < 2 ? 'yes' : 'no') . "\n";
