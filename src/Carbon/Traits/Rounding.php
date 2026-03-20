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

use Carbon\Carbon_Interface;
use Carbon\Exceptions\Unknown_Unit_Exception;
use Carbon\Week_Day;
use DateInterval;
/**
 * Trait Rounding.
 *
 * Round, ceil, floor units.
 *
 * Depends on the following methods:
 *
 * @method static copy()
 * @method static startOfWeek(int $weekStartsAt = null)
 */
trait Rounding
{
    use Interval_Rounding;
    /**
     * Round the current instance at the given unit with given precision if specified and the given function.
     */
    public function round_unit(string $unit, DateInterval|string|float|int $precision = 1, callable|string $function = 'round'): static
    {
        $meta_units = [
            // @call roundUnit
            'millennium' => [static::YEARS_PER_MILLENNIUM, 'year'],
            // @call roundUnit
            'century' => [static::YEARS_PER_CENTURY, 'year'],
            // @call roundUnit
            'decade' => [static::YEARS_PER_DECADE, 'year'],
            // @call roundUnit
            'quarter' => [static::MONTHS_PER_QUARTER, 'month'],
            // @call roundUnit
            'millisecond' => [1000, 'microsecond'],
        ];
        $normalized_unit = static::singular_unit($unit);
        $ranges = array_merge(static::get_ranges_by_unit($this->days_in_month), [
            // @call roundUnit
            'microsecond' => [0, 999999],
        ]);
        $factor = 1;
        if ($normalized_unit === 'week') {
            $normalized_unit = 'day';
            $precision *= static::DAYS_PER_WEEK;
        }
        if (isset($meta_units[$normalized_unit])) {
            [$factor, $normalized_unit] = $meta_units[$normalized_unit];
        }
        $precision *= $factor;
        if (!isset($ranges[$normalized_unit])) {
            throw new Unknown_Unit_Exception($unit);
        }
        $found = false;
        $fraction = 0;
        $arguments = null;
        $initial_value = null;
        $factor = $this->year < 0 ? -1 : 1;
        $changes = [];
        $minimum_inc = null;
        foreach ($ranges as $unit => [$minimum, $maximum]) {
            if ($normalized_unit === $unit) {
                $arguments = [$this->{$unit}, $minimum];
                $initial_value = $this->{$unit};
                $fraction = $precision - floor($precision);
                $found = true;
                continue;
            }
            if ($found) {
                $delta = $maximum + 1 - $minimum;
                $factor /= $delta;
                $fraction *= $delta;
                $inc = ($this->{$unit} - $minimum) * $factor;
                if ($inc !== 0.0) {
                    $minimum_inc ??= $arguments[0] / 2 ** 52;
                    // If value is still the same when adding a non-zero increment/decrement,
                    // it means precision got lost in the addition
                    if (abs($inc) < $minimum_inc) {
                        $inc = $minimum_inc * ($inc < 0 ? -1 : 1);
                    }
                    // If greater than $precision, assume precision loss caused an overflow
                    if ($function !== 'floor' || abs($arguments[0] + $inc - $initial_value) >= $precision) {
                        $arguments[0] += $inc;
                    }
                }
                $changes[$unit] = round($minimum + ($fraction ? $fraction * $function(($this->{$unit} - $minimum) / $fraction) : 0));
                // Cannot use modulo as it lose double precision
                while ($changes[$unit] >= $delta) {
                    $changes[$unit] -= $delta;
                }
                $fraction -= floor($fraction);
            }
        }
        [$value, $minimum] = $arguments;
        $normalized_value = floor($function(($value - $minimum) / $precision) * $precision + $minimum);
        /** @var CarbonInterface $result */
        $result = $this;
        foreach ($changes as $unit => $value) {
            $result = $result->{$unit}($value);
        }
        return $result->{$normalized_unit}($normalized_value);
    }
    /**
     * Truncate the current instance at the given unit with given precision if specified.
     */
    public function floor_unit(string $unit, DateInterval|string|float|int $precision = 1): static
    {
        return $this->round_unit($unit, $precision, 'floor');
    }
    /**
     * Ceil the current instance at the given unit with given precision if specified.
     */
    public function ceil_unit(string $unit, DateInterval|string|float|int $precision = 1): static
    {
        return $this->round_unit($unit, $precision, 'ceil');
    }
    /**
     * Round the current instance second with given precision if specified.
     */
    public function round(DateInterval|string|float|int $precision = 1, callable|string $function = 'round'): static
    {
        return $this->round_with($precision, $function);
    }
    /**
     * Round the current instance second with given precision if specified.
     */
    public function floor(DateInterval|string|float|int $precision = 1): static
    {
        return $this->round($precision, 'floor');
    }
    /**
     * Ceil the current instance second with given precision if specified.
     */
    public function ceil(DateInterval|string|float|int $precision = 1): static
    {
        return $this->round($precision, 'ceil');
    }
    /**
     * Round the current instance week.
     *
     * @param WeekDay|int|null $weekStartsAt optional start allow you to specify the day of week to use to start the week
     */
    public function round_week(Week_Day|int|null $week_starts_at = null): static
    {
        return $this->closest($this->avoid_mutation()->floor_week($week_starts_at), $this->avoid_mutation()->ceil_week($week_starts_at));
    }
    /**
     * Truncate the current instance week.
     *
     * @param WeekDay|int|null $weekStartsAt optional start allow you to specify the day of week to use to start the week
     */
    public function floor_week(Week_Day|int|null $week_starts_at = null): static
    {
        return $this->start_of_week($week_starts_at);
    }
    /**
     * Ceil the current instance week.
     *
     * @param WeekDay|int|null $weekStartsAt optional start allow you to specify the day of week to use to start the week
     */
    public function ceil_week(Week_Day|int|null $week_starts_at = null): static
    {
        if ($this->is_mutable()) {
            $start_of_week = $this->avoid_mutation()->start_of_week($week_starts_at);
            return $start_of_week != $this ? $this->start_of_week($week_starts_at)->add_week() : $this;
        }
        $start_of_week = $this->start_of_week($week_starts_at);
        return $start_of_week != $this ? $start_of_week->add_week() : $this->avoid_mutation();
    }
}