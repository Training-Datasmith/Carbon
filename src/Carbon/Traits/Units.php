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

use Carbon\Carbon_Converter_Interface;
use Carbon\Carbon_Interface;
use Carbon\Carbon_Interval;
use Carbon\Exceptions\Invalid_Format_Exception;
use Carbon\Exceptions\Invalid_Interval_Exception;
use Carbon\Exceptions\Unit_Exception;
use Carbon\Exceptions\Unsupported_Unit_Exception;
use Carbon\Unit;
use Closure;
use DateInterval;
use Date_Malformed_String_Exception;
use Return_Type_Will_Change;
/**
 * Trait Units.
 *
 * Add, subtract and set units.
 */
trait Units
{
    /**
     * @deprecated Prefer to use add addUTCUnit() which more accurately defines what it's doing.
     *
     * Add seconds to the instance using timestamp. Positive $value travels
     * forward while negative $value travels into the past.
     *
     * @param int|float|null $value
     *
     */
    public function add_real_unit(string $unit, $value = 1): static
    {
        return $this->add_utc_unit($unit, $value);
    }
    /**
     * Add seconds to the instance using timestamp. Positive $value travels
     * forward while negative $value travels into the past.
     *
     * @param int|float|null $value
     *
     */
    public function add_utc_unit(string $unit, $value = 1): static
    {
        $value ??= 0;
        switch ($unit) {
            // @call addUTCUnit
            case 'micro':
            // @call addUTCUnit
            case 'microsecond':
                /* @var CarbonInterface $this */
                $diff = $this->microsecond + $value;
                $time = $this->get_timestamp();
                $seconds = (int) floor($diff / static::MICROSECONDS_PER_SECOND);
                $time += $seconds;
                $diff -= $seconds * static::MICROSECONDS_PER_SECOND;
                $microtime = str_pad((string) $diff, 6, '0', STR_PAD_LEFT);
                $timezone = $this->tz;
                return $this->tz('UTC')->modify("@{$time}.{$microtime}")->set_timezone($timezone);
            // @call addUTCUnit
            case 'milli':
            // @call addUTCUnit
            case 'millisecond':
                return $this->add_utc_unit('microsecond', $value * static::MICROSECONDS_PER_MILLISECOND);
            // @call addUTCUnit
            case 'second':
                break;
            // @call addUTCUnit
            case 'minute':
                $value *= static::SECONDS_PER_MINUTE;
                break;
            // @call addUTCUnit
            case 'hour':
                $value *= static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;
                break;
            // @call addUTCUnit
            case 'day':
                $value *= static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;
                break;
            // @call addUTCUnit
            case 'week':
                $value *= static::DAYS_PER_WEEK * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;
                break;
            // @call addUTCUnit
            case 'month':
                $value *= 30 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;
                break;
            // @call addUTCUnit
            case 'quarter':
                $value *= static::MONTHS_PER_QUARTER * 30 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;
                break;
            // @call addUTCUnit
            case 'year':
                $value *= 365 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;
                break;
            // @call addUTCUnit
            case 'decade':
                $value *= static::YEARS_PER_DECADE * 365 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;
                break;
            // @call addUTCUnit
            case 'century':
                $value *= static::YEARS_PER_CENTURY * 365 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;
                break;
            // @call addUTCUnit
            case 'millennium':
                $value *= static::YEARS_PER_MILLENNIUM * 365 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;
                break;
            default:
                if ($this->is_local_strict_mode_enabled()) {
                    throw new Unit_Exception("Invalid unit for real timestamp add/sub: '{$unit}'");
                }
                return $this;
        }
        $seconds = (int) $value;
        $microseconds = (int) round((abs($value) - abs($seconds)) * ($value < 0 ? -1 : 1) * static::MICROSECONDS_PER_SECOND);
        $date = $this->set_timestamp($this->get_timestamp() + $seconds);
        return $microseconds ? $date->add_utc_unit('microsecond', $microseconds) : $date;
    }
    /**
     * @deprecated Prefer to use add subUTCUnit() which more accurately defines what it's doing.
     *
     * Subtract seconds to the instance using timestamp. Positive $value travels
     * into the past while negative $value travels forward.
     *
     * @param string $unit
     * @param int    $value
     */
    public function sub_real_unit($unit, $value = 1): static
    {
        return $this->add_utc_unit($unit, -$value);
    }
    /**
     * Subtract seconds to the instance using timestamp. Positive $value travels
     * into the past while negative $value travels forward.
     *
     * @param string $unit
     * @param int    $value
     */
    public function sub_utc_unit($unit, $value = 1): static
    {
        return $this->add_utc_unit($unit, -$value);
    }
    /**
     * Returns true if a property can be changed via setter.
     *
     * @param string $unit
     */
    public static function is_modifiable_unit($unit): bool
    {
        static $modifiable_units = [
            // @call addUnit
            'millennium',
            // @call addUnit
            'century',
            // @call addUnit
            'decade',
            // @call addUnit
            'quarter',
            // @call addUnit
            'week',
            // @call addUnit
            'weekday',
        ];
        return \in_array($unit, $modifiable_units, true) || \in_array($unit, static::$units, true);
    }
    /**
     * Call native PHP DateTime/DateTimeImmutable add() method.
     *
     *
     */
    public function raw_add(DateInterval $interval): static
    {
        return parent::add($interval);
    }
    /**
     * Add given units or interval to the current instance.
     *
     * @example $date->add('hour', 3)
     * @example $date->add(15, 'days')
     * @example $date->add(CarbonInterval::days(4))
     *
     * @param Unit|int|string|DateInterval|Closure|CarbonConverterInterface $unit
     * @param Unit|int|float|string                                         $value
     *
     */
    #[Return_Type_Will_Change]
    public function add($unit, $value = 1, ?bool $overflow = null): static
    {
        $unit = Unit::to_name_if_unit($unit);
        $value = Unit::to_name_if_unit($value);
        if (\is_string($unit) && \func_num_args() === 1) {
            $unit = Carbon_Interval::make($unit, [], true);
        }
        if ($unit instanceof Carbon_Converter_Interface) {
            $unit = $unit->convert_date(...);
        }
        if ($unit instanceof Closure) {
            $result = $this->resolve_carbon($unit($this, false));
            if ($this !== $result && $this->is_mutable()) {
                return $this->modify($result->raw_format('Y-m-d H:i:s.u e O'));
            }
            return $result;
        }
        if ($unit instanceof DateInterval) {
            return parent::add($unit);
        }
        if (is_numeric($unit)) {
            [$value, $unit] = [$unit, $value];
        }
        return $this->add_unit((string) $unit, $value, $overflow);
    }
    /**
     * Add given units to the current instance.
     */
    public function add_unit(Unit|string $unit, $value = 1, ?bool $overflow = null): static
    {
        $unit = Unit::to_name($unit);
        $original_args = \func_get_args();
        $date = $this;
        if (!is_numeric($value) || !(float) $value) {
            return $date->is_mutable() ? $date : $date->copy();
        }
        $unit = self::singular_unit($unit);
        $meta_units = ['millennium' => [static::YEARS_PER_MILLENNIUM, 'year'], 'century' => [static::YEARS_PER_CENTURY, 'year'], 'decade' => [static::YEARS_PER_DECADE, 'year'], 'quarter' => [static::MONTHS_PER_QUARTER, 'month']];
        if (isset($meta_units[$unit])) {
            [$factor, $unit] = $meta_units[$unit];
            $value *= $factor;
        }
        if ($unit === 'weekday') {
            $weekend_days = $this->transmit_factory(static fn() => static::get_weekend_days());
            if ($weekend_days !== [static::SATURDAY, static::SUNDAY]) {
                $absolute_value = abs($value);
                $sign = $value / max(1, $absolute_value);
                $week_days_count = static::DAYS_PER_WEEK - min(static::DAYS_PER_WEEK - 1, \count(array_unique($weekend_days)));
                $weeks = floor($absolute_value / $week_days_count);
                for ($diff = $absolute_value % $week_days_count; $diff; $diff--) {
                    /** @var static $date */
                    $date = $date->add_days($sign);
                    while (\in_array($date->day_of_week, $weekend_days, true)) {
                        $date = $date->add_days($sign);
                    }
                }
                $value = $weeks * $sign;
                $unit = 'week';
            }
            $time_string = $date->to_time_string();
        } elseif ($can_overflow = \in_array($unit, ['month', 'year']) && ($overflow === false || $overflow === null && ($uc_unit = ucfirst($unit) . 's') && !($this->{'local' . $uc_unit . 'Overflow'} ?? static::{'shouldOverflow' . $uc_unit}()))) {
            $day = $date->day;
        }
        if ($unit === 'milli' || $unit === 'millisecond') {
            $unit = 'microsecond';
            $value *= static::MICROSECONDS_PER_MILLISECOND;
        }
        $previous_exception = null;
        try {
            $date = self::raw_add_unit($date, $unit, $value);
            if (isset($time_string)) {
                $date = $date?->set_time_from_time_string($time_string);
            } elseif (isset($can_overflow, $day) && $can_overflow && $day !== $date?->day) {
                $date = $date?->modify('last day of previous month');
            }
        } catch (Date_Malformed_String_Exception|Invalid_Format_Exception|Unsupported_Unit_Exception $exception) {
            $date = null;
            $previous_exception = $exception;
        }
        return $date ?? throw new Unit_Exception('Unable to add unit ' . var_export($original_args, true), previous: $previous_exception);
    }
    /**
     * Subtract given units to the current instance.
     */
    public function sub_unit(Unit|string $unit, $value = 1, ?bool $overflow = null): static
    {
        return $this->add_unit($unit, -$value, $overflow);
    }
    /**
     * Call native PHP DateTime/DateTimeImmutable sub() method.
     */
    public function raw_sub(DateInterval $interval): static
    {
        return parent::sub($interval);
    }
    /**
     * Subtract given units or interval to the current instance.
     *
     * @example $date->sub('hour', 3)
     * @example $date->sub(15, 'days')
     * @example $date->sub(CarbonInterval::days(4))
     *
     * @param Unit|int|string|DateInterval|Closure|CarbonConverterInterface $unit
     * @param Unit|int|float|string                                         $value
     *
     */
    #[Return_Type_Will_Change]
    public function sub($unit, $value = 1, ?bool $overflow = null): static
    {
        $unit = Unit::to_name_if_unit($unit);
        $value = Unit::to_name_if_unit($value);
        if (\is_string($unit) && \func_num_args() === 1) {
            $unit = Carbon_Interval::make($unit, [], true);
        }
        if ($unit instanceof Carbon_Converter_Interface) {
            $unit = $unit->convert_date(...);
        }
        if ($unit instanceof Closure) {
            $result = $this->resolve_carbon($unit($this, true));
            if ($this !== $result && $this->is_mutable()) {
                return $this->modify($result->raw_format('Y-m-d H:i:s.u e O'));
            }
            return $result;
        }
        if ($unit instanceof DateInterval) {
            return parent::sub($unit);
        }
        if (is_numeric($unit)) {
            [$value, $unit] = [$unit, $value];
        }
        return $this->add_unit((string) $unit, -(float) $value, $overflow);
    }
    /**
     * Subtract given units or interval to the current instance.
     *
     * @see sub()
     *
     * @param Unit|int|string|DateInterval $unit
     * @param Unit|int|float|string        $value
     *
     */
    public function subtract($unit, $value = 1, ?bool $overflow = null): static
    {
        if (\is_string($unit) && \func_num_args() === 1) {
            $unit = Carbon_Interval::make($unit, [], true);
        }
        return $this->sub($unit, $value, $overflow);
    }
    private static function raw_add_unit(self $date, string $unit, int|float $value): ?static
    {
        try {
            return $date->raw_add(Carbon_Interval::from_string(abs($value) . " {$unit}")->invert($value < 0));
        } catch (Invalid_Interval_Exception $exception) {
            try {
                return $date->modify("{$value} {$unit}");
            } catch (Invalid_Format_Exception) {
                throw new Unsupported_Unit_Exception($unit, previous: $exception);
            }
        }
    }
}