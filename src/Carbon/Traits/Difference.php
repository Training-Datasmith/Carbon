<?php

declare (strict_types=1);
/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Carbon\Traits;

use Carbon\Carbon;
use Carbon\Carbon_Immutable;
use Carbon\Carbon_Interface;
use Carbon\Carbon_Interval;
use Carbon\Carbon_Period;
use Carbon\Exceptions\Unknown_Unit_Exception;
use Carbon\Unit;
use Closure;
use DateInterval;
use DateTimeInterface;
/**
 * Trait Difference.
 *
 * Depends on the following methods:
 *
 * @method bool lessThan($date)
 * @method static copy()
 * @method static resolveCarbon($date = null)
 */
trait Difference
{
    /**
     * Get the difference as a DateInterval instance.
     * Return relative interval (negative if $absolute flag is not set to true and the given date is before
     * current one).
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     */
    public function diff_as_date_interval($date = null, bool $absolute = false): DateInterval
    {
        $other = $this->resolve_carbon($date);
        // Work-around for https://bugs.php.net/bug.php?id=81458
        // It was initially introduced for https://bugs.php.net/bug.php?id=80998
        // The very specific case of 80998 was fixed in PHP 8.1beta3, but it introduced 81458
        // So we still need to keep this for now
        if ($other->tz !== $this->tz) {
            $other = $other->avoid_mutation()->set_timezone($this->tz);
        }
        return parent::diff($other, $absolute);
    }
    /**
     * Get the difference as a CarbonInterval instance.
     * Return relative interval (negative if $absolute flag is not set to true and the given date is before
     * current one).
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     */
    public function diff_as_carbon_interval($date = null, bool $absolute = false, array $skip = []): Carbon_Interval
    {
        return Carbon_Interval::diff($this, $this->resolve_carbon($date), $absolute, $skip)->set_local_translator($this->get_local_translator());
    }
    /**
     * @alias diffAsCarbonInterval
     *
     * Get the difference as a DateInterval instance.
     * Return relative interval (negative if $absolute flag is not set to true and the given date is before
     * current one).
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     */
    public function diff($date = null, bool $absolute = false, array $skip = []): Carbon_Interval
    {
        return $this->diff_as_carbon_interval($date, $absolute, $skip);
    }
    /**
     * @param Unit|string                                            $unit     microsecond, millisecond, second, minute,
     *                                                                         hour, day, week, month, quarter, year,
     *                                                                         century, millennium
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     * @param bool                                                   $utc      Always convert dates to UTC before comparing (if not set, it will do it only if timezones are different)
     */
    public function diff_in_unit(Unit|string $unit, $date = null, bool $absolute = false, bool $utc = false): float
    {
        $unit = static::plural_unit($unit instanceof Unit ? $unit->value : rtrim($unit, 'z'));
        $method = 'diffIn' . $unit;
        if (!method_exists($this, $method)) {
            throw new Unknown_Unit_Exception($unit);
        }
        return $this->{$method}($date, $absolute, $utc);
    }
    /**
     * Get the difference in years
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     * @param bool                                                   $utc      Always convert dates to UTC before comparing (if not set, it will do it only if timezones are different)
     */
    public function diff_in_years($date = null, bool $absolute = false, bool $utc = false): float
    {
        $start = $this;
        $end = $this->resolve_carbon($date);
        if ($utc) {
            $start = $start->avoid_mutation()->utc();
            $end = $end->avoid_mutation()->utc();
        }
        $ascending = $start <= $end;
        $sign = $absolute || $ascending ? 1 : -1;
        if (!$ascending) {
            [$start, $end] = [$end, $start];
        }
        $years_diff = (int) $start->diff($end, $absolute)->format('%r%y');
        /** @var Carbon|CarbonImmutable $floorEnd */
        $floor_end = $start->avoid_mutation()->add_years($years_diff);
        if ($floor_end >= $end) {
            return $sign * $years_diff;
        }
        /** @var Carbon|CarbonImmutable $ceilEnd */
        $ceil_end = $start->avoid_mutation()->add_years($years_diff + 1);
        $days_to_floor = $floor_end->diff_in_days($end);
        $days_to_ceil = $end->diff_in_days($ceil_end);
        return $sign * ($years_diff + $days_to_floor / ($days_to_ceil + $days_to_floor));
    }
    /**
     * Get the difference in quarters.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     * @param bool                                                   $utc      Always convert dates to UTC before comparing (if not set, it will do it only if timezones are different)
     */
    public function diff_in_quarters($date = null, bool $absolute = false, bool $utc = false): float
    {
        return $this->diff_in_months($date, $absolute, $utc) / static::MONTHS_PER_QUARTER;
    }
    /**
     * Get the difference in months.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     * @param bool                                                   $utc      Always convert dates to UTC before comparing (if not set, it will do it only if timezones are different)
     */
    public function diff_in_months($date = null, bool $absolute = false, bool $utc = false): float
    {
        $start = $this;
        $end = $this->resolve_carbon($date);
        // Compare using UTC
        if ($utc || $end->timezone_name !== $start->timezone_name) {
            $start = $start->avoid_mutation()->utc();
            $end = $end->avoid_mutation()->utc();
        }
        [$year_start, $month_start, $day_start] = explode('-', $start->format('Y-m-dHisu'));
        [$year_end, $month_end, $day_end] = explode('-', $end->format('Y-m-dHisu'));
        $months_diff = ((int) $year_end - (int) $year_start) * static::MONTHS_PER_YEAR + (int) $month_end - (int) $month_start;
        if ($months_diff > 0) {
            $months_diff -= $day_start > $day_end ? 1 : 0;
        } elseif ($months_diff < 0) {
            $months_diff += $day_start < $day_end ? 1 : 0;
        }
        $ascending = $start <= $end;
        $sign = $absolute || $ascending ? 1 : -1;
        $months_diff = abs($months_diff);
        if (!$ascending) {
            [$start, $end] = [$end, $start];
        }
        /** @var Carbon|CarbonImmutable $floorEnd */
        $floor_end = $start->avoid_mutation()->add_months($months_diff);
        if ($floor_end >= $end) {
            return $sign * $months_diff;
        }
        /** @var Carbon|CarbonImmutable $ceilEnd */
        $ceil_end = $start->avoid_mutation()->add_months($months_diff + 1);
        $days_to_floor = $floor_end->diff_in_days($end);
        $days_to_ceil = $end->diff_in_days($ceil_end);
        return $sign * ($months_diff + $days_to_floor / ($days_to_ceil + $days_to_floor));
    }
    /**
     * Get the difference in weeks.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     * @param bool                                                   $utc      Always convert dates to UTC before comparing (if not set, it will do it only if timezones are different)
     */
    public function diff_in_weeks($date = null, bool $absolute = false, bool $utc = false): float
    {
        return $this->diff_in_days($date, $absolute, $utc) / static::DAYS_PER_WEEK;
    }
    /**
     * Get the difference in days.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     * @param bool                                                   $utc      Always convert dates to UTC before comparing (if not set, it will do it only if timezones are different)
     */
    public function diff_in_days($date = null, bool $absolute = false, bool $utc = false): float
    {
        $date = $this->resolve_carbon($date);
        $current = $this;
        // Compare using UTC
        if ($utc || $date->timezone_name !== $current->timezone_name) {
            $date = $date->avoid_mutation()->utc();
            $current = $current->avoid_mutation()->utc();
        }
        $negative = $date < $current;
        [$start, $end] = $negative ? [$date, $current] : [$current, $date];
        $interval = $start->diff_as_date_interval($end);
        $days_a = $this->get_interval_day_diff($interval);
        $floor_end = $start->avoid_mutation()->add_days($days_a);
        $days_b = $days_a + ($floor_end <= $end ? 1 : -1);
        $ceil_end = $start->avoid_mutation()->add_days($days_b);
        $microseconds_between = $floor_end->diff_in_microseconds($ceil_end);
        $microseconds_to_end = $floor_end->diff_in_microseconds($end);
        return ($negative && !$absolute ? -1 : 1) * ($days_a * ($microseconds_between - $microseconds_to_end) + $days_b * $microseconds_to_end) / $microseconds_between;
    }
    /**
     * Get the difference in days using a filter closure.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     *
     */
    public function diff_in_days_filtered(Closure $callback, $date = null, bool $absolute = false): int
    {
        return $this->diff_filtered(Carbon_Interval::day(), $callback, $date, $absolute);
    }
    /**
     * Get the difference in hours using a filter closure.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     *
     */
    public function diff_in_hours_filtered(Closure $callback, $date = null, bool $absolute = false): int
    {
        return $this->diff_filtered(Carbon_Interval::hour(), $callback, $date, $absolute);
    }
    /**
     * Get the difference by the given interval using a filter closure.
     *
     * @param CarbonInterval                                         $ci       An interval to traverse by
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     *
     */
    public function diff_filtered(Carbon_Interval $ci, Closure $callback, $date = null, bool $absolute = false): int
    {
        $start = $this;
        $end = $this->resolve_carbon($date);
        $inverse = false;
        if ($end < $start) {
            $start = $end;
            $end = $this;
            $inverse = true;
        }
        $options = Carbon_Period::EXCLUDE_END_DATE | ($this->is_mutable() ? 0 : Carbon_Period::IMMUTABLE);
        $diff = $ci->to_period($start, $end, $options)->filter($callback)->count();
        return $inverse && !$absolute ? -$diff : $diff;
    }
    /**
     * Get the difference in weekdays.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     */
    public function diff_in_weekdays($date = null, bool $absolute = false): int
    {
        return $this->diff_in_days_filtered(static fn(Carbon_Interface $date): bool => $date->is_weekday(), $this->resolve_carbon($date)->avoid_mutation()->modify($this->format('H:i:s.u')), $absolute);
    }
    /**
     * Get the difference in weekend days using a filter.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     */
    public function diff_in_weekend_days($date = null, bool $absolute = false): int
    {
        return $this->diff_in_days_filtered(static fn(Carbon_Interface $date): bool => $date->is_weekend(), $this->resolve_carbon($date)->avoid_mutation()->modify($this->format('H:i:s.u')), $absolute);
    }
    /**
     * Get the difference in hours.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     */
    public function diff_in_hours($date = null, bool $absolute = false): float
    {
        return $this->diff_in_minutes($date, $absolute) / static::MINUTES_PER_HOUR;
    }
    /**
     * Get the difference in minutes.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     */
    public function diff_in_minutes($date = null, bool $absolute = false): float
    {
        return $this->diff_in_seconds($date, $absolute) / static::SECONDS_PER_MINUTE;
    }
    /**
     * Get the difference in seconds.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     */
    public function diff_in_seconds($date = null, bool $absolute = false): float
    {
        return $this->diff_in_milliseconds($date, $absolute) / static::MILLISECONDS_PER_SECOND;
    }
    /**
     * Get the difference in microseconds.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     */
    public function diff_in_microseconds($date = null, bool $absolute = false): float
    {
        /** @var CarbonInterface $date */
        $date = $this->resolve_carbon($date);
        $value = ($date->timestamp - $this->timestamp) * static::MICROSECONDS_PER_SECOND + $date->micro - $this->micro;
        return $absolute ? abs($value) : $value;
    }
    /**
     * Get the difference in milliseconds.
     *
     * @param \Carbon\CarbonInterface|\DateTimeInterface|string|null $date
     * @param bool                                                   $absolute Get the absolute of the difference
     */
    public function diff_in_milliseconds($date = null, bool $absolute = false): float
    {
        return $this->diff_in_microseconds($date, $absolute) / static::MICROSECONDS_PER_MILLISECOND;
    }
    /**
     * The number of seconds since midnight.
     */
    public function seconds_since_midnight(): float
    {
        return $this->diff_in_seconds($this->copy()->start_of_day(), true);
    }
    /**
     * The number of seconds until 23:59:59.
     */
    public function seconds_until_end_of_day(): float
    {
        return $this->diff_in_seconds($this->copy()->end_of_day(), true);
    }
    /**
     * Get the difference in a human readable format in the current locale from current instance to an other
     * instance given (or now if null given).
     *
     * @example
     * ```
     * echo Carbon::tomorrow()->diffForHumans() . "\n";
     * echo Carbon::tomorrow()->diffForHumans(['parts' => 2]) . "\n";
     * echo Carbon::tomorrow()->diffForHumans(['parts' => 3, 'join' => true]) . "\n";
     * echo Carbon::tomorrow()->diffForHumans(Carbon::yesterday()) . "\n";
     * echo Carbon::tomorrow()->diffForHumans(Carbon::yesterday(), ['short' => true]) . "\n";
     * ```
     *
     * @param Carbon|DateTimeInterface|string|array|null $other   if array passed, will be used as parameters array, see $syntax below;
     *                                                            if null passed, now will be used as comparison reference;
     *                                                            if any other type, it will be converted to date and used as reference.
     * @param int|array                                  $syntax  if array passed, parameters will be extracted from it, the array may contains:
     *                                                            ⦿ 'syntax' entry (see below)
     *                                                            ⦿ 'short' entry (see below)
     *                                                            ⦿ 'parts' entry (see below)
     *                                                            ⦿ 'options' entry (see below)
     *                                                            ⦿ 'skip' entry, list of units to skip (array of strings or a single string,
     *                                                            ` it can be the unit name (singular or plural) or its shortcut
     *                                                            ` (y, m, w, d, h, min, s, ms, µs).
     *                                                            ⦿ 'aUnit' entry, prefer "an hour" over "1 hour" if true
     *                                                            ⦿ 'altNumbers' entry, use alternative numbers if available
     *                                                            ` (from the current language if true is passed, from the given language(s)
     *                                                            ` if array or string is passed)
     *                                                            ⦿ 'join' entry determines how to join multiple parts of the string
     *                                                            `  - if $join is a string, it's used as a joiner glue
     *                                                            `  - if $join is a callable/closure, it get the list of string and should return a string
     *                                                            `  - if $join is an array, the first item will be the default glue, and the second item
     *                                                            `    will be used instead of the glue for the last item
     *                                                            `  - if $join is true, it will be guessed from the locale ('list' translation file entry)
     *                                                            `  - if $join is missing, a space will be used as glue
     *                                                            ⦿ 'other' entry (see above)
     *                                                            ⦿ 'minimumUnit' entry determines the smallest unit of time to display can be long or
     *                                                            `  short form of the units, e.g. 'hour' or 'h' (default value: s)
     *                                                            ⦿ 'locale' language in which the diff should be output (has no effect if 'translator' key is set)
     *                                                            ⦿ 'translator' a custom translator to use to translator the output.
     *                                                            if int passed, it adds modifiers:
     *                                                            Possible values:
     *                                                            - CarbonInterface::DIFF_ABSOLUTE          no modifiers
     *                                                            - CarbonInterface::DIFF_RELATIVE_TO_NOW   add ago/from now modifier
     *                                                            - CarbonInterface::DIFF_RELATIVE_TO_OTHER add before/after modifier
     *                                                            Default value: CarbonInterface::DIFF_ABSOLUTE
     * @param bool                                       $short   displays short format of time units
     * @param int                                        $parts   maximum number of parts to display (default value: 1: single unit)
     * @param int                                        $options human diff options
     */
    public function diff_for_humans($other = null, $syntax = null, $short = false, $parts = 1, $options = null): string
    {
        /* @var CarbonInterface $this */
        if (\is_array($other)) {
            $other['syntax'] = \array_key_exists('syntax', $other) ? $other['syntax'] : $syntax;
            $syntax = $other;
            $other = $syntax['other'] ?? null;
        }
        $int_syntax =& $syntax;
        if (\is_array($syntax)) {
            $syntax['syntax'] ??= null;
            $int_syntax =& $syntax['syntax'];
        }
        $int_syntax = (int) ($int_syntax ?? static::DIFF_RELATIVE_AUTO);
        $int_syntax = $int_syntax === static::DIFF_RELATIVE_AUTO && $other === null ? static::DIFF_RELATIVE_TO_NOW : $int_syntax;
        $parts = min(7, max(1, (int) $parts));
        $skip = \is_array($syntax) ? $syntax['skip'] ?? [] : [];
        $options ??= $this->local_human_diff_options ?? $this->transmit_factory(static fn() => static::get_human_diff_options());
        return $this->diff($other, skip: (array) $skip)->for_humans($syntax, (bool) $short, $parts, $options);
    }
    /**
     * @alias diffForHumans
     *
     * Get the difference in a human readable format in the current locale from current instance to an other
     * instance given (or now if null given).
     *
     * @param Carbon|\DateTimeInterface|string|array|null $other   if array passed, will be used as parameters array, see $syntax below;
     *                                                             if null passed, now will be used as comparison reference;
     *                                                             if any other type, it will be converted to date and used as reference.
     * @param int|array                                   $syntax  if array passed, parameters will be extracted from it, the array may contains:
     *                                                             - 'syntax' entry (see below)
     *                                                             - 'short' entry (see below)
     *                                                             - 'parts' entry (see below)
     *                                                             - 'options' entry (see below)
     *                                                             - 'join' entry determines how to join multiple parts of the string
     *                                                             `  - if $join is a string, it's used as a joiner glue
     *                                                             `  - if $join is a callable/closure, it get the list of string and should return a string
     *                                                             `  - if $join is an array, the first item will be the default glue, and the second item
     *                                                             `    will be used instead of the glue for the last item
     *                                                             `  - if $join is true, it will be guessed from the locale ('list' translation file entry)
     *                                                             `  - if $join is missing, a space will be used as glue
     *                                                             - 'other' entry (see above)
     *                                                             if int passed, it add modifiers:
     *                                                             Possible values:
     *                                                             - CarbonInterface::DIFF_ABSOLUTE          no modifiers
     *                                                             - CarbonInterface::DIFF_RELATIVE_TO_NOW   add ago/from now modifier
     *                                                             - CarbonInterface::DIFF_RELATIVE_TO_OTHER add before/after modifier
     *                                                             Default value: CarbonInterface::DIFF_ABSOLUTE
     * @param bool                                        $short   displays short format of time units
     * @param int                                         $parts   maximum number of parts to display (default value: 1: single unit)
     * @param int                                         $options human diff options
     *
     * @return string
     */
    public function from($other = null, $syntax = null, $short = false, $parts = 1, $options = null)
    {
        return $this->diff_for_humans($other, $syntax, $short, $parts, $options);
    }
    /**
     * @alias diffForHumans
     *
     * Get the difference in a human readable format in the current locale from current instance to an other
     * instance given (or now if null given).
     */
    public function since($other = null, $syntax = null, $short = false, $parts = 1, $options = null)
    {
        return $this->diff_for_humans($other, $syntax, $short, $parts, $options);
    }
    /**
     * Get the difference in a human readable format in the current locale from an other
     * instance given (or now if null given) to current instance.
     *
     * When comparing a value in the past to default now:
     * 1 hour from now
     * 5 months from now
     *
     * When comparing a value in the future to default now:
     * 1 hour ago
     * 5 months ago
     *
     * When comparing a value in the past to another value:
     * 1 hour after
     * 5 months after
     *
     * When comparing a value in the future to another value:
     * 1 hour before
     * 5 months before
     *
     * @param Carbon|\DateTimeInterface|string|array|null $other   if array passed, will be used as parameters array, see $syntax below;
     *                                                             if null passed, now will be used as comparison reference;
     *                                                             if any other type, it will be converted to date and used as reference.
     * @param int|array                                   $syntax  if array passed, parameters will be extracted from it, the array may contains:
     *                                                             - 'syntax' entry (see below)
     *                                                             - 'short' entry (see below)
     *                                                             - 'parts' entry (see below)
     *                                                             - 'options' entry (see below)
     *                                                             - 'join' entry determines how to join multiple parts of the string
     *                                                             `  - if $join is a string, it's used as a joiner glue
     *                                                             `  - if $join is a callable/closure, it get the list of string and should return a string
     *                                                             `  - if $join is an array, the first item will be the default glue, and the second item
     *                                                             `    will be used instead of the glue for the last item
     *                                                             `  - if $join is true, it will be guessed from the locale ('list' translation file entry)
     *                                                             `  - if $join is missing, a space will be used as glue
     *                                                             - 'other' entry (see above)
     *                                                             if int passed, it add modifiers:
     *                                                             Possible values:
     *                                                             - CarbonInterface::DIFF_ABSOLUTE          no modifiers
     *                                                             - CarbonInterface::DIFF_RELATIVE_TO_NOW   add ago/from now modifier
     *                                                             - CarbonInterface::DIFF_RELATIVE_TO_OTHER add before/after modifier
     *                                                             Default value: CarbonInterface::DIFF_ABSOLUTE
     * @param bool                                        $short   displays short format of time units
     * @param int                                         $parts   maximum number of parts to display (default value: 1: single unit)
     * @param int                                         $options human diff options
     *
     * @return string
     */
    public function to($other = null, $syntax = null, $short = false, $parts = 1, $options = null)
    {
        if (!$syntax && !$other) {
            $syntax = Carbon_Interface::DIFF_RELATIVE_TO_NOW;
        }
        return $this->resolve_carbon($other)->diff_for_humans($this, $syntax, $short, $parts, $options);
    }
    /**
     * @alias to
     *
     * Get the difference in a human readable format in the current locale from an other
     * instance given (or now if null given) to current instance.
     *
     * @param Carbon|\DateTimeInterface|string|array|null $other   if array passed, will be used as parameters array, see $syntax below;
     *                                                             if null passed, now will be used as comparison reference;
     *                                                             if any other type, it will be converted to date and used as reference.
     * @param int|array                                   $syntax  if array passed, parameters will be extracted from it, the array may contains:
     *                                                             - 'syntax' entry (see below)
     *                                                             - 'short' entry (see below)
     *                                                             - 'parts' entry (see below)
     *                                                             - 'options' entry (see below)
     *                                                             - 'join' entry determines how to join multiple parts of the string
     *                                                             `  - if $join is a string, it's used as a joiner glue
     *                                                             `  - if $join is a callable/closure, it get the list of string and should return a string
     *                                                             `  - if $join is an array, the first item will be the default glue, and the second item
     *                                                             `    will be used instead of the glue for the last item
     *                                                             `  - if $join is true, it will be guessed from the locale ('list' translation file entry)
     *                                                             `  - if $join is missing, a space will be used as glue
     *                                                             - 'other' entry (see above)
     *                                                             if int passed, it add modifiers:
     *                                                             Possible values:
     *                                                             - CarbonInterface::DIFF_ABSOLUTE          no modifiers
     *                                                             - CarbonInterface::DIFF_RELATIVE_TO_NOW   add ago/from now modifier
     *                                                             - CarbonInterface::DIFF_RELATIVE_TO_OTHER add before/after modifier
     *                                                             Default value: CarbonInterface::DIFF_ABSOLUTE
     * @param bool                                        $short   displays short format of time units
     * @param int                                         $parts   maximum number of parts to display (default value: 1: single unit)
     * @param int                                         $options human diff options
     *
     * @return string
     */
    public function until($other = null, $syntax = null, $short = false, $parts = 1, $options = null)
    {
        return $this->to($other, $syntax, $short, $parts, $options);
    }
    /**
     * Get the difference in a human readable format in the current locale from current
     * instance to now.
     *
     * @param int|array $syntax  if array passed, parameters will be extracted from it, the array may contains:
     *                           - 'syntax' entry (see below)
     *                           - 'short' entry (see below)
     *                           - 'parts' entry (see below)
     *                           - 'options' entry (see below)
     *                           - 'join' entry determines how to join multiple parts of the string
     *                           `  - if $join is a string, it's used as a joiner glue
     *                           `  - if $join is a callable/closure, it get the list of string and should return a string
     *                           `  - if $join is an array, the first item will be the default glue, and the second item
     *                           `    will be used instead of the glue for the last item
     *                           `  - if $join is true, it will be guessed from the locale ('list' translation file entry)
     *                           `  - if $join is missing, a space will be used as glue
     *                           if int passed, it add modifiers:
     *                           Possible values:
     *                           - CarbonInterface::DIFF_ABSOLUTE          no modifiers
     *                           - CarbonInterface::DIFF_RELATIVE_TO_NOW   add ago/from now modifier
     *                           - CarbonInterface::DIFF_RELATIVE_TO_OTHER add before/after modifier
     *                           Default value: CarbonInterface::DIFF_ABSOLUTE
     * @param bool      $short   displays short format of time units
     * @param int       $parts   maximum number of parts to display (default value: 1: single unit)
     * @param int       $options human diff options
     *
     * @return string
     */
    public function from_now($syntax = null, $short = false, $parts = 1, $options = null)
    {
        $other = null;
        if ($syntax instanceof DateTimeInterface) {
            [$other, $syntax, $short, $parts, $options] = array_pad(\func_get_args(), 5, null);
        }
        return $this->from($other, $syntax, $short, $parts, $options);
    }
    /**
     * Get the difference in a human readable format in the current locale from an other
     * instance given to now
     *
     * @param int|array $syntax  if array passed, parameters will be extracted from it, the array may contains:
     *                           - 'syntax' entry (see below)
     *                           - 'short' entry (see below)
     *                           - 'parts' entry (see below)
     *                           - 'options' entry (see below)
     *                           - 'join' entry determines how to join multiple parts of the string
     *                           `  - if $join is a string, it's used as a joiner glue
     *                           `  - if $join is a callable/closure, it get the list of string and should return a string
     *                           `  - if $join is an array, the first item will be the default glue, and the second item
     *                           `    will be used instead of the glue for the last item
     *                           `  - if $join is true, it will be guessed from the locale ('list' translation file entry)
     *                           `  - if $join is missing, a space will be used as glue
     *                           if int passed, it add modifiers:
     *                           Possible values:
     *                           - CarbonInterface::DIFF_ABSOLUTE          no modifiers
     *                           - CarbonInterface::DIFF_RELATIVE_TO_NOW   add ago/from now modifier
     *                           - CarbonInterface::DIFF_RELATIVE_TO_OTHER add before/after modifier
     *                           Default value: CarbonInterface::DIFF_ABSOLUTE
     * @param bool      $short   displays short format of time units
     * @param int       $parts   maximum number of parts to display (default value: 1: single part)
     * @param int       $options human diff options
     *
     * @return string
     */
    public function to_now($syntax = null, $short = false, $parts = 1, $options = null)
    {
        return $this->to(null, $syntax, $short, $parts, $options);
    }
    /**
     * Get the difference in a human readable format in the current locale from an other
     * instance given to now
     *
     * @param int|array $syntax  if array passed, parameters will be extracted from it, the array may contains:
     *                           - 'syntax' entry (see below)
     *                           - 'short' entry (see below)
     *                           - 'parts' entry (see below)
     *                           - 'options' entry (see below)
     *                           - 'join' entry determines how to join multiple parts of the string
     *                           `  - if $join is a string, it's used as a joiner glue
     *                           `  - if $join is a callable/closure, it get the list of string and should return a string
     *                           `  - if $join is an array, the first item will be the default glue, and the second item
     *                           `    will be used instead of the glue for the last item
     *                           `  - if $join is true, it will be guessed from the locale ('list' translation file entry)
     *                           `  - if $join is missing, a space will be used as glue
     *                           if int passed, it add modifiers:
     *                           Possible values:
     *                           - CarbonInterface::DIFF_ABSOLUTE          no modifiers
     *                           - CarbonInterface::DIFF_RELATIVE_TO_NOW   add ago/from now modifier
     *                           - CarbonInterface::DIFF_RELATIVE_TO_OTHER add before/after modifier
     *                           Default value: CarbonInterface::DIFF_ABSOLUTE
     * @param bool      $short   displays short format of time units
     * @param int       $parts   maximum number of parts to display (default value: 1: single part)
     * @param int       $options human diff options
     *
     * @return string
     */
    public function ago($syntax = null, $short = false, $parts = 1, $options = null)
    {
        $other = null;
        if ($syntax instanceof DateTimeInterface) {
            [$other, $syntax, $short, $parts, $options] = array_pad(\func_get_args(), 5, null);
        }
        return $this->from($other, $syntax, $short, $parts, $options);
    }
    /**
     * Get the difference in a human-readable format in the current locale from current instance to another
     * instance given (or now if null given).
     */
    public function timespan($other = null, $timezone = null): string
    {
        if (\is_string($other)) {
            $other = $this->transmit_factory(static fn() => static::parse($other, $timezone));
        }
        return $this->diff_for_humans($other, ['join' => ', ', 'syntax' => Carbon_Interface::DIFF_ABSOLUTE, 'parts' => INF]);
    }
    /**
     * Returns either day of week + time (e.g. "Last Friday at 3:30 PM") if reference time is within 7 days,
     * or a calendar date (e.g. "10/29/2017") otherwise.
     *
     * Language, date and time formats will change according to the current locale.
     *
     * @param Carbon|\DateTimeInterface|string|null $referenceTime
     *
     * @return string
     */
    public function calendar($reference_time = null, array $formats = [])
    {
        /** @var CarbonInterface $current */
        $current = $this->avoid_mutation()->start_of_day();
        /** @var CarbonInterface $other */
        $other = $this->resolve_carbon($reference_time)->avoid_mutation()->set_timezone($this->get_timezone())->start_of_day();
        $diff = $other->diff_in_days($current, false);
        $format = $diff <= -static::DAYS_PER_WEEK ? 'sameElse' : ($diff < -1 ? 'lastWeek' : ($diff < 0 ? 'lastDay' : ($diff < 1 ? 'sameDay' : ($diff < 2 ? 'nextDay' : ($diff < static::DAYS_PER_WEEK ? 'nextWeek' : 'sameElse')))));
        $format = array_merge($this->get_calendar_formats(), $formats)[$format];
        if ($format instanceof Closure) {
            $format = $format($current, $other) ?? '';
        }
        return $this->iso_format((string) $format);
    }
    private function get_interval_day_diff(DateInterval $interval): int
    {
        return (int) $interval->format('%r%a');
    }
}