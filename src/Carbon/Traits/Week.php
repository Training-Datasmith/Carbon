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

use Carbon\Carbon_Interval;
/**
 * Trait Week.
 *
 * week and ISO week number, year and count in year.
 *
 * Depends on the following properties:
 *
 * @property int $daysInYear
 * @property int $dayOfWeek
 * @property int $dayOfYear
 * @property int $year
 *
 * Depends on the following methods:
 *
 * @method static addWeeks(int $weeks = 1)
 * @method static copy()
 * @method static dayOfYear(int $dayOfYear)
 * @method string getTranslationMessage(string $key, ?string $locale = null, ?string $default = null, $translator = null)
 * @method static next(int|string $modifier = null)
 * @method static startOfWeek(int $day = null)
 * @method static subWeeks(int $weeks = 1)
 * @method static year(int $year = null)
 */
trait Week
{
    /**
     * Set/get the week number of year using given first day of week and first
     * day of year included in the first week. Or use ISO format if no settings
     * given.
     *
     * @param int|null $year      if null, act as a getter, if not null, set the year and return current instance.
     * @param int|null $dayOfWeek first date of week from 0 (Sunday) to 6 (Saturday)
     * @param int|null $dayOfYear first day of year included in the week #1
     *
     * @return int|static
     */
    public function iso_week_year($year = null, $day_of_week = null, $day_of_year = null)
    {
        return $this->week_year($year, $day_of_week ?? static::MONDAY, $day_of_year ?? static::THURSDAY);
    }
    /**
     * Set/get the week number of year using given first day of week and first
     * day of year included in the first week. Or use US format if no settings
     * given (Sunday / Jan 6).
     *
     * @param int|null $year      if null, act as a getter, if not null, set the year and return current instance.
     * @param int|null $dayOfWeek first date of week from 0 (Sunday) to 6 (Saturday)
     * @param int|null $dayOfYear first day of year included in the week #1
     *
     * @return int|static
     */
    public function week_year($year = null, $day_of_week = null, $day_of_year = null)
    {
        $day_of_week ??= $this->get_translation_message('first_day_of_week') ?? static::SUNDAY;
        $day_of_year ??= $this->get_translation_message('day_of_first_week_of_year') ?? 1;
        if ($year !== null) {
            $year = (int) round($year);
            if ($this->week_year(null, $day_of_week, $day_of_year) === $year) {
                return $this->avoid_mutation();
            }
            $week = $this->week(null, $day_of_week, $day_of_year);
            $day = $this->day_of_week;
            $date = $this->year($year);
            $date = match ($date->week_year(null, $day_of_week, $day_of_year) - $year) {
                Carbon_Interval::POSITIVE => $date->sub_weeks(static::WEEKS_PER_YEAR / 2),
                Carbon_Interval::NEGATIVE => $date->add_weeks(static::WEEKS_PER_YEAR / 2),
                default => $date,
            };
            $date = $date->add_weeks($week - $date->week(null, $day_of_week, $day_of_year))->start_of_week($day_of_week);
            if ($date->day_of_week === $day) {
                return $date;
            }
            return $date->next($day);
        }
        $year = $this->year;
        $day = $this->day_of_year;
        $date = $this->avoid_mutation()->day_of_year($day_of_year)->start_of_week($day_of_week);
        if ($date->year === $year && $day < $date->day_of_year) {
            return $year - 1;
        }
        $date = $this->avoid_mutation()->add_year()->day_of_year($day_of_year)->start_of_week($day_of_week);
        if ($date->year === $year && $day >= $date->day_of_year) {
            return $year + 1;
        }
        return $year;
    }
    /**
     * Get the number of weeks of the current week-year using given first day of week and first
     * day of year included in the first week. Or use ISO format if no settings
     * given.
     *
     * @param int|null $dayOfWeek first date of week from 0 (Sunday) to 6 (Saturday)
     * @param int|null $dayOfYear first day of year included in the week #1
     *
     * @return int
     */
    public function iso_weeks_in_year($day_of_week = null, $day_of_year = null)
    {
        return $this->weeks_in_year($day_of_week ?? static::MONDAY, $day_of_year ?? static::THURSDAY);
    }
    /**
     * Get the number of weeks of the current week-year using given first day of week and first
     * day of year included in the first week. Or use US format if no settings
     * given (Sunday / Jan 6).
     *
     * @param int|null $dayOfWeek first date of week from 0 (Sunday) to 6 (Saturday)
     * @param int|null $dayOfYear first day of year included in the week #1
     */
    public function weeks_in_year($day_of_week = null, $day_of_year = null): int
    {
        $day_of_week ??= $this->get_translation_message('first_day_of_week') ?? static::SUNDAY;
        $day_of_year ??= $this->get_translation_message('day_of_first_week_of_year') ?? 1;
        $year = $this->year;
        $start = $this->avoid_mutation()->day_of_year($day_of_year)->start_of_week($day_of_week);
        $start_day = $start->day_of_year;
        if ($start->year !== $year) {
            $start_day -= $start->days_in_year;
        }
        $end = $this->avoid_mutation()->add_year()->day_of_year($day_of_year)->start_of_week($day_of_week);
        $end_day = $end->day_of_year;
        if ($end->year !== $year) {
            $end_day += $this->days_in_year;
        }
        return (int) round(($end_day - $start_day) / static::DAYS_PER_WEEK);
    }
    /**
     * Get/set the week number using given first day of week and first
     * day of year included in the first week. Or use US format if no settings
     * given (Sunday / Jan 6).
     *
     * @param int|null $week
     * @param int|null $dayOfWeek
     * @param int|null $dayOfYear
     *
     * @return int|static
     */
    public function week($week = null, $day_of_week = null, $day_of_year = null)
    {
        $date = $this;
        $day_of_week ??= $this->get_translation_message('first_day_of_week') ?? 0;
        $day_of_year ??= $this->get_translation_message('day_of_first_week_of_year') ?? 1;
        if ($week !== null) {
            return $date->add_weeks(round($week) - $this->week(null, $day_of_week, $day_of_year));
        }
        $start = $date->avoid_mutation()->shift_timezone('UTC')->day_of_year($day_of_year)->start_of_week($day_of_week);
        $end = $date->avoid_mutation()->shift_timezone('UTC')->start_of_week($day_of_week);
        if ($start > $end) {
            $start = $start->sub_weeks(static::WEEKS_PER_YEAR / 2)->day_of_year($day_of_year)->start_of_week($day_of_week);
        }
        $week = (int) ($start->diff_in_days($end) / static::DAYS_PER_WEEK + 1);
        return $week > $end->weeks_in_year($day_of_week, $day_of_year) ? 1 : $week;
    }
    /**
     * Get/set the week number using given first day of week and first
     * day of year included in the first week. Or use ISO format if no settings
     * given.
     *
     * @param int|null $week
     * @param int|null $dayOfWeek
     * @param int|null $dayOfYear
     *
     * @return int|static
     */
    public function iso_week($week = null, $day_of_week = null, $day_of_year = null)
    {
        return $this->week($week, $day_of_week ?? static::MONDAY, $day_of_year ?? static::THURSDAY);
    }
}