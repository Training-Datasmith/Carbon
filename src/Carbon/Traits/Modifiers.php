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
use Carbon\Exceptions\Invalid_Format_Exception;
use Return_Type_Will_Change;
/**
 * Trait Modifiers.
 *
 * Returns dates relative to current date using modifier short-hand.
 */
trait Modifiers
{
    /**
     * Midday/noon hour.
     *
     * @var int
     */
    protected static $mid_day_at = 12;
    /**
     * get midday/noon hour
     *
     * @return int
     */
    public static function get_mid_day_at()
    {
        return static::$mid_day_at;
    }
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather consider mid-day is always 12pm, then if you need to test if it's an other
     *             hour, test it explicitly:
     *                 $date->format('G') == 13
     *             or to set explicitly to a given hour:
     *                 $date->setTime(13, 0, 0, 0)
     *
     * Set midday/noon hour
     *
     * @param int $hour midday hour
     */
    public static function set_mid_day_at($hour): void
    {
        static::$mid_day_at = $hour;
    }
    /**
     * Modify to midday, default to self::$midDayAt
     *
     * @return static
     */
    public function mid_day()
    {
        return $this->set_time(static::$mid_day_at, 0, 0, 0);
    }
    /**
     * Modify to the next occurrence of a given modifier such as a day of
     * the week. If no modifier is provided, modify to the next occurrence
     * of the current day of the week. Use the supplied constants
     * to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param string|int|null $modifier
     *
     * @return static
     */
    public function next($modifier = null)
    {
        if ($modifier === null) {
            $modifier = $this->day_of_week;
        }
        return $this->change('next ' . (\is_string($modifier) ? $modifier : static::$days[$modifier]));
    }
    /**
     * Go forward or backward to the next week- or weekend-day.
     *
     * @param bool $weekday
     * @param bool $forward
     *
     * @return static
     */
    private function next_or_previous_day($weekday = true, $forward = true)
    {
        /** @var CarbonInterface $date */
        $date = $this;
        $step = $forward ? 1 : -1;
        do {
            $date = $date->add_days($step);
        } while ($weekday ? $date->is_weekend() : $date->is_weekday());
        return $date;
    }
    /**
     * Go forward to the next weekday.
     *
     * @return static
     */
    public function next_weekday()
    {
        return $this->next_or_previous_day();
    }
    /**
     * Go backward to the previous weekday.
     *
     * @return static
     */
    public function previous_weekday()
    {
        return $this->next_or_previous_day(true, false);
    }
    /**
     * Go forward to the next weekend day.
     *
     * @return static
     */
    public function next_weekend_day()
    {
        return $this->next_or_previous_day(false);
    }
    /**
     * Go backward to the previous weekend day.
     *
     * @return static
     */
    public function previous_weekend_day()
    {
        return $this->next_or_previous_day(false, false);
    }
    /**
     * Modify to the previous occurrence of a given modifier such as a day of
     * the week. If no dayOfWeek is provided, modify to the previous occurrence
     * of the current day of the week. Use the supplied constants
     * to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param string|int|null $modifier
     *
     * @return static
     */
    public function previous($modifier = null)
    {
        if ($modifier === null) {
            $modifier = $this->day_of_week;
        }
        return $this->change('last ' . (\is_string($modifier) ? $modifier : static::$days[$modifier]));
    }
    /**
     * Modify to the first occurrence of a given day of the week
     * in the current month. If no dayOfWeek is provided, modify to the
     * first day of the current month.  Use the supplied constants
     * to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param int|null $dayOfWeek
     *
     * @return static
     */
    public function first_of_month($day_of_week = null)
    {
        $date = $this->start_of_day();
        if ($day_of_week === null) {
            return $date->day(1);
        }
        return $date->modify('first ' . static::$days[$day_of_week] . ' of ' . $date->raw_format('F') . ' ' . $date->year);
    }
    /**
     * Modify to the last occurrence of a given day of the week
     * in the current month. If no dayOfWeek is provided, modify to the
     * last day of the current month.  Use the supplied constants
     * to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param int|null $dayOfWeek
     *
     * @return static
     */
    public function last_of_month($day_of_week = null)
    {
        $date = $this->start_of_day();
        if ($day_of_week === null) {
            return $date->day($date->days_in_month);
        }
        return $date->modify('last ' . static::$days[$day_of_week] . ' of ' . $date->raw_format('F') . ' ' . $date->year);
    }
    /**
     * Modify to the given occurrence of a given day of the week
     * in the current month. If the calculated occurrence is outside the scope
     * of the current month, then return false and no modifications are made.
     * Use the supplied constants to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param int $nth
     * @param int $dayOfWeek
     *
     * @return mixed
     */
    public function nth_of_month($nth, $day_of_week)
    {
        $date = $this->avoid_mutation()->first_of_month();
        $check = $date->raw_format('Y-m');
        $date = $date->modify('+' . $nth . ' ' . static::$days[$day_of_week]);
        return $date->raw_format('Y-m') === $check ? $this->modify((string) $date) : false;
    }
    /**
     * Modify to the first occurrence of a given day of the week
     * in the current quarter. If no dayOfWeek is provided, modify to the
     * first day of the current quarter.  Use the supplied constants
     * to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param int|null $dayOfWeek day of the week default null
     *
     * @return static
     */
    public function first_of_quarter($day_of_week = null)
    {
        return $this->set_date($this->year, $this->quarter * static::MONTHS_PER_QUARTER - 2, 1)->first_of_month($day_of_week);
    }
    /**
     * Modify to the last occurrence of a given day of the week
     * in the current quarter. If no dayOfWeek is provided, modify to the
     * last day of the current quarter.  Use the supplied constants
     * to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param int|null $dayOfWeek day of the week default null
     *
     * @return static
     */
    public function last_of_quarter($day_of_week = null)
    {
        return $this->set_date($this->year, $this->quarter * static::MONTHS_PER_QUARTER, 1)->last_of_month($day_of_week);
    }
    /**
     * Modify to the given occurrence of a given day of the week
     * in the current quarter. If the calculated occurrence is outside the scope
     * of the current quarter, then return false and no modifications are made.
     * Use the supplied constants to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param int $nth
     * @param int $dayOfWeek
     *
     * @return mixed
     */
    public function nth_of_quarter($nth, $day_of_week)
    {
        $date = $this->avoid_mutation()->day(1)->month($this->quarter * static::MONTHS_PER_QUARTER);
        $last_month = $date->month;
        $year = $date->year;
        $date = $date->first_of_quarter()->modify('+' . $nth . ' ' . static::$days[$day_of_week]);
        return $last_month < $date->month || $year !== $date->year ? false : $this->modify((string) $date);
    }
    /**
     * Modify to the first occurrence of a given day of the week
     * in the current year. If no dayOfWeek is provided, modify to the
     * first day of the current year.  Use the supplied constants
     * to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param int|null $dayOfWeek day of the week default null
     *
     * @return static
     */
    public function first_of_year($day_of_week = null)
    {
        return $this->month(1)->first_of_month($day_of_week);
    }
    /**
     * Modify to the last occurrence of a given day of the week
     * in the current year. If no dayOfWeek is provided, modify to the
     * last day of the current year.  Use the supplied constants
     * to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param int|null $dayOfWeek day of the week default null
     *
     * @return static
     */
    public function last_of_year($day_of_week = null)
    {
        return $this->month(static::MONTHS_PER_YEAR)->last_of_month($day_of_week);
    }
    /**
     * Modify to the given occurrence of a given day of the week
     * in the current year. If the calculated occurrence is outside the scope
     * of the current year, then return false and no modifications are made.
     * Use the supplied constants to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * @param int $nth
     * @param int $dayOfWeek
     *
     * @return mixed
     */
    public function nth_of_year($nth, $day_of_week)
    {
        $date = $this->avoid_mutation()->first_of_year()->modify('+' . $nth . ' ' . static::$days[$day_of_week]);
        return $this->year === $date->year ? $this->modify((string) $date) : false;
    }
    /**
     * Modify the current instance to the average of a given instance (default now) and the current instance
     * (second-precision).
     *
     * @param \Carbon\Carbon|\DateTimeInterface|null $date
     *
     * @return static
     */
    public function average($date = null)
    {
        return $this->add_real_microseconds((int) ($this->diff_in_microseconds($this->resolve_carbon($date), false) / 2));
    }
    /**
     * Get the closest date from the instance (second-precision).
     *
     * @param \Carbon\Carbon|\DateTimeInterface|mixed $date1
     * @param \Carbon\Carbon|\DateTimeInterface|mixed $date2
     *
     * @return static
     */
    public function closest($date1, $date2)
    {
        return $this->diff_in_microseconds($date1, true) < $this->diff_in_microseconds($date2, true) ? $date1 : $date2;
    }
    /**
     * Get the farthest date from the instance (second-precision).
     *
     * @param \Carbon\Carbon|\DateTimeInterface|mixed $date1
     * @param \Carbon\Carbon|\DateTimeInterface|mixed $date2
     *
     * @return static
     */
    public function farthest($date1, $date2)
    {
        return $this->diff_in_microseconds($date1, true) > $this->diff_in_microseconds($date2, true) ? $date1 : $date2;
    }
    /**
     * Get the minimum instance between a given instance (default now) and the current instance.
     *
     * @param \Carbon\Carbon|\DateTimeInterface|mixed $date
     *
     * @return static
     */
    public function min($date = null)
    {
        $date = $this->resolve_carbon($date);
        return $this->lt($date) ? $this : $date;
    }
    /**
     * Get the minimum instance between a given instance (default now) and the current instance.
     *
     * @param \Carbon\Carbon|\DateTimeInterface|mixed $date
     *
     * @see min()
     *
     * @return static
     */
    public function minimum($date = null)
    {
        return $this->min($date);
    }
    /**
     * Get the maximum instance between a given instance (default now) and the current instance.
     *
     * @param \Carbon\Carbon|\DateTimeInterface|mixed $date
     *
     * @return static
     */
    public function max($date = null)
    {
        $date = $this->resolve_carbon($date);
        return $this->gt($date) ? $this : $date;
    }
    /**
     * Get the maximum instance between a given instance (default now) and the current instance.
     *
     * @param \Carbon\Carbon|\DateTimeInterface|mixed $date
     *
     * @see max()
     *
     * @return static
     */
    public function maximum($date = null)
    {
        return $this->max($date);
    }
    /**
     * Calls \DateTime::modify if mutable or \DateTimeImmutable::modify else.
     *
     * @see https://php.net/manual/en/datetime.modify.php
     *
     * @return static
     */
    #[Return_Type_Will_Change]
    public function modify($modify)
    {
        return parent::modify((string) $modify) ?: throw new Invalid_Format_Exception('Could not modify with: ' . var_export($modify, true));
    }
    /**
     * Similar to native modify() method of DateTime but can handle more grammars.
     *
     * @example
     * ```
     * echo Carbon::now()->change('next 2pm');
     * ```
     *
     * @link https://php.net/manual/en/datetime.modify.php
     *
     * @param string $modifier
     *
     * @return static
     */
    public function change($modifier)
    {
        return $this->modify(preg_replace_callback('/^(next|previous|last)\s+(\d{1,2}(h|am|pm|:\d{1,2}(:\d{1,2})?))$/i', function (array $match) {
            $match[2] = str_replace('h', ':00', $match[2]);
            $test = $this->avoid_mutation()->modify($match[2]);
            $method = $match[1] === 'next' ? 'lt' : 'gt';
            $match[1] = $test->{$method}($this) ? $match[1] . ' day' : 'today';
            return $match[1] . ' ' . $match[2];
        }, strtr(trim($modifier), [' at ' => ' ', 'just now' => 'now', 'after tomorrow' => 'tomorrow +1 day', 'before yesterday' => 'yesterday -1 day'])));
    }
}