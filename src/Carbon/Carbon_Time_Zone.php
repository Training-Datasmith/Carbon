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
namespace Carbon;

use Carbon\Exceptions\Invalid_Cast_Exception;
use Carbon\Exceptions\Invalid_Time_Zone_Exception;
use Carbon\Traits\Local_Factory;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Throwable;
class Carbon_Time_Zone extends DateTimeZone
{
    use Local_Factory;
    public const MAXIMUM_TIMEZONE_OFFSET = 99;
    public function __construct(string|int|float $timezone)
    {
        $this->init_local_factory();
        parent::__construct(static::get_date_time_zone_name_from_mixed($timezone));
    }
    protected static function parse_numeric_timezone(string|int|float $timezone): string
    {
        if (abs((float) $timezone) > static::MAXIMUM_TIMEZONE_OFFSET) {
            throw new Invalid_Time_Zone_Exception('Absolute timezone offset cannot be greater than ' . static::MAXIMUM_TIMEZONE_OFFSET . '.');
        }
        return ($timezone >= 0 ? '+' : '') . ltrim((string) $timezone, '+') . ':00';
    }
    protected static function get_date_time_zone_name_from_mixed(string|int|float $timezone): string
    {
        if (\is_string($timezone)) {
            $timezone = preg_replace('/^\s*([+-]\d+)(\d{2})\s*$/', '$1:$2', $timezone);
        }
        if (is_numeric($timezone)) {
            return static::parse_numeric_timezone($timezone);
        }
        return $timezone;
    }
    /**
     * Cast the current instance into the given class.
     *
     * @param class-string<DateTimeZone> $className The $className::instance() method will be called to cast the current object.
     *
     * @return DateTimeZone|mixed
     */
    public function cast(string $class_name): mixed
    {
        if (!method_exists($class_name, 'instance')) {
            if (is_a($class_name, DateTimeZone::class, true)) {
                return new $class_name($this->get_name());
            }
            throw new Invalid_Cast_Exception("{$class_name} has not the instance() method needed to cast the date.");
        }
        return $class_name::instance($this);
    }
    /**
     * Create a CarbonTimeZone from mixed input.
     *
     * @param DateTimeZone|string|int|false|null $object     original value to get CarbonTimeZone from it.
     * @param DateTimeZone|string|int|false|null $objectDump dump of the object for error messages.
     *
     * @throws InvalidTimeZoneException
     *
     * @return static|null
     */
    public static function instance(DateTimeZone|string|int|false|null $object, DateTimeZone|string|int|false|null $object_dump = null): ?self
    {
        $timezone = $object;
        if ($timezone instanceof static) {
            return $timezone;
        }
        if ($timezone === null || $timezone === false) {
            return null;
        }
        try {
            if (!$timezone instanceof DateTimeZone) {
                $name = static::get_date_time_zone_name_from_mixed($object);
                $timezone = new static($name);
            }
            return $timezone instanceof static ? $timezone : new static($timezone->get_name());
        } catch (Exception $exception) {
            throw new Invalid_Time_Zone_Exception('Unknown or bad timezone (' . ($object_dump ?: $object) . ')', previous: $exception);
        }
    }
    /**
     * Returns abbreviated name of the current timezone according to DST setting.
     *
     * @param bool $dst
     *
     * @return string
     */
    public function get_abbreviated_name(bool $dst = false): string
    {
        $name = $this->get_name();
        $date = new DateTimeImmutable($dst ? 'July 1' : 'January 1', $this);
        $timezone = $date->format('T');
        $abbreviations = $this->list_abbreviations();
        $matching_zones = array_merge($abbreviations[$timezone] ?? [], $abbreviations[strtolower($timezone)] ?? []);
        if ($matching_zones !== []) {
            foreach ($matching_zones as $zone) {
                if ($zone['timezone_id'] === $name && $zone['dst'] == $dst) {
                    return $timezone;
                }
            }
        }
        foreach ($abbreviations as $abbreviation => $zones) {
            foreach ($zones as $zone) {
                if ($zone['timezone_id'] === $name && $zone['dst'] == $dst) {
                    return strtoupper($abbreviation);
                }
            }
        }
        return 'unknown';
    }
    /**
     * @alias getAbbreviatedName
     *
     * Returns abbreviated name of the current timezone according to DST setting.
     *
     * @param bool $dst
     *
     * @return string
     */
    public function get_abbr(bool $dst = false): string
    {
        return $this->get_abbreviated_name($dst);
    }
    /**
     * Get the offset as string "sHH:MM" (such as "+00:00" or "-12:30").
     */
    public function to_offset_name(?DateTimeInterface $date = null): string
    {
        return static::get_offset_name_from_minute_offset($this->get_offset($this->resolve_carbon($date)) / 60);
    }
    /**
     * Returns a new CarbonTimeZone object using the offset string instead of region string.
     */
    public function to_offset_time_zone(?DateTimeInterface $date = null): static
    {
        return new static($this->to_offset_name($date));
    }
    /**
     * Returns the first region string (such as "America/Toronto") that matches the current timezone or
     * false if no match is found.
     *
     * @see timezone_name_from_abbr native PHP function.
     */
    public function to_region_name(?DateTimeInterface $date = null, int $is_dst = 1): ?string
    {
        $name = $this->get_name();
        $first_char = substr($name, 0, 1);
        if ($first_char !== '+' && $first_char !== '-') {
            return $name;
        }
        $date = $this->resolve_carbon($date);
        // Integer construction no longer supported since PHP 8
        // @codeCoverageIgnoreStart
        try {
            $offset = @$this->get_offset($date) ?: 0;
        } catch (Throwable) {
            $offset = 0;
        }
        // @codeCoverageIgnoreEnd
        $name = @timezone_name_from_abbr('', $offset, $is_dst);
        if ($name) {
            return $name;
        }
        foreach (timezone_identifiers_list() as $timezone) {
            if (Carbon::instance($date)->set_timezone($timezone)->get_offset() === $offset) {
                return $timezone;
            }
        }
        return null;
    }
    /**
     * Returns a new CarbonTimeZone object using the region string instead of offset string.
     */
    public function to_region_time_zone(?DateTimeInterface $date = null): ?self
    {
        $timezone = $this->to_region_name($date);
        if ($timezone !== null) {
            return new static($timezone);
        }
        if (Carbon::is_strict_mode_enabled()) {
            throw new Invalid_Time_Zone_Exception('Unknown timezone for offset ' . $this->get_offset($this->resolve_carbon($date)) . ' seconds.');
        }
        return null;
    }
    /**
     * Cast to string (get timezone name).
     *
     * @return string
     */
    public function __toString()
    {
        return $this->get_name();
    }
    /**
     * Return the type number:
     *
     * Type 1; A UTC offset, such as -0300
     * Type 2; A timezone abbreviation, such as GMT
     * Type 3: A timezone identifier, such as Europe/London
     */
    public function get_type(): int
    {
        return preg_match('/"timezone_type";i:(\d)/', serialize($this), $match) ? (int) $match[1] : 3;
    }
    /**
     * Create a CarbonTimeZone from mixed input.
     *
     * @param DateTimeZone|string|int|null $object
     *
     * @return false|static
     */
    public static function create($object = null)
    {
        return static::instance($object);
    }
    /**
     * Create a CarbonTimeZone from int/float hour offset.
     *
     * @param float $hourOffset number of hour of the timezone shift (can be decimal).
     *
     * @return false|static
     */
    public static function create_from_hour_offset(float $hour_offset)
    {
        return static::create_from_minute_offset($hour_offset * Carbon::MINUTES_PER_HOUR);
    }
    /**
     * Create a CarbonTimeZone from int/float minute offset.
     *
     * @param float $minuteOffset number of total minutes of the timezone shift.
     *
     * @return false|static
     */
    public static function create_from_minute_offset(float $minute_offset)
    {
        return static::instance(static::get_offset_name_from_minute_offset($minute_offset));
    }
    /**
     * Convert a total minutes offset into a standardized timezone offset string.
     *
     * @param float $minutes number of total minutes of the timezone shift.
     *
     * @return string
     */
    public static function get_offset_name_from_minute_offset(float $minutes): string
    {
        $minutes = round($minutes);
        $unsigned_minutes = abs($minutes);
        return ($minutes < 0 ? '-' : '+') . str_pad((string) floor($unsigned_minutes / 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) ($unsigned_minutes % 60), 2, '0', STR_PAD_LEFT);
    }
    private function resolve_carbon(?DateTimeInterface $date): DateTimeInterface
    {
        if ($date) {
            return $date;
        }
        if (isset($this->clock)) {
            return $this->clock->now()->set_timezone($this);
        }
        return Carbon::now($this);
    }
}