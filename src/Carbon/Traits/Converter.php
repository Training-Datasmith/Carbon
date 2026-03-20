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
use Carbon\Carbon_Period_Immutable;
use Carbon\Exceptions\Unit_Exception;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
/**
 * Trait Converter.
 *
 * Change date into different string formats and types and
 * handle the string cast.
 *
 * Depends on the following methods:
 *
 * @method static copy()
 */
trait Converter
{
    use To_String_Format;
    /**
     * Returns the formatted date string on success or FALSE on failure.
     *
     * @see https://php.net/manual/en/datetime.format.php
     */
    public function format(string $format): string
    {
        $function = $this->local_format_function ?? $this->get_factory()->get_settings()['formatFunction'] ?? static::$format_function;
        if (!$function) {
            return $this->raw_format($format);
        }
        if (\is_string($function) && method_exists($this, $function)) {
            $function = [$this, $function];
        }
        return $function(...\func_get_args());
    }
    /**
     * @see https://php.net/manual/en/datetime.format.php
     */
    public function raw_format(string $format): string
    {
        return parent::format($format);
    }
    /**
     * Format the instance as a string using the set format
     *
     * @example
     * ```
     * echo Carbon::now(); // Carbon instances can be cast to string
     * ```
     */
    public function __toString(): string
    {
        $format = $this->local_to_string_format ?? $this->get_factory()->get_settings()['toStringFormat'] ?? null;
        return $format instanceof Closure ? $format($this) : $this->raw_format($format ?: (\defined('static::DEFAULT_TO_STRING_FORMAT') ? static::DEFAULT_TO_STRING_FORMAT : Carbon_Interface::DEFAULT_TO_STRING_FORMAT));
    }
    /**
     * Format the instance as date
     *
     * @example
     * ```
     * echo Carbon::now()->toDateString();
     * ```
     */
    public function to_date_string(): string
    {
        return $this->raw_format('Y-m-d');
    }
    /**
     * Format the instance as a readable date
     *
     * @example
     * ```
     * echo Carbon::now()->toFormattedDateString();
     * ```
     */
    public function to_formatted_date_string(): string
    {
        return $this->raw_format('M j, Y');
    }
    /**
     * Format the instance with the day, and a readable date
     *
     * @example
     * ```
     * echo Carbon::now()->toFormattedDayDateString();
     * ```
     */
    public function to_formatted_day_date_string(): string
    {
        return $this->raw_format('D, M j, Y');
    }
    /**
     * Format the instance as time
     *
     * @example
     * ```
     * echo Carbon::now()->toTimeString();
     * ```
     */
    public function to_time_string(string $unit_precision = 'second'): string
    {
        return $this->raw_format(static::get_time_format_by_precision($unit_precision));
    }
    /**
     * Format the instance as date and time
     *
     * @example
     * ```
     * echo Carbon::now()->toDateTimeString();
     * ```
     */
    public function to_date_time_string(string $unit_precision = 'second'): string
    {
        return $this->raw_format('Y-m-d ' . static::get_time_format_by_precision($unit_precision));
    }
    /**
     * Return a format from H:i to H:i:s.u according to given unit precision.
     *
     * @param string $unitPrecision "minute", "second", "millisecond" or "microsecond"
     */
    public static function get_time_format_by_precision(string $unit_precision): string
    {
        return match (static::singular_unit($unit_precision)) {
            'minute' => 'H:i',
            'second' => 'H:i:s',
            'm', 'millisecond' => 'H:i:s.v',
            'µ', 'microsecond' => 'H:i:s.u',
            default => throw new Unit_Exception('Precision unit expected among: minute, second, millisecond and microsecond.'),
        };
    }
    /**
     * Format the instance as date and time T-separated with no timezone
     *
     * @example
     * ```
     * echo Carbon::now()->toDateTimeLocalString();
     * echo "\n";
     * echo Carbon::now()->toDateTimeLocalString('minute'); // You can specify precision among: minute, second, millisecond and microsecond
     * ```
     */
    public function to_date_time_local_string(string $unit_precision = 'second'): string
    {
        return $this->raw_format('Y-m-d\T' . static::get_time_format_by_precision($unit_precision));
    }
    /**
     * Format the instance with day, date and time
     *
     * @example
     * ```
     * echo Carbon::now()->toDayDateTimeString();
     * ```
     */
    public function to_day_date_time_string(): string
    {
        return $this->raw_format('D, M j, Y g:i A');
    }
    /**
     * Format the instance as ATOM
     *
     * @example
     * ```
     * echo Carbon::now()->toAtomString();
     * ```
     */
    public function to_atom_string(): string
    {
        return $this->raw_format(DateTime::ATOM);
    }
    /**
     * Format the instance as COOKIE
     *
     * @example
     * ```
     * echo Carbon::now()->toCookieString();
     * ```
     */
    public function to_cookie_string(): string
    {
        return $this->raw_format(DateTimeInterface::COOKIE);
    }
    /**
     * Format the instance as ISO8601
     *
     * @example
     * ```
     * echo Carbon::now()->toIso8601String();
     * ```
     */
    public function to_iso8601string(): string
    {
        return $this->to_atom_string();
    }
    /**
     * Format the instance as RFC822
     *
     * @example
     * ```
     * echo Carbon::now()->toRfc822String();
     * ```
     */
    public function to_rfc822string(): string
    {
        return $this->raw_format(DateTimeInterface::RFC822);
    }
    /**
     * Convert the instance to UTC and return as Zulu ISO8601
     *
     * @example
     * ```
     * echo Carbon::now()->toIso8601ZuluString();
     * ```
     */
    public function to_iso8601zulu_string(string $unit_precision = 'second'): string
    {
        return $this->avoid_mutation()->utc()->raw_format('Y-m-d\T' . static::get_time_format_by_precision($unit_precision) . '\Z');
    }
    /**
     * Format the instance as RFC850
     *
     * @example
     * ```
     * echo Carbon::now()->toRfc850String();
     * ```
     */
    public function to_rfc850string(): string
    {
        return $this->raw_format(DateTimeInterface::RFC850);
    }
    /**
     * Format the instance as RFC1036
     *
     * @example
     * ```
     * echo Carbon::now()->toRfc1036String();
     * ```
     */
    public function to_rfc1036string(): string
    {
        return $this->raw_format(DateTimeInterface::RFC1036);
    }
    /**
     * Format the instance as RFC1123
     *
     * @example
     * ```
     * echo Carbon::now()->toRfc1123String();
     * ```
     */
    public function to_rfc1123string(): string
    {
        return $this->raw_format(DateTimeInterface::RFC1123);
    }
    /**
     * Format the instance as RFC2822
     *
     * @example
     * ```
     * echo Carbon::now()->toRfc2822String();
     * ```
     */
    public function to_rfc2822string(): string
    {
        return $this->raw_format(DateTimeInterface::RFC2822);
    }
    /**
     * Format the instance as RFC3339.
     *
     * @example
     * ```
     * echo Carbon::now()->toRfc3339String() . "\n";
     * echo Carbon::now()->toRfc3339String(true) . "\n";
     * ```
     */
    public function to_rfc3339string(bool $extended = false): string
    {
        return $this->raw_format($extended ? DateTimeInterface::RFC3339_EXTENDED : DateTimeInterface::RFC3339);
    }
    /**
     * Format the instance as RSS
     *
     * @example
     * ```
     * echo Carbon::now()->toRssString();
     * ```
     */
    public function to_rss_string(): string
    {
        return $this->raw_format(DateTimeInterface::RSS);
    }
    /**
     * Format the instance as W3C
     *
     * @example
     * ```
     * echo Carbon::now()->toW3cString();
     * ```
     */
    public function to_w3c_string(): string
    {
        return $this->raw_format(DateTimeInterface::W3C);
    }
    /**
     * Format the instance as RFC7231
     *
     * @example
     * ```
     * echo Carbon::now()->toRfc7231String();
     * ```
     */
    public function to_rfc7231string(): string
    {
        return $this->avoid_mutation()->set_timezone('GMT')->raw_format(\defined('static::RFC7231_FORMAT') ? static::RFC7231_FORMAT : Carbon_Interface::RFC7231_FORMAT);
    }
    /**
     * Get default array representation.
     *
     * @example
     * ```
     * var_dump(Carbon::now()->toArray());
     * ```
     */
    public function to_array(): array
    {
        return ['year' => $this->year, 'month' => $this->month, 'day' => $this->day, 'dayOfWeek' => $this->day_of_week, 'dayOfYear' => $this->day_of_year, 'hour' => $this->hour, 'minute' => $this->minute, 'second' => $this->second, 'micro' => $this->micro, 'timestamp' => $this->timestamp, 'formatted' => $this->raw_format(\defined('static::DEFAULT_TO_STRING_FORMAT') ? static::DEFAULT_TO_STRING_FORMAT : Carbon_Interface::DEFAULT_TO_STRING_FORMAT), 'timezone' => $this->timezone];
    }
    /**
     * Get default object representation.
     *
     * @example
     * ```
     * var_dump(Carbon::now()->toObject());
     * ```
     */
    public function to_object(): object
    {
        return (object) $this->to_array();
    }
    /**
     * Returns english human-readable complete date string.
     *
     * @example
     * ```
     * echo Carbon::now()->toString();
     * ```
     */
    public function to_string(): string
    {
        return $this->avoid_mutation()->locale('en')->iso_format('ddd MMM DD YYYY HH:mm:ss [GMT]ZZ');
    }
    /**
     * Return the ISO-8601 string (ex: 1977-04-22T06:00:00Z, if $keepOffset truthy, offset will be kept:
     * 1977-04-22T01:00:00-05:00).
     *
     * @example
     * ```
     * echo Carbon::now('America/Toronto')->toISOString() . "\n";
     * echo Carbon::now('America/Toronto')->toISOString(true) . "\n";
     * ```
     *
     * @param bool $keepOffset Pass true to keep the date offset. Else forced to UTC.
     */
    public function to_iso_string(bool $keep_offset = false): ?string
    {
        if (!$this->is_valid()) {
            return null;
        }
        $year_format = $this->year < 0 || $this->year > 9999 ? 'YYYYYY' : 'YYYY';
        $timezone_format = $keep_offset ? 'Z' : '[Z]';
        $date = $keep_offset ? $this : $this->avoid_mutation()->utc();
        return $date->iso_format("{$year_format}-MM-DD[T]HH:mm:ss.SSSSSS{$timezone_format}");
    }
    /**
     * Return the ISO-8601 string (ex: 1977-04-22T06:00:00Z) with UTC timezone.
     *
     * @example
     * ```
     * echo Carbon::now('America/Toronto')->toJSON();
     * ```
     */
    public function to_json(): ?string
    {
        return $this->to_iso_string();
    }
    /**
     * Return native DateTime PHP object matching the current instance.
     *
     * @example
     * ```
     * var_dump(Carbon::now()->toDateTime());
     * ```
     */
    public function to_date_time(): DateTime
    {
        return DateTime::create_from_format('U.u', $this->raw_format('U.u'))->set_timezone($this->get_timezone());
    }
    /**
     * Return native toDateTimeImmutable PHP object matching the current instance.
     *
     * @example
     * ```
     * var_dump(Carbon::now()->toDateTimeImmutable());
     * ```
     */
    public function to_date_time_immutable(): DateTimeImmutable
    {
        return DateTimeImmutable::create_from_format('U.u', $this->raw_format('U.u'))->set_timezone($this->get_timezone());
    }
    /**
     * @alias toDateTime
     *
     * Return native DateTime PHP object matching the current instance.
     *
     * @example
     * ```
     * var_dump(Carbon::now()->toDate());
     * ```
     */
    public function to_date(): DateTime
    {
        return $this->to_date_time();
    }
    /**
     * Create a iterable CarbonPeriod object from current date to a given end date (and optional interval).
     *
     * @param \DateTimeInterface|Carbon|CarbonImmutable|int|null $end      period end date or recurrences count if int
     * @param int|\DateInterval|string|null                      $interval period default interval or number of the given $unit
     * @param string|null                                        $unit     if specified, $interval must be an integer
     */
    public function to_period($end = null, $interval = null, $unit = null): Carbon_Period
    {
        if ($unit) {
            $interval = Carbon_Interval::make("{$interval} " . static::plural_unit($unit));
        }
        $is_default_interval = !$interval;
        $interval ??= Carbon_Interval::day();
        $class = $this->is_mutable() ? Carbon_Period::class : Carbon_Period_Immutable::class;
        $end ??= 1;
        if (!\is_int($end)) {
            $end = $this->resolve_carbon($end);
        }
        return new $class(raw: [$this, Carbon_Interval::make($interval), $end], dateClass: static::class, isDefaultInterval: $is_default_interval);
    }
    /**
     * Create a iterable CarbonPeriod object from current date to a given end date (and optional interval).
     *
     * @param \DateTimeInterface|Carbon|CarbonImmutable|null $end      period end date
     * @param int|\DateInterval|string|null                  $interval period default interval or number of the given $unit
     * @param string|null                                    $unit     if specified, $interval must be an integer
     */
    public function range($end = null, $interval = null, $unit = null): Carbon_Period
    {
        return $this->to_period($end, $interval, $unit);
    }
}