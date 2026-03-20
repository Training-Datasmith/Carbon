<?php

declare(strict_types=1);

/**
 * Carbon — CarbonImmutable and CarbonPeriod Examples
 *
 * Demonstrates immutable dates, period ranges, and recurrence.
 * Run: php examples/immutable_and_periods.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Carbon\CarbonInterval;

// ── 1. Immutable instances (no side-effects) ─────────────────────────────────

$original = CarbonImmutable::parse('2025-06-01');
$plusWeek = $original->addWeek();
$minusDay = $original->subDay();

// $original is unchanged
echo "Original: {$original->toDateString()}\n";
echo "+1 week:  {$plusWeek->toDateString()}\n";
echo "-1 day:   {$minusDay->toDateString()}\n";

// ── 2. Iteration with CarbonPeriod ───────────────────────────────────────────

echo "\nEvery Monday in Q1 2025:\n";
$period = CarbonPeriod::create('2025-01-01', '1 week', '2025-03-31')
    ->filter(fn($date) => $date->isDayOfWeek(Carbon\Carbon::MONDAY));

foreach ($period as $date) {
    echo '  ' . $date->toDateString() . "\n";
}

// ── 3. Business-day iteration ────────────────────────────────────────────────

echo "\nFirst 5 business days of 2025:\n";
$business = CarbonPeriod::create('2025-01-01', '1 day')
    ->filter(fn($d) => $d->isWeekday())
    ->take(5);

foreach ($business as $d) {
    echo '  ' . $d->format('D, Y-m-d') . "\n";
}

// ── 4. Custom interval ───────────────────────────────────────────────────────

echo "\nEvery 90 minutes for 6 hours from now:\n";
$start = CarbonImmutable::parse('2025-01-01 08:00:00');
$end   = $start->addHours(6);

foreach (CarbonPeriod::create($start, CarbonInterval::minutes(90), $end) as $slot) {
    echo '  ' . $slot->format('H:i') . "\n";
}

// ── 5. Checking period membership ───────────────────────────────────────────

$sprint = CarbonPeriod::create('2025-02-01', '2025-02-14');
$check  = CarbonImmutable::parse('2025-02-07');

echo "\nIs 2025-02-07 in sprint: " . ($sprint->isInPeriod($check) ? 'yes' : 'no') . "\n";
echo "Sprint length (days): " . $sprint->count() . "\n";
