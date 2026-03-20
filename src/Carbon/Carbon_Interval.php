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

use Carbon\Constants\Unit_Value;
use Carbon\Exceptions\Bad_Fluent_Constructor_Exception;
use Carbon\Exceptions\Bad_Fluent_Setter_Exception;
use Carbon\Exceptions\Invalid_Cast_Exception;
use Carbon\Exceptions\Invalid_Format_Exception;
use Carbon\Exceptions\Invalid_Interval_Exception;
use Carbon\Exceptions\OutOfRangeException;
use Carbon\Exceptions\Parse_Error_Exception;
use Carbon\Exceptions\Unit_Not_Configured_Exception;
use Carbon\Exceptions\Unknown_Getter_Exception;
use Carbon\Exceptions\Unknown_Setter_Exception;
use Carbon\Exceptions\Unknown_Unit_Exception;
use Carbon\Traits\Interval_Rounding;
use Carbon\Traits\Interval_Step;
use Carbon\Traits\Local_Factory;
use Carbon\Traits\Magic_Parameter;
use Carbon\Traits\Mixin;
use Carbon\Traits\Options;
use Carbon\Traits\To_String_Format;
use Closure;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Reflection_Exception;
use Return_Type_Will_Change;
use RuntimeException;
use Symfony\Contracts\Translation\Translator_Interface;
use Throwable;
/**
 * A simple API extension for DateInterval.
 * The implementation provides helpers to handle weeks but only days are saved.
 * Weeks are calculated based on the total days of the current instance.
 *
 * @property int $years Year component of the current interval. (For P2Y6M, the value will be 2)
 * @property int $months Month component of the current interval. (For P1Y6M10D, the value will be 6)
 * @property int $weeks Week component of the current interval calculated from the days. (For P1Y6M17D, the value will be 2)
 * @property int $dayz Day component of the current interval (weeks * 7 + days). (For P6M17DT20H, the value will be 17)
 * @property int $hours Hour component of the current interval. (For P7DT20H5M, the value will be 20)
 * @property int $minutes Minute component of the current interval. (For PT20H5M30S, the value will be 5)
 * @property int $seconds Second component of the current interval. (CarbonInterval::minutes(2)->seconds(34)->microseconds(567_890)->seconds = 34)
 * @property int $milliseconds Milliseconds component of the current interval. (CarbonInterval::seconds(34)->microseconds(567_890)->milliseconds = 567)
 * @property int $microseconds Microseconds component of the current interval. (CarbonInterval::seconds(34)->microseconds(567_890)->microseconds = 567_890)
 * @property int $microExcludeMilli Remaining microseconds without the milliseconds.
 * @property int $dayzExcludeWeeks Total days remaining in the final week of the current instance (days % 7).
 * @property int $daysExcludeWeeks alias of dayzExcludeWeeks
 * @property-read float $totalYears Number of years equivalent to the interval. (For P1Y6M, the value will be 1.5)
 * @property-read float $totalMonths Number of months equivalent to the interval. (For P1Y6M10D, the value will be ~12.357)
 * @property-read float $totalWeeks Number of weeks equivalent to the interval. (For P6M17DT20H, the value will be ~26.548)
 * @property-read float $totalDays Number of days equivalent to the interval. (For P17DT20H, the value will be ~17.833)
 * @property-read float $totalDayz Alias for totalDays.
 * @property-read float $totalHours Number of hours equivalent to the interval. (For P1DT20H5M, the value will be ~44.083)
 * @property-read float $totalMinutes Number of minutes equivalent to the interval. (For PT20H5M30S, the value will be 1205.5)
 * @property-read float $totalSeconds Number of seconds equivalent to the interval. (CarbonInterval::minutes(2)->seconds(34)->microseconds(567_890)->totalSeconds = 154.567_890)
 * @property-read float $totalMilliseconds Number of milliseconds equivalent to the interval. (CarbonInterval::seconds(34)->microseconds(567_890)->totalMilliseconds = 34567.890)
 * @property-read float $totalMicroseconds Number of microseconds equivalent to the interval. (CarbonInterval::seconds(34)->microseconds(567_890)->totalMicroseconds = 34567890)
 * @property-read string $locale locale of the current instance
 *
 * @method static CarbonInterval years($years = 1) Create instance specifying a number of years or modify the number of years if called on an instance.
 * @method static CarbonInterval year($years = 1) Alias for years()
 * @method static CarbonInterval months($months = 1) Create instance specifying a number of months or modify the number of months if called on an instance.
 * @method static CarbonInterval month($months = 1) Alias for months()
 * @method static CarbonInterval weeks($weeks = 1) Create instance specifying a number of weeks or modify the number of weeks if called on an instance.
 * @method static CarbonInterval week($weeks = 1) Alias for weeks()
 * @method static CarbonInterval days($days = 1) Create instance specifying a number of days or modify the number of days if called on an instance.
 * @method static CarbonInterval dayz($days = 1) Alias for days()
 * @method static CarbonInterval daysExcludeWeeks($days = 1) Create instance specifying a number of days or modify the number of days (keeping the current number of weeks) if called on an instance.
 * @method static CarbonInterval dayzExcludeWeeks($days = 1) Alias for daysExcludeWeeks()
 * @method static CarbonInterval day($days = 1) Alias for days()
 * @method static CarbonInterval hours($hours = 1) Create instance specifying a number of hours or modify the number of hours if called on an instance.
 * @method static CarbonInterval hour($hours = 1) Alias for hours()
 * @method static CarbonInterval minutes($minutes = 1) Create instance specifying a number of minutes or modify the number of minutes if called on an instance.
 * @method static CarbonInterval minute($minutes = 1) Alias for minutes()
 * @method static CarbonInterval seconds($seconds = 1) Create instance specifying a number of seconds or modify the number of seconds if called on an instance.
 * @method static CarbonInterval second($seconds = 1) Alias for seconds()
 * @method static CarbonInterval milliseconds($milliseconds = 1) Create instance specifying a number of milliseconds or modify the number of milliseconds if called on an instance.
 * @method static CarbonInterval millisecond($milliseconds = 1) Alias for milliseconds()
 * @method static CarbonInterval microseconds($microseconds = 1) Create instance specifying a number of microseconds or modify the number of microseconds if called on an instance.
 * @method static CarbonInterval microsecond($microseconds = 1) Alias for microseconds()
 * @method $this addYears(int $years) Add given number of years to the current interval
 * @method $this subYears(int $years) Subtract given number of years to the current interval
 * @method $this addMonths(int $months) Add given number of months to the current interval
 * @method $this subMonths(int $months) Subtract given number of months to the current interval
 * @method $this addWeeks(int|float $weeks) Add given number of weeks to the current interval
 * @method $this subWeeks(int|float $weeks) Subtract given number of weeks to the current interval
 * @method $this addDays(int|float $days) Add given number of days to the current interval
 * @method $this subDays(int|float $days) Subtract given number of days to the current interval
 * @method $this addHours(int|float $hours) Add given number of hours to the current interval
 * @method $this subHours(int|float $hours) Subtract given number of hours to the current interval
 * @method $this addMinutes(int|float $minutes) Add given number of minutes to the current interval
 * @method $this subMinutes(int|float $minutes) Subtract given number of minutes to the current interval
 * @method $this addSeconds(int|float $seconds) Add given number of seconds to the current interval
 * @method $this subSeconds(int|float $seconds) Subtract given number of seconds to the current interval
 * @method $this addMilliseconds(int|float $milliseconds) Add given number of milliseconds to the current interval
 * @method $this subMilliseconds(int|float $milliseconds) Subtract given number of milliseconds to the current interval
 * @method $this addMicroseconds(int|float $microseconds) Add given number of microseconds to the current interval
 * @method $this subMicroseconds(int|float $microseconds) Subtract given number of microseconds to the current interval
 * @method $this roundYear(int|float $precision = 1, string $function = "round") Round the current instance year with given precision using the given function.
 * @method $this roundYears(int|float $precision = 1, string $function = "round") Round the current instance year with given precision using the given function.
 * @method $this floorYear(int|float $precision = 1) Truncate the current instance year with given precision.
 * @method $this floorYears(int|float $precision = 1) Truncate the current instance year with given precision.
 * @method $this ceilYear(int|float $precision = 1) Ceil the current instance year with given precision.
 * @method $this ceilYears(int|float $precision = 1) Ceil the current instance year with given precision.
 * @method $this roundMonth(int|float $precision = 1, string $function = "round") Round the current instance month with given precision using the given function.
 * @method $this roundMonths(int|float $precision = 1, string $function = "round") Round the current instance month with given precision using the given function.
 * @method $this floorMonth(int|float $precision = 1) Truncate the current instance month with given precision.
 * @method $this floorMonths(int|float $precision = 1) Truncate the current instance month with given precision.
 * @method $this ceilMonth(int|float $precision = 1) Ceil the current instance month with given precision.
 * @method $this ceilMonths(int|float $precision = 1) Ceil the current instance month with given precision.
 * @method $this roundWeek(int|float $precision = 1, string $function = "round") Round the current instance day with given precision using the given function.
 * @method $this roundWeeks(int|float $precision = 1, string $function = "round") Round the current instance day with given precision using the given function.
 * @method $this floorWeek(int|float $precision = 1) Truncate the current instance day with given precision.
 * @method $this floorWeeks(int|float $precision = 1) Truncate the current instance day with given precision.
 * @method $this ceilWeek(int|float $precision = 1) Ceil the current instance day with given precision.
 * @method $this ceilWeeks(int|float $precision = 1) Ceil the current instance day with given precision.
 * @method $this roundDay(int|float $precision = 1, string $function = "round") Round the current instance day with given precision using the given function.
 * @method $this roundDays(int|float $precision = 1, string $function = "round") Round the current instance day with given precision using the given function.
 * @method $this floorDay(int|float $precision = 1) Truncate the current instance day with given precision.
 * @method $this floorDays(int|float $precision = 1) Truncate the current instance day with given precision.
 * @method $this ceilDay(int|float $precision = 1) Ceil the current instance day with given precision.
 * @method $this ceilDays(int|float $precision = 1) Ceil the current instance day with given precision.
 * @method $this roundHour(int|float $precision = 1, string $function = "round") Round the current instance hour with given precision using the given function.
 * @method $this roundHours(int|float $precision = 1, string $function = "round") Round the current instance hour with given precision using the given function.
 * @method $this floorHour(int|float $precision = 1) Truncate the current instance hour with given precision.
 * @method $this floorHours(int|float $precision = 1) Truncate the current instance hour with given precision.
 * @method $this ceilHour(int|float $precision = 1) Ceil the current instance hour with given precision.
 * @method $this ceilHours(int|float $precision = 1) Ceil the current instance hour with given precision.
 * @method $this roundMinute(int|float $precision = 1, string $function = "round") Round the current instance minute with given precision using the given function.
 * @method $this roundMinutes(int|float $precision = 1, string $function = "round") Round the current instance minute with given precision using the given function.
 * @method $this floorMinute(int|float $precision = 1) Truncate the current instance minute with given precision.
 * @method $this floorMinutes(int|float $precision = 1) Truncate the current instance minute with given precision.
 * @method $this ceilMinute(int|float $precision = 1) Ceil the current instance minute with given precision.
 * @method $this ceilMinutes(int|float $precision = 1) Ceil the current instance minute with given precision.
 * @method $this roundSecond(int|float $precision = 1, string $function = "round") Round the current instance second with given precision using the given function.
 * @method $this roundSeconds(int|float $precision = 1, string $function = "round") Round the current instance second with given precision using the given function.
 * @method $this floorSecond(int|float $precision = 1) Truncate the current instance second with given precision.
 * @method $this floorSeconds(int|float $precision = 1) Truncate the current instance second with given precision.
 * @method $this ceilSecond(int|float $precision = 1) Ceil the current instance second with given precision.
 * @method $this ceilSeconds(int|float $precision = 1) Ceil the current instance second with given precision.
 * @method $this roundMillennium(int|float $precision = 1, string $function = "round") Round the current instance millennium with given precision using the given function.
 * @method $this roundMillennia(int|float $precision = 1, string $function = "round") Round the current instance millennium with given precision using the given function.
 * @method $this floorMillennium(int|float $precision = 1) Truncate the current instance millennium with given precision.
 * @method $this floorMillennia(int|float $precision = 1) Truncate the current instance millennium with given precision.
 * @method $this ceilMillennium(int|float $precision = 1) Ceil the current instance millennium with given precision.
 * @method $this ceilMillennia(int|float $precision = 1) Ceil the current instance millennium with given precision.
 * @method $this roundCentury(int|float $precision = 1, string $function = "round") Round the current instance century with given precision using the given function.
 * @method $this roundCenturies(int|float $precision = 1, string $function = "round") Round the current instance century with given precision using the given function.
 * @method $this floorCentury(int|float $precision = 1) Truncate the current instance century with given precision.
 * @method $this floorCenturies(int|float $precision = 1) Truncate the current instance century with given precision.
 * @method $this ceilCentury(int|float $precision = 1) Ceil the current instance century with given precision.
 * @method $this ceilCenturies(int|float $precision = 1) Ceil the current instance century with given precision.
 * @method $this roundDecade(int|float $precision = 1, string $function = "round") Round the current instance decade with given precision using the given function.
 * @method $this roundDecades(int|float $precision = 1, string $function = "round") Round the current instance decade with given precision using the given function.
 * @method $this floorDecade(int|float $precision = 1) Truncate the current instance decade with given precision.
 * @method $this floorDecades(int|float $precision = 1) Truncate the current instance decade with given precision.
 * @method $this ceilDecade(int|float $precision = 1) Ceil the current instance decade with given precision.
 * @method $this ceilDecades(int|float $precision = 1) Ceil the current instance decade with given precision.
 * @method $this roundQuarter(int|float $precision = 1, string $function = "round") Round the current instance quarter with given precision using the given function.
 * @method $this roundQuarters(int|float $precision = 1, string $function = "round") Round the current instance quarter with given precision using the given function.
 * @method $this floorQuarter(int|float $precision = 1) Truncate the current instance quarter with given precision.
 * @method $this floorQuarters(int|float $precision = 1) Truncate the current instance quarter with given precision.
 * @method $this ceilQuarter(int|float $precision = 1) Ceil the current instance quarter with given precision.
 * @method $this ceilQuarters(int|float $precision = 1) Ceil the current instance quarter with given precision.
 * @method $this roundMillisecond(int|float $precision = 1, string $function = "round") Round the current instance millisecond with given precision using the given function.
 * @method $this roundMilliseconds(int|float $precision = 1, string $function = "round") Round the current instance millisecond with given precision using the given function.
 * @method $this floorMillisecond(int|float $precision = 1) Truncate the current instance millisecond with given precision.
 * @method $this floorMilliseconds(int|float $precision = 1) Truncate the current instance millisecond with given precision.
 * @method $this ceilMillisecond(int|float $precision = 1) Ceil the current instance millisecond with given precision.
 * @method $this ceilMilliseconds(int|float $precision = 1) Ceil the current instance millisecond with given precision.
 * @method $this roundMicrosecond(int|float $precision = 1, string $function = "round") Round the current instance microsecond with given precision using the given function.
 * @method $this roundMicroseconds(int|float $precision = 1, string $function = "round") Round the current instance microsecond with given precision using the given function.
 * @method $this floorMicrosecond(int|float $precision = 1) Truncate the current instance microsecond with given precision.
 * @method $this floorMicroseconds(int|float $precision = 1) Truncate the current instance microsecond with given precision.
 * @method $this ceilMicrosecond(int|float $precision = 1) Ceil the current instance microsecond with given precision.
 * @method $this ceilMicroseconds(int|float $precision = 1) Ceil the current instance microsecond with given precision.
 */
class Carbon_Interval extends DateInterval implements Carbon_Converter_Interface, Unit_Value, \Stringable
{
    use Local_Factory;
    use Interval_Rounding;
    use Interval_Step;
    use Magic_Parameter;
    use Mixin {
        Mixin::mixin as baseMixin;
    }
    use Options;
    use To_String_Format;
    /**
     * Unlimited parts for forHumans() method.
     *
     * INF constant can be used instead.
     */
    public const NO_LIMIT = -1;
    public const POSITIVE = 1;
    public const NEGATIVE = -1;
    /**
     * Interval spec period designators
     */
    public const PERIOD_PREFIX = 'P';
    public const PERIOD_YEARS = 'Y';
    public const PERIOD_MONTHS = 'M';
    public const PERIOD_DAYS = 'D';
    public const PERIOD_TIME_PREFIX = 'T';
    public const PERIOD_HOURS = 'H';
    public const PERIOD_MINUTES = 'M';
    public const PERIOD_SECONDS = 'S';
    public const SPECIAL_TRANSLATIONS = [1 => ['option' => Carbon_Interface::ONE_DAY_WORDS, 'future' => 'diff_tomorrow', 'past' => 'diff_yesterday'], 2 => ['option' => Carbon_Interface::TWO_DAY_WORDS, 'future' => 'diff_after_tomorrow', 'past' => 'diff_before_yesterday']];
    protected static ?array $cascade_factors = null;
    protected static array $formats = ['y' => 'y', 'Y' => 'y', 'o' => 'y', 'm' => 'm', 'n' => 'm', 'W' => 'weeks', 'd' => 'd', 'j' => 'd', 'z' => 'd', 'h' => 'h', 'g' => 'h', 'H' => 'h', 'G' => 'h', 'i' => 'i', 's' => 's', 'u' => 'micro', 'v' => 'milli'];
    private static ?array $flip_cascade_factors = null;
    private static bool $float_setters_enabled = false;
    /**
     * The registered macros.
     */
    protected static array $macros = [];
    /**
     * Timezone handler for settings() method.
     */
    protected DateTimeZone|string|int|null $timezone_setting = null;
    /**
     * The input used to create the interval.
     */
    protected mixed $original_input = null;
    /**
     * Start date if interval was created from a difference between 2 dates.
     */
    protected ?Carbon_Interface $start_date = null;
    /**
     * End date if interval was created from a difference between 2 dates.
     */
    protected ?Carbon_Interface $end_date = null;
    /**
     * End date if interval was created from a difference between 2 dates.
     */
    protected ?DateInterval $raw_interval = null;
    /**
     * Flag if the interval was made from a diff with absolute flag on.
     */
    protected bool $absolute = false;
    protected ?array $initial_values = null;
    /**
     * Set the instance's timezone from a string or object.
     */
    public function set_timezone(DateTimeZone|string|int $timezone): static
    {
        $this->timezone_setting = $timezone;
        $this->check_start_and_end();
        if ($this->start_date) {
            $this->start_date = $this->start_date->avoid_mutation()->set_timezone($timezone);
            $this->raw_interval = null;
        }
        if ($this->end_date) {
            $this->end_date = $this->end_date->avoid_mutation()->set_timezone($timezone);
            $this->raw_interval = null;
        }
        return $this;
    }
    /**
     * Set the instance's timezone from a string or object and add/subtract the offset difference.
     */
    public function shift_timezone(DateTimeZone|string|int $timezone): static
    {
        $this->timezone_setting = $timezone;
        $this->check_start_and_end();
        if ($this->start_date) {
            $this->start_date = $this->start_date->avoid_mutation()->shift_timezone($timezone);
            $this->raw_interval = null;
        }
        if ($this->end_date) {
            $this->end_date = $this->end_date->avoid_mutation()->shift_timezone($timezone);
            $this->raw_interval = null;
        }
        return $this;
    }
    /**
     * Mapping of units and factors for cascading.
     *
     * Should only be modified by changing the factors or referenced constants.
     */
    public static function get_cascade_factors(): array
    {
        return static::$cascade_factors ?: static::get_default_cascade_factors();
    }
    protected static function get_default_cascade_factors(): array
    {
        return ['milliseconds' => [Carbon_Interface::MICROSECONDS_PER_MILLISECOND, 'microseconds'], 'seconds' => [Carbon_Interface::MILLISECONDS_PER_SECOND, 'milliseconds'], 'minutes' => [Carbon_Interface::SECONDS_PER_MINUTE, 'seconds'], 'hours' => [Carbon_Interface::MINUTES_PER_HOUR, 'minutes'], 'dayz' => [Carbon_Interface::HOURS_PER_DAY, 'hours'], 'weeks' => [Carbon_Interface::DAYS_PER_WEEK, 'dayz'], 'months' => [Carbon_Interface::WEEKS_PER_MONTH, 'weeks'], 'years' => [Carbon_Interface::MONTHS_PER_YEAR, 'months']];
    }
    /**
     * Set default cascading factors for ->cascade() method.
     */
    public static function set_cascade_factors(array $cascade_factors): void
    {
        self::$flip_cascade_factors = null;
        static::$cascade_factors = $cascade_factors;
    }
    /**
     * This option allow you to opt-in for the Carbon 3 behavior where float
     * values will no longer be cast to integer (so truncated).
     *
     * ⚠️ This settings will be applied globally, which mean your whole application
     * code including the third-party dependencies that also may use Carbon will
     * adopt the new behavior.
     */
    public static function enable_float_setters(bool $float_setters_enabled = true): void
    {
        self::$float_setters_enabled = $float_setters_enabled;
    }
    ///////////////////////////////////////////////////////////////////
    //////////////////////////// CONSTRUCTORS /////////////////////////
    ///////////////////////////////////////////////////////////////////
    /**
     * Create a new CarbonInterval instance.
     *
     * @param Closure|DateInterval|string|int|null $years
     * @param int|float|null                       $months
     * @param int|float|null                       $weeks
     * @param int|float|null                       $days
     * @param int|float|null                       $hours
     * @param int|float|null                       $minutes
     * @param int|float|null                       $seconds
     * @param int|float|null                       $microseconds
     *
     * @throws Exception when the interval_spec (passed as $years) cannot be parsed as an interval.
     */
    public function __construct($years = null, $months = null, $weeks = null, $days = null, $hours = null, $minutes = null, $seconds = null, $microseconds = null)
    {
        $this->original_input = \func_num_args() === 1 ? $years : \func_get_args();
        if ($years instanceof Closure) {
            $this->step = $years;
            $years = null;
        }
        if ($years instanceof DateInterval) {
            parent::__construct(static::get_date_interval_spec($years));
            $this->f = $years->f;
            self::copy_negative_units($years, $this);
            return;
        }
        $spec = $years;
        $is_string_spec = \is_string($spec) && !preg_match('/^[\d.]/', $spec);
        if (!$is_string_spec || (float) $years) {
            $spec = static::PERIOD_PREFIX;
            $spec .= $years > 0 ? $years . static::PERIOD_YEARS : '';
            $spec .= $months > 0 ? $months . static::PERIOD_MONTHS : '';
            $spec_days = 0;
            $spec_days += $weeks > 0 ? $weeks * static::get_days_per_week() : 0;
            $spec_days += $days > 0 ? $days : 0;
            $spec .= $spec_days > 0 ? $spec_days . static::PERIOD_DAYS : '';
            if ($hours > 0 || $minutes > 0 || $seconds > 0) {
                $spec .= static::PERIOD_TIME_PREFIX;
                $spec .= $hours > 0 ? $hours . static::PERIOD_HOURS : '';
                $spec .= $minutes > 0 ? $minutes . static::PERIOD_MINUTES : '';
                $spec .= $seconds > 0 ? $seconds . static::PERIOD_SECONDS : '';
            }
            if ($spec === static::PERIOD_PREFIX) {
                // Allow the zero interval.
                $spec .= '0' . static::PERIOD_YEARS;
            }
        }
        try {
            parent::__construct($spec);
        } catch (Throwable $exception) {
            try {
                parent::__construct('PT0S');
                if ($is_string_spec) {
                    if (!preg_match('/^P
                        (?:(?<year>[+-]?\d*(?:\.\d+)?)Y)?
                        (?:(?<month>[+-]?\d*(?:\.\d+)?)M)?
                        (?:(?<week>[+-]?\d*(?:\.\d+)?)W)?
                        (?:(?<day>[+-]?\d*(?:\.\d+)?)D)?
                        (?:T
                            (?:(?<hour>[+-]?\d*(?:\.\d+)?)H)?
                            (?:(?<minute>[+-]?\d*(?:\.\d+)?)M)?
                            (?:(?<second>[+-]?\d*(?:\.\d+)?)S)?
                        )?
                    $/x', $spec, $match)) {
                        throw new InvalidArgumentException("Invalid duration: {$spec}");
                    }
                    $years = (float) ($match['year'] ?? 0);
                    $this->assert_safe_for_integer('year', $years);
                    $months = (float) ($match['month'] ?? 0);
                    $this->assert_safe_for_integer('month', $months);
                    $weeks = (float) ($match['week'] ?? 0);
                    $this->assert_safe_for_integer('week', $weeks);
                    $days = (float) ($match['day'] ?? 0);
                    $this->assert_safe_for_integer('day', $days);
                    $hours = (float) ($match['hour'] ?? 0);
                    $this->assert_safe_for_integer('hour', $hours);
                    $minutes = (float) ($match['minute'] ?? 0);
                    $this->assert_safe_for_integer('minute', $minutes);
                    $seconds = (float) ($match['second'] ?? 0);
                    $this->assert_safe_for_integer('second', $seconds);
                    $microseconds = (int) str_pad(substr(explode('.', $match['second'] ?? '0.0')[1] ?? '0', 0, 6), 6, '0');
                }
                $total_days = $weeks * static::get_days_per_week() + $days;
                $this->assert_safe_for_integer('days total (including weeks)', $total_days);
                $this->y = (int) $years;
                $this->m = (int) $months;
                $this->d = (int) $total_days;
                $this->h = (int) $hours;
                $this->i = (int) $minutes;
                $this->s = (int) $seconds;
                $second_float_part = (float) ($microseconds / Carbon_Interface::MICROSECONDS_PER_SECOND);
                $this->f = $second_float_part;
                $interval_microseconds = (int) ($this->f * Carbon_Interface::MICROSECONDS_PER_SECOND);
                $interval_seconds = $seconds - $second_float_part;
                if ((float) $this->y !== $years || (float) $this->m !== $months || (float) $this->d !== $total_days || (float) $this->h !== $hours || (float) $this->i !== $minutes || (float) $this->s !== $interval_seconds || $interval_microseconds !== (int) $microseconds) {
                    $this->add(static::from_string($years - $this->y . ' years ' . ($months - $this->m) . ' months ' . ($total_days - $this->d) . ' days ' . ($hours - $this->h) . ' hours ' . ($minutes - $this->i) . ' minutes ' . number_format($interval_seconds - $this->s, 6, '.', '') . ' seconds ' . ($microseconds - $interval_microseconds) . ' microseconds '));
                }
            } catch (Throwable $second_exception) {
                throw $second_exception instanceof OutOfRangeException ? $second_exception : $exception;
            }
        }
        if ($microseconds !== null) {
            $this->f = $microseconds / Carbon_Interface::MICROSECONDS_PER_SECOND;
        }
        foreach (['years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds'] as $unit) {
            if (${$unit} < 0) {
                $this->set($unit, ${$unit});
            }
        }
    }
    /**
     * Returns the factor for a given source-to-target couple.
     *
     * @param string $source
     * @param string $target
     *
     * @return int|float|null
     */
    public static function get_factor($source, $target)
    {
        $source = self::standardize_unit($source);
        $target = self::standardize_unit($target);
        $factors = self::get_flip_cascade_factors();
        if (isset($factors[$source])) {
            [$to, $factor] = $factors[$source];
            if ($to === $target) {
                return $factor;
            }
            return $factor * static::get_factor($to, $target);
        }
        return null;
    }
    /**
     * Returns the factor for a given source-to-target couple if set,
     * else try to find the appropriate constant as the factor, such as Carbon::DAYS_PER_WEEK.
     *
     * @param string $source
     * @param string $target
     *
     * @return int|float|null
     */
    public static function get_factor_with_default($source, $target)
    {
        $factor = self::get_factor($source, $target);
        if ($factor) {
            return $factor;
        }
        static $defaults = ['month' => ['year' => Carbon::MONTHS_PER_YEAR], 'week' => ['month' => Carbon::WEEKS_PER_MONTH], 'day' => ['week' => Carbon::DAYS_PER_WEEK], 'hour' => ['day' => Carbon::HOURS_PER_DAY], 'minute' => ['hour' => Carbon::MINUTES_PER_HOUR], 'second' => ['minute' => Carbon::SECONDS_PER_MINUTE], 'millisecond' => ['second' => Carbon::MILLISECONDS_PER_SECOND], 'microsecond' => ['millisecond' => Carbon::MICROSECONDS_PER_MILLISECOND]];
        return $defaults[$source][$target] ?? null;
    }
    /**
     * Returns current config for days per week.
     *
     * @return int|float
     */
    public static function get_days_per_week()
    {
        return static::get_factor('dayz', 'weeks') ?: Carbon::DAYS_PER_WEEK;
    }
    /**
     * Returns current config for hours per day.
     *
     * @return int|float
     */
    public static function get_hours_per_day()
    {
        return static::get_factor('hours', 'dayz') ?: Carbon::HOURS_PER_DAY;
    }
    /**
     * Returns current config for minutes per hour.
     *
     * @return int|float
     */
    public static function get_minutes_per_hour()
    {
        return static::get_factor('minutes', 'hours') ?: Carbon::MINUTES_PER_HOUR;
    }
    /**
     * Returns current config for seconds per minute.
     *
     * @return int|float
     */
    public static function get_seconds_per_minute()
    {
        return static::get_factor('seconds', 'minutes') ?: Carbon::SECONDS_PER_MINUTE;
    }
    /**
     * Returns current config for microseconds per second.
     *
     * @return int|float
     */
    public static function get_milliseconds_per_second()
    {
        return static::get_factor('milliseconds', 'seconds') ?: Carbon::MILLISECONDS_PER_SECOND;
    }
    /**
     * Returns current config for microseconds per second.
     *
     * @return int|float
     */
    public static function get_microseconds_per_millisecond()
    {
        return static::get_factor('microseconds', 'milliseconds') ?: Carbon::MICROSECONDS_PER_MILLISECOND;
    }
    /**
     * Create a new CarbonInterval instance from specific values.
     * This is an alias for the constructor that allows better fluent
     * syntax as it allows you to do CarbonInterval::create(1)->fn() rather than
     * (new CarbonInterval(1))->fn().
     *
     * @param int $years
     * @param int $months
     * @param int $weeks
     * @param int $days
     * @param int $hours
     * @param int $minutes
     * @param int $seconds
     * @param int $microseconds
     *
     * @throws Exception when the interval_spec (passed as $years) cannot be parsed as an interval.
     */
    public static function create($years = null, $months = null, $weeks = null, $days = null, $hours = null, $minutes = null, $seconds = null, $microseconds = null): static
    {
        return new static($years, $months, $weeks, $days, $hours, $minutes, $seconds, $microseconds);
    }
    /**
     * Parse a string into a new CarbonInterval object according to the specified format.
     *
     * @example
     * ```
     * echo Carboninterval::createFromFormat('H:i', '1:30');
     * ```
     *
     * @param string      $format   Format of the $interval input string
     * @param string|null $interval Input string to convert into an interval
     *
     * @throws \Carbon\Exceptions\ParseErrorException when the $interval cannot be parsed as an interval.
     */
    public static function create_from_format(string $format, ?string $interval): static
    {
        $instance = new static(0);
        $length = mb_strlen($format);
        if (preg_match('/s([,.])([uv])$/', $format, $match)) {
            $interval = explode($match[1], (string) $interval);
            $index = \count($interval) - 1;
            $interval[$index] = str_pad($interval[$index], $match[2] === 'v' ? 3 : 6, '0');
            $interval = implode($match[1], $interval);
        }
        $interval ??= '';
        for ($index = 0; $index < $length; $index++) {
            $expected = mb_substr($format, $index, 1);
            $next_character = mb_substr($interval, 0, 1);
            $unit = static::$formats[$expected] ?? null;
            if ($unit) {
                if (!preg_match('/^-?\d+/', $interval, $match)) {
                    throw new Parse_Error_Exception('number', $next_character);
                }
                $interval = mb_substr($interval, mb_strlen($match[0]));
                self::increment_unit($instance, $unit, (int) $match[0]);
                continue;
            }
            if ($next_character !== $expected) {
                throw new Parse_Error_Exception("'{$expected}'", $next_character, 'Allowed substitutes for interval formats are ' . implode(', ', array_keys(static::$formats)) . "\n" . 'See https://php.net/manual/en/function.date.php for their meaning');
            }
            $interval = mb_substr($interval, 1);
        }
        if ($interval !== '') {
            throw new Parse_Error_Exception('end of string', $interval);
        }
        return $instance;
    }
    /**
     * Return the original source used to create the current interval.
     *
     * @return array|int|string|DateInterval|mixed|null
     */
    public function original()
    {
        return $this->original_input;
    }
    /**
     * Return the start date if interval was created from a difference between 2 dates.
     */
    public function start(): ?Carbon_Interface
    {
        $this->check_start_and_end();
        return $this->start_date;
    }
    /**
     * Return the end date if interval was created from a difference between 2 dates.
     */
    public function end(): ?Carbon_Interface
    {
        $this->check_start_and_end();
        return $this->end_date;
    }
    /**
     * Get rid of the original input, start date and end date that may be kept in memory.
     *
     * @return $this
     */
    public function optimize(): static
    {
        $this->original_input = null;
        $this->start_date = null;
        $this->end_date = null;
        $this->raw_interval = null;
        $this->absolute = false;
        return $this;
    }
    /**
     * Get a copy of the instance.
     */
    public function copy(): static
    {
        $date = new static(0);
        $date->copy_properties($this);
        $date->step = $this->step;
        return $date;
    }
    /**
     * Get a copy of the instance.
     */
    public function clone(): static
    {
        return $this->copy();
    }
    /**
     * Provide static helpers to create instances.  Allows CarbonInterval::years(3).
     *
     * Note: This is done using the magic method to allow static and instance methods to
     *       have the same names.
     *
     * @param string $method     magic method name called
     * @param array  $parameters parameters list
     *
     * @return static|mixed|null
     */
    public static function __callStatic(string $method, array $parameters)
    {
        try {
            $interval = new static(0);
            $local_strict_mode_enabled = $interval->local_strict_mode_enabled;
            $interval->local_strict_mode_enabled = true;
            $result = static::has_macro($method) ? static::bind_macro_context(null, function () use (&$method, &$parameters, &$interval) {
                return $interval->call_macro($method, $parameters);
            }) : $interval->{$method}(...$parameters);
            $interval->local_strict_mode_enabled = $local_strict_mode_enabled;
            return $result;
        } catch (Bad_Fluent_Setter_Exception $exception) {
            if (Carbon::is_strict_mode_enabled()) {
                throw new Bad_Fluent_Constructor_Exception($method, 0, $exception);
            }
            return null;
        }
    }
    /**
     * Evaluate the PHP generated by var_export() and recreate the exported CarbonInterval instance.
     *
     * @param array $dump data as exported by var_export()
     *
     * @return static
     */
    #[Return_Type_Will_Change]
    public static function __set_state($dump)
    {
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        /** @var DateInterval $dateInterval */
        $date_interval = parent::__set_state($dump);
        return static::instance($date_interval);
    }
    /**
     * Return the current context from inside a macro callee or a new one if static.
     */
    protected static function this(): static
    {
        return end(static::$macro_context_stack) ?: new static(0);
    }
    /**
     * Creates a CarbonInterval from string.
     *
     * Format:
     *
     * Suffix | Unit    | Example | DateInterval expression
     * -------|---------|---------|------------------------
     * y      | years   |   1y    | P1Y
     * mo     | months  |   3mo   | P3M
     * w      | weeks   |   2w    | P2W
     * d      | days    |  28d    | P28D
     * h      | hours   |   4h    | PT4H
     * m      | minutes |  12m    | PT12M
     * s      | seconds |  59s    | PT59S
     *
     * e. g. `1w 3d 4h 32m 23s` is converted to 10 days 4 hours 32 minutes and 23 seconds.
     *
     * Special cases:
     *  - An empty string will return a zero interval
     *  - Fractions are allowed for weeks, days, hours and minutes and will be converted
     *    and rounded to the next smaller value (caution: 0.5w = 4d)
     *
     *
     * @throws InvalidIntervalException
     *
     */
    public static function from_string(string $interval_definition): static
    {
        if (empty($interval_definition)) {
            return self::with_original(new static(0), $interval_definition);
        }
        $years = 0;
        $months = 0;
        $weeks = 0;
        $days = 0;
        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        $milliseconds = 0;
        $microseconds = 0;
        $pattern = '/(-?\d+(?:\.\d+)?)\h*([^\d\h]*)/i';
        preg_match_all($pattern, $interval_definition, $parts, PREG_SET_ORDER);
        while ([$part, $value, $unit] = array_shift($parts)) {
            $int_value = (int) $value;
            $fraction = (float) $value - $int_value;
            // Fix calculation precision
            switch (round($fraction, 6)) {
                case 1:
                    $fraction = 0;
                    $int_value++;
                    break;
                case 0:
                    $fraction = 0;
                    break;
            }
            switch ($unit === 'µs' ? 'µs' : strtolower($unit)) {
                case 'millennia':
                case 'millennium':
                    $years += $int_value * Carbon_Interface::YEARS_PER_MILLENNIUM;
                    break;
                case 'century':
                case 'centuries':
                    $years += $int_value * Carbon_Interface::YEARS_PER_CENTURY;
                    break;
                case 'decade':
                case 'decades':
                    $years += $int_value * Carbon_Interface::YEARS_PER_DECADE;
                    break;
                case 'year':
                case 'years':
                case 'y':
                case 'yr':
                case 'yrs':
                    $years += $int_value;
                    break;
                case 'quarter':
                case 'quarters':
                    $months += $int_value * Carbon_Interface::MONTHS_PER_QUARTER;
                    break;
                case 'month':
                case 'months':
                case 'mo':
                case 'mos':
                    $months += $int_value;
                    break;
                case 'week':
                case 'weeks':
                case 'w':
                    $weeks += $int_value;
                    if ($fraction) {
                        $parts[] = [null, $fraction * static::get_days_per_week(), 'd'];
                    }
                    break;
                case 'day':
                case 'days':
                case 'd':
                    $days += $int_value;
                    if ($fraction) {
                        $parts[] = [null, $fraction * static::get_hours_per_day(), 'h'];
                    }
                    break;
                case 'hour':
                case 'hours':
                case 'h':
                    $hours += $int_value;
                    if ($fraction) {
                        $parts[] = [null, $fraction * static::get_minutes_per_hour(), 'm'];
                    }
                    break;
                case 'minute':
                case 'minutes':
                case 'm':
                    $minutes += $int_value;
                    if ($fraction) {
                        $parts[] = [null, $fraction * static::get_seconds_per_minute(), 's'];
                    }
                    break;
                case 'second':
                case 'seconds':
                case 's':
                    $seconds += $int_value;
                    if ($fraction) {
                        $parts[] = [null, $fraction * static::get_milliseconds_per_second(), 'ms'];
                    }
                    break;
                case 'millisecond':
                case 'milliseconds':
                case 'milli':
                case 'ms':
                    $milliseconds += $int_value;
                    if ($fraction) {
                        $microseconds += round($fraction * static::get_microseconds_per_millisecond());
                    }
                    break;
                case 'microsecond':
                case 'microseconds':
                case 'micro':
                case 'µs':
                    $microseconds += $int_value;
                    break;
                default:
                    throw new Invalid_Interval_Exception("Invalid part {$part} in definition {$interval_definition}");
            }
        }
        return self::with_original(new static($years, $months, $weeks, $days, $hours, $minutes, $seconds, $milliseconds * Carbon::MICROSECONDS_PER_MILLISECOND + $microseconds), $interval_definition);
    }
    /**
     * Creates a CarbonInterval from string using a different locale.
     *
     * @param string      $interval interval string in the given language (may also contain English).
     * @param string|null $locale   if locale is null or not specified, current global locale will be used instead.
     */
    public static function parse_from_locale(string $interval, ?string $locale = null): static
    {
        return static::from_string(Carbon::translate_time_string($interval, $locale ?: static::get_locale(), Carbon_Interface::DEFAULT_LOCALE));
    }
    /**
     * Create an interval from the difference between 2 dates.
     *
     * @param \Carbon\Carbon|\DateTimeInterface|mixed $start
     * @param \Carbon\Carbon|\DateTimeInterface|mixed $end
     */
    public static function diff($start, $end = null, bool $absolute = false, array $skip = []): static
    {
        $start = $start instanceof Carbon_Interface ? $start : Carbon::make($start);
        $end = $end instanceof Carbon_Interface ? $end : Carbon::make($end);
        $raw_interval = $start->diff_as_date_interval($end, $absolute);
        $interval = static::instance($raw_interval, $skip);
        $interval->absolute = $absolute;
        $interval->raw_interval = $raw_interval;
        $interval->start_date = $start;
        $interval->end_date = $end;
        $interval->initial_values = $interval->get_inner_values();
        return $interval;
    }
    /**
     * Invert the interval if it's inverted.
     *
     * @param bool $absolute do nothing if set to false
     *
     * @return $this
     */
    public function abs(bool $absolute = false): static
    {
        if ($absolute && $this->invert) {
            $this->invert();
        }
        return $this;
    }
    /**
     * @alias abs
     *
     * Invert the interval if it's inverted.
     *
     * @param bool $absolute do nothing if set to false
     *
     * @return $this
     */
    public function absolute(bool $absolute = true): static
    {
        return $this->abs($absolute);
    }
    /**
     * Cast the current instance into the given class.
     *
     * @template T of DateInterval
     *
     * @psalm-param class-string<T> $className The $className::instance() method will be called to cast the current object.
     *
     * @return T
     */
    public function cast(string $class_name): mixed
    {
        return self::cast_interval_to_class($this, $class_name);
    }
    /**
     * Create a CarbonInterval instance from a DateInterval one.  Can not instance
     * DateInterval objects created from DateTime::diff() as you can't externally
     * set the $days field.
     *
     * @param bool         $skipCopy set to true to return the passed object
     *                               (without copying it) if it's already of the
     *                               current class
     *
     */
    public static function instance(DateInterval $interval, array $skip = [], bool $skip_copy = false): static
    {
        if ($skip_copy && $interval instanceof static) {
            return $interval;
        }
        return self::cast_interval_to_class($interval, static::class, $skip);
    }
    /**
     * Make a CarbonInterval instance from given variable if possible.
     *
     * Always return a new instance. Parse only strings and only these likely to be intervals (skip dates
     * and recurrences). Throw an exception for invalid format, but otherwise return null.
     *
     * @param mixed|int|DateInterval|string|Closure|Unit|null $interval interval or number of the given $unit
     * @param Unit|string|null                                $unit     if specified, $interval must be an integer
     * @param bool                                            $skipCopy set to true to return the passed object
     *                                                                  (without copying it) if it's already of the
     *                                                                  current class
     *
     * @return static|null
     */
    public static function make($interval, $unit = null, bool $skip_copy = false): ?self
    {
        if ($interval instanceof Unit) {
            $interval = $interval->interval();
        }
        if ($unit instanceof Unit) {
            $unit = $unit->value;
        }
        if ($unit) {
            $interval = "{$interval} {$unit}";
        }
        if ($interval instanceof DateInterval) {
            return static::instance($interval, [], $skip_copy);
        }
        if ($interval instanceof Closure) {
            return self::with_original(new static($interval), $interval);
        }
        if (!\is_string($interval)) {
            return null;
        }
        return static::make_from_string($interval);
    }
    protected static function make_from_string(string $interval): ?self
    {
        $interval = preg_replace('/\s+/', ' ', trim($interval));
        if (preg_match('/^P[T\d]/', (string) $interval)) {
            return new static($interval);
        }
        if (preg_match('/^(?:\h*-?\d+(?:\.\d+)?\h*[a-z]+)+$/i', (string) $interval)) {
            return static::from_string($interval);
        }
        $interval_instance = static::create_from_date_string($interval);
        return $interval_instance->is_empty() ? null : $interval_instance;
    }
    protected function resolve_interval($interval): ?self
    {
        if (!$interval instanceof self) {
            return self::make($interval);
        }
        return $interval;
    }
    /**
     * Sets up a DateInterval from the relative parts of the string.
     *
     *
     *
     * @link https://php.net/manual/en/dateinterval.createfromdatestring.php
     */
    public static function create_from_date_string(string $datetime): static
    {
        $string = strtr($datetime, [',' => ' ', ' and ' => ' ']);
        $previous_exception = null;
        try {
            $interval = parent::create_from_date_string($string);
        } catch (Throwable $exception) {
            $interval = null;
            $previous_exception = $exception;
        }
        $interval ?: throw new Invalid_Format_Exception('Could not create interval from: ' . var_export($datetime, true), previous: $previous_exception);
        if (!$interval instanceof static) {
            $interval = static::instance($interval);
        }
        return self::with_original($interval, $datetime);
    }
    ///////////////////////////////////////////////////////////////////
    ///////////////////////// GETTERS AND SETTERS /////////////////////
    ///////////////////////////////////////////////////////////////////
    /**
     * Get a part of the CarbonInterval object.
     */
    public function get(Unit|string $name): int|float|string|null
    {
        $name = Unit::to_name($name);
        if (str_starts_with($name, 'total')) {
            return $this->total(substr($name, 5));
        }
        $resolved_unit = Carbon::singular_unit(rtrim($name, 'z'));
        return match ($resolved_unit) {
            'tzname', 'tz_name' => match (true) {
                $this->timezone_setting === null => null,
                \is_string($this->timezone_setting) => $this->timezone_setting,
                $this->timezone_setting instanceof DateTimeZone => $this->timezone_setting->get_name(),
                default => Carbon_Time_Zone::instance($this->timezone_setting)->get_name(),
            },
            'year' => $this->y,
            'month' => $this->m,
            'day' => $this->d,
            'hour' => $this->h,
            'minute' => $this->i,
            'second' => $this->s,
            'milli', 'millisecond' => (int) (round($this->f * Carbon::MICROSECONDS_PER_SECOND) / Carbon::MICROSECONDS_PER_MILLISECOND),
            'micro', 'microsecond' => (int) round($this->f * Carbon::MICROSECONDS_PER_SECOND),
            'microexcludemilli' => (int) round($this->f * Carbon::MICROSECONDS_PER_SECOND) % Carbon::MICROSECONDS_PER_MILLISECOND,
            'week' => (int) ($this->d / (int) static::get_days_per_week()),
            'daysexcludeweek', 'dayzexcludeweek' => $this->d % (int) static::get_days_per_week(),
            'locale' => $this->get_translator_locale(),
            default => throw new Unknown_Getter_Exception($name, previous: new Unknown_Getter_Exception($resolved_unit)),
        };
    }
    /**
     * Get a part of the CarbonInterval object.
     */
    public function __get(string $name): int|float|string|null
    {
        return $this->get($name);
    }
    /**
     * Set a part of the CarbonInterval object.
     *
     * @param Unit|string|array $name
     * @param int               $value
     *
     * @throws UnknownSetterException
     *
     * @return $this
     */
    public function set($name, $value = null): static
    {
        $properties = \is_array($name) ? $name : [$name => $value];
        foreach ($properties as $key => $value) {
            switch (Carbon::singular_unit($key instanceof Unit ? $key->value : rtrim((string) $key, 'z'))) {
                case 'year':
                    $this->check_integer_value($key, $value);
                    $this->y = $value;
                    $this->handle_decimal_part('year', $value, $this->y);
                    break;
                case 'month':
                    $this->check_integer_value($key, $value);
                    $this->m = $value;
                    $this->handle_decimal_part('month', $value, $this->m);
                    break;
                case 'week':
                    $this->check_integer_value($key, $value);
                    $days = $value * (int) static::get_days_per_week();
                    $this->assert_safe_for_integer('days total (including weeks)', $days);
                    $this->d = $days;
                    $this->handle_decimal_part('day', $days, $this->d);
                    break;
                case 'day':
                    if ($value === false) {
                        break;
                    }
                    $this->check_integer_value($key, $value);
                    $this->d = $value;
                    $this->handle_decimal_part('day', $value, $this->d);
                    break;
                case 'daysexcludeweek':
                case 'dayzexcludeweek':
                    $this->check_integer_value($key, $value);
                    $days = $this->weeks * (int) static::get_days_per_week() + $value;
                    $this->assert_safe_for_integer('days total (including weeks)', $days);
                    $this->d = $days;
                    $this->handle_decimal_part('day', $days, $this->d);
                    break;
                case 'hour':
                    $this->check_integer_value($key, $value);
                    $this->h = $value;
                    $this->handle_decimal_part('hour', $value, $this->h);
                    break;
                case 'minute':
                    $this->check_integer_value($key, $value);
                    $this->i = $value;
                    $this->handle_decimal_part('minute', $value, $this->i);
                    break;
                case 'second':
                    $this->check_integer_value($key, $value);
                    $this->s = $value;
                    $this->handle_decimal_part('second', $value, $this->s);
                    break;
                case 'milli':
                case 'millisecond':
                    $this->microseconds = $value * Carbon::MICROSECONDS_PER_MILLISECOND + $this->microseconds % Carbon::MICROSECONDS_PER_MILLISECOND;
                    break;
                case 'micro':
                case 'microsecond':
                    $this->f = $value / Carbon::MICROSECONDS_PER_SECOND;
                    break;
                default:
                    if (str_starts_with((string) $key, ' * ')) {
                        return $this->set_setting(substr((string) $key, 3), $value);
                    }
                    if ($this->local_strict_mode_enabled ?? Carbon::is_strict_mode_enabled()) {
                        throw new Unknown_Setter_Exception($key);
                    }
                    $this->{$key} = $value;
            }
        }
        return $this;
    }
    /**
     * Set a part of the CarbonInterval object.
     *
     * @param int    $value
     * @throws UnknownSetterException
     */
    public function __set(string $name, mixed $value)
    {
        $this->set($name, $value);
    }
    /**
     * Allow setting of weeks and days to be cumulative.
     *
     * @param int $weeks Number of weeks to set
     * @param int $days  Number of days to set
     */
    public function weeks_and_days(int $weeks, int $days): static
    {
        $this->dayz = $weeks * static::get_days_per_week() + $days;
        return $this;
    }
    /**
     * Returns true if the interval is empty for each unit.
     */
    public function is_empty(): bool
    {
        return $this->years === 0 && $this->months === 0 && $this->dayz === 0 && !$this->days && $this->hours === 0 && $this->minutes === 0 && $this->seconds === 0 && $this->microseconds === 0;
    }
    /**
     * Register a custom macro.
     *
     * Pass null macro to remove it.
     *
     * @example
     * ```
     * CarbonInterval::macro('twice', function () {
     *   return $this->times(2);
     * });
     * echo CarbonInterval::hours(2)->twice();
     * ```
     *
     * @param-closure-this static $macro
     */
    public static function macro(string $name, ?callable $macro): void
    {
        static::$macros[$name] = $macro;
    }
    /**
     * Register macros from a mixin object.
     *
     * @example
     * ```
     * CarbonInterval::mixin(new class {
     *   public function daysToHours() {
     *     return function () {
     *       $this->hours += $this->days;
     *       $this->days = 0;
     *
     *       return $this;
     *     };
     *   }
     *   public function hoursToDays() {
     *     return function () {
     *       $this->days += $this->hours;
     *       $this->hours = 0;
     *
     *       return $this;
     *     };
     *   }
     * });
     * echo CarbonInterval::hours(5)->hoursToDays() . "\n";
     * echo CarbonInterval::days(5)->daysToHours() . "\n";
     * ```
     *
     * @param object|string $mixin
     *
     * @throws ReflectionException
     */
    public static function mixin($mixin): void
    {
        static::base_mixin($mixin);
    }
    /**
     * Check if macro is registered.
     *
     *
     */
    public static function has_macro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }
    /**
     * Call given macro.
     *
     *
     * @return mixed
     */
    protected function call_macro(string $name, array $parameters)
    {
        $macro = static::$macros[$name];
        if ($macro instanceof Closure) {
            $bound_macro = @$macro->bind_to($this, static::class) ?: @$macro->bind_to(null, static::class);
            return ($bound_macro ?: $macro)(...$parameters);
        }
        return $macro(...$parameters);
    }
    /**
     * Allow fluent calls on the setters... CarbonInterval::years(3)->months(5)->day().
     *
     * Note: This is done using the magic method to allow static and instance methods to
     *       have the same names.
     *
     * @param string $method     magic method name called
     * @param array  $parameters parameters list
     *
     * @throws BadFluentSetterException|Throwable
     *
     * @return static|int|float|string
     */
    public function __call(string $method, array $parameters)
    {
        if (static::has_macro($method)) {
            return static::bind_macro_context($this, function () use (&$method, &$parameters) {
                return $this->call_macro($method, $parameters);
            });
        }
        $rounded_value = $this->call_round_method($method, $parameters);
        if ($rounded_value !== null) {
            return $rounded_value;
        }
        if (preg_match('/^(?<method>add|sub)(?<unit>[A-Z].*)$/', $method, $match)) {
            $value = $this->get_magic_parameter($parameters, 0, Carbon::plural_unit($match['unit']), 0);
            return $this->{$match['method']}($value, $match['unit']);
        }
        $value = $this->get_magic_parameter($parameters, 0, Carbon::plural_unit($method), 1);
        try {
            $this->set($method, $value);
        } catch (Unknown_Setter_Exception $exception) {
            if ($this->local_strict_mode_enabled ?? Carbon::is_strict_mode_enabled()) {
                throw new Bad_Fluent_Setter_Exception($method, 0, $exception);
            }
        }
        return $this;
    }
    protected function get_for_humans_initial_variables($syntax, $short): array
    {
        if (\is_array($syntax)) {
            return $syntax;
        }
        if (\is_int($short)) {
            return ['parts' => $short, 'short' => false];
        }
        if (\is_bool($syntax)) {
            return ['short' => $syntax, 'syntax' => Carbon_Interface::DIFF_ABSOLUTE];
        }
        return [];
    }
    /**
     * @param mixed $syntax
     * @param mixed $short
     * @param mixed $parts
     * @param mixed $options
     */
    protected function get_for_humans_parameters($syntax = null, $short = false, $parts = self::NO_LIMIT, $options = null): array
    {
        $optional_space = ' ';
        $default = $this->get_translation_message('list.0') ?? $this->get_translation_message('list') ?? ' ';
        /** @var bool|string $join */
        $join = $default === '' ? '' : ' ';
        /** @var bool|array|string $altNumbers */
        $alt_numbers = false;
        $a_unit = false;
        $minimum_unit = 's';
        $skip = [];
        extract($this->get_for_humans_initial_variables($syntax, $short));
        $skip = array_map(static fn($unit) => $unit instanceof Unit ? $unit->value : $unit, $skip);
        $skip = array_map(strtolower(...), array_filter($skip, static fn($unit): bool => \is_string($unit) && $unit !== ''));
        $syntax ??= Carbon_Interface::DIFF_ABSOLUTE;
        if ($parts === self::NO_LIMIT) {
            $parts = INF;
        }
        $options ??= static::get_human_diff_options();
        if ($join === false) {
            $join = ' ';
        } elseif ($join === true) {
            $join = [$default, $this->get_translation_message('list.1') ?? $default];
        }
        if ($alt_numbers && $alt_numbers !== true) {
            $language = new Language($this->locale);
            $alt_numbers = \in_array($language->get_code(), (array) $alt_numbers, true);
        }
        if (\is_array($join)) {
            [$default, $last] = $join;
            if ($default !== ' ') {
                $optional_space = '';
            }
            $join = function ($list) use ($default, $last): string {
                if (\count($list) < 2) {
                    return implode('', $list);
                }
                $end = array_pop($list);
                return implode($default, $list) . $last . $end;
            };
        }
        if (\is_string($join)) {
            if ($join !== ' ') {
                $optional_space = '';
            }
            $glue = $join;
            $join = static fn($list): string => implode($glue, $list);
        }
        $interpolations = [':optional-space' => $optional_space];
        $translator ??= isset($locale) ? Translator::get($locale) : null;
        return [$syntax, $short, $parts, $options, $join, $a_unit, $alt_numbers, $interpolations, $minimum_unit, $skip, $translator];
    }
    protected static function get_rounding_method_from_options(int $options): ?string
    {
        if ($options & Carbon_Interface::ROUND) {
            return 'round';
        }
        if ($options & Carbon_Interface::CEIL) {
            return 'ceil';
        }
        if ($options & Carbon_Interface::FLOOR) {
            return 'floor';
        }
        return null;
    }
    /**
     * Returns interval values as an array where key are the unit names and values the counts.
     *
     * @return int[]
     */
    public function to_array(): array
    {
        return ['years' => $this->years, 'months' => $this->months, 'weeks' => $this->weeks, 'days' => $this->days_exclude_weeks, 'hours' => $this->hours, 'minutes' => $this->minutes, 'seconds' => $this->seconds, 'microseconds' => $this->microseconds];
    }
    /**
     * Returns interval non-zero values as an array where key are the unit names and values the counts.
     *
     * @return int[]
     */
    public function get_non_zero_values(): array
    {
        return array_filter($this->to_array(), intval(...));
    }
    /**
     * Returns interval values as an array where key are the unit names and values the counts
     * from the biggest non-zero one the the smallest non-zero one.
     *
     * @return int[]
     */
    public function get_values_sequence(): array
    {
        $non_zero_values = $this->get_non_zero_values();
        if ($non_zero_values === []) {
            return [];
        }
        $keys = array_keys($non_zero_values);
        $first_key = $keys[0];
        $last_key = $keys[\count($keys) - 1];
        $values = [];
        $record = false;
        foreach ($this->to_array() as $unit => $count) {
            if ($unit === $first_key) {
                $record = true;
            }
            if ($record) {
                $values[$unit] = $count;
            }
            if ($unit === $last_key) {
                $record = false;
            }
        }
        return $values;
    }
    /**
     * Get the current interval in a human readable format in the current locale.
     *
     * @example
     * ```
     * echo CarbonInterval::fromString('4d 3h 40m')->forHumans() . "\n";
     * echo CarbonInterval::fromString('4d 3h 40m')->forHumans(['parts' => 2]) . "\n";
     * echo CarbonInterval::fromString('4d 3h 40m')->forHumans(['parts' => 3, 'join' => true]) . "\n";
     * echo CarbonInterval::fromString('4d 3h 40m')->forHumans(['short' => true]) . "\n";
     * echo CarbonInterval::fromString('1d 24h')->forHumans(['join' => ' or ']) . "\n";
     * echo CarbonInterval::fromString('1d 24h')->forHumans(['minimumUnit' => 'hour']) . "\n";
     * ```
     *
     * @param int|array $syntax  if array passed, parameters will be extracted from it, the array may contain:
     *                           ⦿ 'syntax' entry (see below)
     *                           ⦿ 'short' entry (see below)
     *                           ⦿ 'parts' entry (see below)
     *                           ⦿ 'options' entry (see below)
     *                           ⦿ 'skip' entry, list of units to skip (array of strings or a single string,
     *                           ` it can be the unit name (singular or plural) or its shortcut
     *                           ` (y, m, w, d, h, min, s, ms, µs).
     *                           ⦿ 'aUnit' entry, prefer "an hour" over "1 hour" if true
     *                           ⦿ 'altNumbers' entry, use alternative numbers if available
     *                           ` (from the current language if true is passed, from the given language(s)
     *                           ` if array or string is passed)
     *                           ⦿ 'join' entry determines how to join multiple parts of the string
     *                           `  - if $join is a string, it's used as a joiner glue
     *                           `  - if $join is a callable/closure, it get the list of string and should return a string
     *                           `  - if $join is an array, the first item will be the default glue, and the second item
     *                           `    will be used instead of the glue for the last item
     *                           `  - if $join is true, it will be guessed from the locale ('list' translation file entry)
     *                           `  - if $join is missing, a space will be used as glue
     *                           ⦿ 'minimumUnit' entry determines the smallest unit of time to display can be long or
     *                           `  short form of the units, e.g. 'hour' or 'h' (default value: s)
     *                           ⦿ 'locale' language in which the diff should be output (has no effect if 'translator' key is set)
     *                           ⦿ 'translator' a custom translator to use to translator the output.
     *                           if int passed, it adds modifiers:
     *                           Possible values:
     *                           - CarbonInterface::DIFF_ABSOLUTE          no modifiers
     *                           - CarbonInterface::DIFF_RELATIVE_TO_NOW   add ago/from now modifier
     *                           - CarbonInterface::DIFF_RELATIVE_TO_OTHER add before/after modifier
     *                           Default value: CarbonInterface::DIFF_ABSOLUTE
     * @param bool      $short   displays short format of time units
     * @param int       $parts   maximum number of parts to display (default value: -1: no limits)
     * @param int       $options human diff options
     *
     * @throws Exception
     */
    public function for_humans($syntax = null, $short = false, $parts = self::NO_LIMIT, $options = null): string
    {
        /* @var TranslatorInterface|null $translator */
        [$syntax, $short, $parts, $options, $join, $a_unit, $alt_numbers, $interpolations, $minimum_unit, $skip, $translator] = $this->get_for_humans_parameters($syntax, $short, $parts, $options);
        $interval = [];
        $syntax = (int) ($syntax ?? Carbon_Interface::DIFF_ABSOLUTE);
        $absolute = $syntax === Carbon_Interface::DIFF_ABSOLUTE;
        $relative_to_now = $syntax === Carbon_Interface::DIFF_RELATIVE_TO_NOW;
        $count = 1;
        $unit = $short ? 's' : 'second';
        $is_future = $this->invert === 1;
        $trans_id = $relative_to_now ? $is_future ? 'from_now' : 'ago' : ($is_future ? 'after' : 'before');
        $declension_mode = null;
        $translator ??= $this->get_local_translator();
        $handle_declensions = function (string $unit, string|int|float|null $count, $index = 0, $parts = 1) use ($interpolations, $trans_id, $translator, $alt_numbers, $absolute, &$declension_mode): ?string {
            if (!$absolute) {
                $declension_mode ??= $this->translate($trans_id . '_mode');
                if ($this->needs_declension($declension_mode, $index, $parts)) {
                    // Some languages have special pluralization for past and future tense.
                    $key = $unit . '_' . $trans_id;
                    $result = $this->translate($key, $interpolations, $count, $translator, $alt_numbers);
                    if ($result !== $key) {
                        return $result;
                    }
                }
            }
            $result = $this->translate($unit, $interpolations, $count, $translator, $alt_numbers);
            if ($result !== $unit) {
                return $result;
            }
            return null;
        };
        $interval_values = $this;
        $method = static::get_rounding_method_from_options($options);
        if ($method) {
            $previous_count = INF;
            while (\count($interval_values->get_non_zero_values()) > $parts && ($count = \count($keys = array_keys($interval_values->get_values_sequence()))) > 1) {
                $index = min($count, $previous_count - 1) - 2;
                if ($index < 0) {
                    break;
                }
                $interval_values = $this->copy()->round_unit($keys[$index], 1, $method);
                $previous_count = $count;
            }
        }
        $diff_interval_array = [['value' => $interval_values->years, 'unit' => 'year', 'unitShort' => 'y'], ['value' => $interval_values->months, 'unit' => 'month', 'unitShort' => 'm'], ['value' => $interval_values->weeks, 'unit' => 'week', 'unitShort' => 'w'], ['value' => $interval_values->days_exclude_weeks, 'unit' => 'day', 'unitShort' => 'd'], ['value' => $interval_values->hours, 'unit' => 'hour', 'unitShort' => 'h'], ['value' => $interval_values->minutes, 'unit' => 'minute', 'unitShort' => 'min'], ['value' => $interval_values->seconds, 'unit' => 'second', 'unitShort' => 's'], ['value' => $interval_values->milliseconds, 'unit' => 'millisecond', 'unitShort' => 'ms'], ['value' => $interval_values->micro_exclude_milli, 'unit' => 'microsecond', 'unitShort' => 'µs']];
        if (!empty($skip)) {
            foreach ($diff_interval_array as $index => &$unit_data) {
                $next_index = $index + 1;
                if ($unit_data['value'] && isset($diff_interval_array[$next_index]) && \count(array_intersect([$unit_data['unit'], $unit_data['unit'] . 's', $unit_data['unitShort']], $skip))) {
                    $diff_interval_array[$next_index]['value'] += $unit_data['value'] * self::get_factor_with_default($diff_interval_array[$next_index]['unit'], $unit_data['unit']);
                    $unit_data['value'] = 0;
                }
            }
        }
        $trans_choice = function ($short, array $unit_data, $index, $parts) use ($absolute, $handle_declensions, $translator, $a_unit, $alt_numbers, $interpolations): ?string {
            $count = $unit_data['value'];
            if ($short) {
                $result = $handle_declensions($unit_data['unitShort'], $count, $index, $parts);
                if ($result !== null) {
                    return $result;
                }
            } elseif ($a_unit) {
                $result = $handle_declensions('a_' . $unit_data['unit'], $count, $index, $parts);
                if ($result !== null) {
                    return $result;
                }
            }
            if (!$absolute) {
                return $handle_declensions($unit_data['unit'], $count, $index, $parts);
            }
            return $this->translate($unit_data['unit'], $interpolations, $count, $translator, $alt_numbers);
        };
        $fallback_unit = ['second', 's'];
        foreach ($diff_interval_array as $diff_interval_data) {
            if ($diff_interval_data['value'] > 0) {
                $unit = $short ? $diff_interval_data['unitShort'] : $diff_interval_data['unit'];
                $count = $diff_interval_data['value'];
                $interval[] = [$short, $diff_interval_data];
            } elseif ($options & Carbon_Interface::SEQUENTIAL_PARTS_ONLY && \count($interval) > 0) {
                break;
            }
            // break the loop after we get the required number of parts in array
            if (\count($interval) >= $parts) {
                break;
            }
            // break the loop after we have reached the minimum unit
            if (\in_array($minimum_unit, [$diff_interval_data['unit'], $diff_interval_data['unitShort']], true)) {
                $fallback_unit = [$diff_interval_data['unit'], $diff_interval_data['unitShort']];
                break;
            }
        }
        $actual_parts = \count($interval);
        foreach ($interval as $index => &$item) {
            $item = $trans_choice($item[0], $item[1], $index, $actual_parts);
        }
        if (\count($interval) === 0) {
            if ($relative_to_now && $options & Carbon_Interface::JUST_NOW) {
                $key = 'diff_now';
                $translation = $this->translate($key, $interpolations, null, $translator);
                if ($translation !== $key) {
                    return $translation;
                }
            }
            $count = $options & Carbon_Interface::NO_ZERO_DIFF ? 1 : 0;
            $unit = $fallback_unit[$short ? 1 : 0];
            $interval[] = $this->translate($unit, $interpolations, $count, $translator, $alt_numbers);
        }
        // join the interval parts by a space
        $time = $join($interval);
        unset($diff_interval_array, $interval);
        if ($absolute) {
            return $time;
        }
        $is_future = $this->invert === 1;
        $trans_id = $relative_to_now ? $is_future ? 'from_now' : 'ago' : ($is_future ? 'after' : 'before');
        if ($parts === 1) {
            if ($relative_to_now && $unit === 'day') {
                $special_translations = static::SPECIAL_TRANSLATIONS[$count] ?? null;
                if ($special_translations && $options & $special_translations['option']) {
                    $key = $special_translations[$is_future ? 'future' : 'past'];
                    $translation = $this->translate($key, $interpolations, null, $translator);
                    if ($translation !== $key) {
                        return $translation;
                    }
                }
            }
            $a_time = $a_unit ? $handle_declensions('a_' . $unit, $count) : null;
            $time = ($a_time ?: $handle_declensions($unit, $count)) ?: $time;
        }
        $time = [':time' => $time];
        return $this->translate($trans_id, array_merge($time, $interpolations, $time), null, $translator);
    }
    public function format(string $format): string
    {
        $output = parent::format($format);
        if (!str_contains($format, '%a') || !isset($this->start_date, $this->end_date)) {
            return $output;
        }
        $this->raw_interval ??= $this->start_date->diff_as_date_interval($this->end_date);
        return str_replace('(unknown)', $this->raw_interval->format('%a'), $output);
    }
    /**
     * Format the instance as a string using the forHumans() function.
     *
     * @throws Exception
     */
    public function __toString(): string
    {
        $format = $this->local_to_string_format ?? $this->get_factory()->get_settings()['toStringFormat'] ?? null;
        if (!$format) {
            return $this->for_humans();
        }
        if ($format instanceof Closure) {
            return (string) $format($this);
        }
        return $this->format($format);
    }
    /**
     * Return native DateInterval PHP object matching the current instance.
     *
     * @example
     * ```
     * var_dump(CarbonInterval::hours(2)->toDateInterval());
     * ```
     */
    public function to_date_interval(): DateInterval
    {
        return self::cast_interval_to_class($this, DateInterval::class);
    }
    /**
     * Convert the interval to a CarbonPeriod.
     *
     * @param DateTimeInterface|string|int ...$params Start date, [end date or recurrences] and optional settings.
     */
    public function to_period(...$params): Carbon_Period
    {
        if ($this->timezone_setting) {
            $time_zone = \is_string($this->timezone_setting) ? new DateTimeZone($this->timezone_setting) : $this->timezone_setting;
            if ($time_zone instanceof DateTimeZone) {
                array_unshift($params, $time_zone);
            }
        }
        $class = ($params[0] ?? null) instanceof DateTime ? Carbon_Period::class : Carbon_Period_Immutable::class;
        return $class::create($this, ...$params);
    }
    /**
     * Decompose the current interval into
     *
     * @param mixed|int|DateInterval|string|Closure|Unit|null $interval interval or number of the given $unit
     * @param Unit|string|null                                $unit     if specified, $interval must be an integer
     */
    public function step_by($interval, Unit|string|null $unit = null): Carbon_Period
    {
        $this->check_start_and_end();
        $start = $this->start_date ?? Carbon_Immutable::make('now');
        $end = $this->end_date ?? $start->copy()->add($this);
        try {
            $step = static::make($interval, $unit);
        } catch (Invalid_Format_Exception $exception) {
            if ($unit || (\is_string($interval) ? preg_match('/(\s|\d)/', $interval) : !$interval instanceof Unit)) {
                throw $exception;
            }
            $step = static::make(1, $interval);
        }
        $class = $start instanceof DateTime ? Carbon_Period::class : Carbon_Period_Immutable::class;
        return $class::create($step, $start, $end);
    }
    /**
     * Invert the interval.
     *
     * @param bool|int $inverted if a parameter is passed, the passed value cast as 1 or 0 is used
     *                           as the new value of the ->invert property.
     *
     * @return $this
     */
    public function invert($inverted = null): static
    {
        $this->invert = (\func_num_args() === 0 ? !$this->invert : $inverted) ? 1 : 0;
        return $this;
    }
    protected function solve_negative_interval(): static
    {
        if (!$this->is_empty() && $this->years <= 0 && $this->months <= 0 && $this->dayz <= 0 && $this->hours <= 0 && $this->minutes <= 0 && $this->seconds <= 0 && $this->microseconds <= 0) {
            $this->years *= self::NEGATIVE;
            $this->months *= self::NEGATIVE;
            $this->dayz *= self::NEGATIVE;
            $this->hours *= self::NEGATIVE;
            $this->minutes *= self::NEGATIVE;
            $this->seconds *= self::NEGATIVE;
            $this->microseconds *= self::NEGATIVE;
            $this->invert();
        }
        return $this;
    }
    /**
     * Add the passed interval to the current instance.
     *
     * @param string|DateInterval $unit
     * @param int|float           $value
     *
     * @return $this
     */
    public function add($unit, $value = 1): static
    {
        if (is_numeric($unit)) {
            [$value, $unit] = [$unit, $value];
        }
        if (\is_string($unit) && !preg_match('/^\s*-?\d/', $unit)) {
            $unit = "{$value} {$unit}";
            $value = 1;
        }
        $interval = static::make($unit);
        if (!$interval) {
            throw new Invalid_Interval_Exception('This type of data cannot be added/subtracted.');
        }
        if ($value !== 1) {
            $interval->times($value);
        }
        $sign = ($this->invert === 1) !== ($interval->invert === 1) ? self::NEGATIVE : self::POSITIVE;
        $this->years += $interval->y * $sign;
        $this->months += $interval->m * $sign;
        $this->dayz += ($interval->days === false ? $interval->d : $interval->days) * $sign;
        $this->hours += $interval->h * $sign;
        $this->minutes += $interval->i * $sign;
        $this->seconds += $interval->s * $sign;
        $this->microseconds += $interval->microseconds * $sign;
        $this->solve_negative_interval();
        return $this;
    }
    /**
     * Subtract the passed interval to the current instance.
     *
     * @param string|DateInterval $unit
     * @param int|float           $value
     *
     * @return $this
     */
    public function sub($unit, $value = 1): static
    {
        if (is_numeric($unit)) {
            [$value, $unit] = [$unit, $value];
        }
        return $this->add($unit, -(float) $value);
    }
    /**
     * Subtract the passed interval to the current instance.
     *
     * @param string|DateInterval $unit
     * @param int|float           $value
     *
     * @return $this
     */
    public function subtract($unit, $value = 1): static
    {
        return $this->sub($unit, $value);
    }
    /**
     * Add given parameters to the current interval.
     *
     * @param int       $years
     * @param int       $months
     * @param int|float $weeks
     * @param int|float $days
     * @param int|float $hours
     * @param int|float $minutes
     * @param int|float $seconds
     * @param int|float $microseconds
     *
     * @return $this
     */
    public function plus($years = 0, $months = 0, $weeks = 0, $days = 0, $hours = 0, $minutes = 0, $seconds = 0, $microseconds = 0): static
    {
        return $this->add("\n            {$years} years {$months} months {$weeks} weeks {$days} days\n            {$hours} hours {$minutes} minutes {$seconds} seconds {$microseconds} microseconds\n        ");
    }
    /**
     * Add given parameters to the current interval.
     *
     * @param int       $years
     * @param int       $months
     * @param int|float $weeks
     * @param int|float $days
     * @param int|float $hours
     * @param int|float $minutes
     * @param int|float $seconds
     * @param int|float $microseconds
     *
     * @return $this
     */
    public function minus($years = 0, $months = 0, $weeks = 0, $days = 0, $hours = 0, $minutes = 0, $seconds = 0, $microseconds = 0): static
    {
        return $this->sub("\n            {$years} years {$months} months {$weeks} weeks {$days} days\n            {$hours} hours {$minutes} minutes {$seconds} seconds {$microseconds} microseconds\n        ");
    }
    /**
     * Multiply current instance given number of times. times() is naive, it multiplies each unit
     * (so day can be greater than 31, hour can be greater than 23, etc.) and the result is rounded
     * separately for each unit.
     *
     * Use times() when you want a fast and approximated calculation that does not cascade units.
     *
     * For a precise and cascaded calculation,
     *
     * @see multiply()
     *
     * @param float|int $factor
     *
     * @return $this
     */
    public function times($factor): static
    {
        if ($factor < 0) {
            $this->invert = $this->invert ? 0 : 1;
            $factor = -$factor;
        }
        $this->years = (int) round($this->years * $factor);
        $this->months = (int) round($this->months * $factor);
        $this->dayz = (int) round($this->dayz * $factor);
        $this->hours = (int) round($this->hours * $factor);
        $this->minutes = (int) round($this->minutes * $factor);
        $this->seconds = (int) round($this->seconds * $factor);
        $this->microseconds = (int) round($this->microseconds * $factor);
        return $this;
    }
    /**
     * Divide current instance by a given divider. shares() is naive, it divides each unit separately
     * and the result is rounded for each unit. So 5 hours and 20 minutes shared by 3 becomes 2 hours
     * and 7 minutes.
     *
     * Use shares() when you want a fast and approximated calculation that does not cascade units.
     *
     * For a precise and cascaded calculation,
     *
     * @see divide()
     *
     * @param float|int $divider
     *
     * @return $this
     */
    public function shares($divider): static
    {
        return $this->times(1 / $divider);
    }
    protected function copy_properties(self $interval, $ignore_sign = false): static
    {
        $this->years = $interval->years;
        $this->months = $interval->months;
        $this->dayz = $interval->dayz;
        $this->hours = $interval->hours;
        $this->minutes = $interval->minutes;
        $this->seconds = $interval->seconds;
        $this->microseconds = $interval->microseconds;
        if (!$ignore_sign) {
            $this->invert = $interval->invert;
        }
        return $this;
    }
    /**
     * Multiply and cascade current instance by a given factor.
     *
     * @param float|int $factor
     *
     * @return $this
     */
    public function multiply($factor): static
    {
        if ($factor < 0) {
            $this->invert = $this->invert ? 0 : 1;
            $factor = -$factor;
        }
        $year_part = (int) floor($this->years * $factor);
        // Split calculation to prevent imprecision
        if ($year_part) {
            $this->years -= $year_part / $factor;
        }
        return $this->copy_properties(static::create($year_part)->microseconds(abs($this->total_microseconds) * $factor)->cascade(), true);
    }
    /**
     * Divide and cascade current instance by a given divider.
     *
     * @param float|int $divider
     *
     * @return $this
     */
    public function divide($divider): static
    {
        return $this->multiply(1 / $divider);
    }
    /**
     * Get the interval_spec string of a date interval.
     *
     *
     */
    public static function get_date_interval_spec(DateInterval $interval, bool $microseconds = false, array $skip = []): string
    {
        $date = array_filter([static::PERIOD_YEARS => abs($interval->y), static::PERIOD_MONTHS => abs($interval->m), static::PERIOD_DAYS => abs($interval->d)]);
        $skip = array_map(Unit::to_name_if_unit(...), $skip);
        if ($interval->days >= Carbon_Interface::DAYS_PER_WEEK * Carbon_Interface::WEEKS_PER_MONTH && (!isset($date[static::PERIOD_YEARS]) || \count(array_intersect(['y', 'year', 'years'], $skip))) && (!isset($date[static::PERIOD_MONTHS]) || \count(array_intersect(['m', 'month', 'months'], $skip)))) {
            $date = [static::PERIOD_DAYS => abs($interval->days)];
        }
        $seconds = abs($interval->s);
        if ($microseconds && $interval->f > 0) {
            $seconds = \sprintf('%d.%06d', $seconds, abs($interval->f) * 1000000);
        }
        $time = array_filter([static::PERIOD_HOURS => abs($interval->h), static::PERIOD_MINUTES => abs($interval->i), static::PERIOD_SECONDS => $seconds]);
        $spec_string = static::PERIOD_PREFIX;
        foreach ($date as $key => $value) {
            $spec_string .= $value . $key;
        }
        if (\count($time) > 0) {
            $spec_string .= static::PERIOD_TIME_PREFIX;
            foreach ($time as $key => $value) {
                $spec_string .= $value . $key;
            }
        }
        return $spec_string === static::PERIOD_PREFIX ? 'PT0S' : $spec_string;
    }
    /**
     * Get the interval_spec string.
     */
    public function spec(bool $microseconds = false): string
    {
        return static::get_date_interval_spec($this, $microseconds);
    }
    /**
     * Comparing 2 date intervals.
     *
     *
     * @return int 0, 1 or -1
     */
    public static function compare_date_intervals(DateInterval $first, DateInterval $second): int
    {
        $current = Carbon::now();
        $passed = $current->avoid_mutation()->add($second);
        $current->add($first);
        return $current <=> $passed;
    }
    /**
     * Comparing with passed interval.
     *
     *
     * @return int 0, 1 or -1
     */
    public function compare(DateInterval $interval): int
    {
        return static::compare_date_intervals($this, $interval);
    }
    /**
     * Convert overflowed values into bigger units.
     *
     * @return $this
     */
    public function cascade(): static
    {
        return $this->do_cascade(false);
    }
    public function has_negative_values(): bool
    {
        foreach ($this->to_array() as $value) {
            if ($value < 0) {
                return true;
            }
        }
        return false;
    }
    public function has_positive_values(): bool
    {
        foreach ($this->to_array() as $value) {
            if ($value > 0) {
                return true;
            }
        }
        return false;
    }
    /**
     * Get amount of given unit equivalent to the interval.
     *
     *
     * @throws UnknownUnitException|UnitNotConfiguredException
     *
     */
    public function total(string $unit): float
    {
        $real_unit = $unit = strtolower($unit);
        if (\in_array($unit, ['days', 'weeks'])) {
            $real_unit = 'dayz';
        } elseif (!\in_array($unit, ['microseconds', 'milliseconds', 'seconds', 'minutes', 'hours', 'dayz', 'months', 'years'])) {
            throw new Unknown_Unit_Exception($unit);
        }
        $this->check_start_and_end();
        if ($this->start_date && $this->end_date) {
            $diff = $this->start_date->diff_in_unit($unit, $this->end_date);
            return $this->absolute ? abs($diff) : $diff;
        }
        $result = 0;
        $cumulative_factor = 0;
        $unit_found = false;
        $factors = self::get_flip_cascade_factors();
        $days_per_week = (int) static::get_days_per_week();
        $values = ['years' => $this->years, 'months' => $this->months, 'weeks' => (int) ($this->d / $days_per_week), 'dayz' => fmod($this->d, $days_per_week), 'hours' => $this->hours, 'minutes' => $this->minutes, 'seconds' => $this->seconds, 'milliseconds' => (int) ($this->microseconds / Carbon::MICROSECONDS_PER_MILLISECOND), 'microseconds' => $this->microseconds % Carbon::MICROSECONDS_PER_MILLISECOND];
        if (isset($factors['dayz']) && $factors['dayz'][0] !== 'weeks') {
            $values['dayz'] += $values['weeks'] * $days_per_week;
            $values['weeks'] = 0;
        }
        foreach ($factors as $source => [$target, $factor]) {
            if ($source === $real_unit) {
                $unit_found = true;
                $value = $values[$source];
                $result += $value;
                $cumulative_factor = 1;
            }
            if ($factor === false) {
                if ($unit_found) {
                    break;
                }
                $result = 0;
                $cumulative_factor = 0;
                continue;
            }
            if ($target === $real_unit) {
                $unit_found = true;
            }
            if ($cumulative_factor) {
                $cumulative_factor *= $factor;
                $result += $values[$target] * $cumulative_factor;
                continue;
            }
            $value = $values[$source];
            $result = ($result + $value) / $factor;
        }
        if (isset($target) && !$cumulative_factor) {
            $result += $values[$target];
        }
        if (!$unit_found) {
            throw new Unit_Not_Configured_Exception($unit);
        }
        if ($this->invert) {
            $result *= self::NEGATIVE;
        }
        if ($unit === 'weeks') {
            $result /= $days_per_week;
        }
        // Cast as int numbers with no decimal part
        return fmod($result, 1) === 0.0 ? (int) $result : $result;
    }
    /**
     * Determines if the instance is equal to another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     *
     * @see equalTo()
     */
    public function eq($interval): bool
    {
        return $this->equal_to($interval);
    }
    /**
     * Determines if the instance is equal to another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     */
    public function equal_to($interval): bool
    {
        $interval = $this->resolve_interval($interval);
        if ($interval === null) {
            return false;
        }
        $step = $this->get_step();
        if ($step) {
            return $step === $interval->get_step();
        }
        if ($this->is_empty()) {
            return $interval->is_empty();
        }
        $cascaded_interval = $this->copy()->cascade();
        $compared_interval = $interval->copy()->cascade();
        return $cascaded_interval->invert === $compared_interval->invert && $cascaded_interval->get_non_zero_values() === $compared_interval->get_non_zero_values();
    }
    /**
     * Determines if the instance is not equal to another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     *
     * @see notEqualTo()
     */
    public function ne($interval): bool
    {
        return $this->not_equal_to($interval);
    }
    /**
     * Determines if the instance is not equal to another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     */
    public function not_equal_to($interval): bool
    {
        return !$this->eq($interval);
    }
    /**
     * Determines if the instance is greater (longer) than another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     *
     * @see greaterThan()
     */
    public function gt($interval): bool
    {
        return $this->greater_than($interval);
    }
    /**
     * Determines if the instance is greater (longer) than another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     */
    public function greater_than($interval): bool
    {
        $interval = $this->resolve_interval($interval);
        return $interval === null || $this->total_microseconds > $interval->total_microseconds;
    }
    /**
     * Determines if the instance is greater (longer) than or equal to another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     *
     * @see greaterThanOrEqualTo()
     */
    public function gte($interval): bool
    {
        return $this->greater_than_or_equal_to($interval);
    }
    /**
     * Determines if the instance is greater (longer) than or equal to another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     */
    public function greater_than_or_equal_to($interval): bool
    {
        if ($this->greater_than($interval)) {
            return true;
        }
        return $this->equal_to($interval);
    }
    /**
     * Determines if the instance is less (shorter) than another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     *
     * @see lessThan()
     */
    public function lt($interval): bool
    {
        return $this->less_than($interval);
    }
    /**
     * Determines if the instance is less (shorter) than another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     */
    public function less_than($interval): bool
    {
        $interval = $this->resolve_interval($interval);
        return $interval !== null && $this->total_microseconds < $interval->total_microseconds;
    }
    /**
     * Determines if the instance is less (shorter) than or equal to another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     *
     * @see lessThanOrEqualTo()
     */
    public function lte($interval): bool
    {
        return $this->less_than_or_equal_to($interval);
    }
    /**
     * Determines if the instance is less (shorter) than or equal to another
     *
     * @param CarbonInterval|DateInterval|mixed $interval
     */
    public function less_than_or_equal_to($interval): bool
    {
        if ($this->less_than($interval)) {
            return true;
        }
        return $this->equal_to($interval);
    }
    /**
     * Determines if the instance is between two others.
     *
     * The third argument allow you to specify if bounds are included or not (true by default)
     * but for when you including/excluding bounds may produce different results in your application,
     * we recommend to use the explicit methods ->betweenIncluded() or ->betweenExcluded() instead.
     *
     * @example
     * ```
     * CarbonInterval::hours(48)->between(CarbonInterval::day(), CarbonInterval::days(3)); // true
     * CarbonInterval::hours(48)->between(CarbonInterval::day(), CarbonInterval::hours(36)); // false
     * CarbonInterval::hours(48)->between(CarbonInterval::day(), CarbonInterval::days(2)); // true
     * CarbonInterval::hours(48)->between(CarbonInterval::day(), CarbonInterval::days(2), false); // false
     * ```
     *
     * @param CarbonInterval|DateInterval|mixed $interval1
     * @param CarbonInterval|DateInterval|mixed $interval2
     * @param bool                              $equal     Indicates if an equal to comparison should be done
     */
    public function between($interval1, $interval2, bool $equal = true): bool
    {
        return $equal ? $this->greater_than_or_equal_to($interval1) && $this->less_than_or_equal_to($interval2) : $this->greater_than($interval1) && $this->less_than($interval2);
    }
    /**
     * Determines if the instance is between two others, bounds excluded.
     *
     * @example
     * ```
     * CarbonInterval::hours(48)->betweenExcluded(CarbonInterval::day(), CarbonInterval::days(3)); // true
     * CarbonInterval::hours(48)->betweenExcluded(CarbonInterval::day(), CarbonInterval::hours(36)); // false
     * CarbonInterval::hours(48)->betweenExcluded(CarbonInterval::day(), CarbonInterval::days(2)); // true
     * ```
     *
     * @param CarbonInterval|DateInterval|mixed $interval1
     * @param CarbonInterval|DateInterval|mixed $interval2
     */
    public function between_included($interval1, $interval2): bool
    {
        return $this->between($interval1, $interval2, true);
    }
    /**
     * Determines if the instance is between two others, bounds excluded.
     *
     * @example
     * ```
     * CarbonInterval::hours(48)->betweenExcluded(CarbonInterval::day(), CarbonInterval::days(3)); // true
     * CarbonInterval::hours(48)->betweenExcluded(CarbonInterval::day(), CarbonInterval::hours(36)); // false
     * CarbonInterval::hours(48)->betweenExcluded(CarbonInterval::day(), CarbonInterval::days(2)); // false
     * ```
     *
     * @param CarbonInterval|DateInterval|mixed $interval1
     * @param CarbonInterval|DateInterval|mixed $interval2
     */
    public function between_excluded($interval1, $interval2): bool
    {
        return $this->between($interval1, $interval2, false);
    }
    /**
     * Determines if the instance is between two others
     *
     * @example
     * ```
     * CarbonInterval::hours(48)->isBetween(CarbonInterval::day(), CarbonInterval::days(3)); // true
     * CarbonInterval::hours(48)->isBetween(CarbonInterval::day(), CarbonInterval::hours(36)); // false
     * CarbonInterval::hours(48)->isBetween(CarbonInterval::day(), CarbonInterval::days(2)); // true
     * CarbonInterval::hours(48)->isBetween(CarbonInterval::day(), CarbonInterval::days(2), false); // false
     * ```
     *
     * @param CarbonInterval|DateInterval|mixed $interval1
     * @param CarbonInterval|DateInterval|mixed $interval2
     * @param bool                              $equal     Indicates if an equal to comparison should be done
     */
    public function is_between($interval1, $interval2, bool $equal = true): bool
    {
        return $this->between($interval1, $interval2, $equal);
    }
    /**
     * Round the current instance at the given unit with given precision if specified and the given function.
     *
     * @throws Exception
     */
    public function round_unit(string $unit, DateInterval|string|int|float $precision = 1, string $function = 'round'): static
    {
        if (static::get_cascade_factors() !== static::get_default_cascade_factors()) {
            $value = $function($this->total($unit) / $precision) * $precision;
            $inverted = $value < 0;
            return $this->copy_properties(self::from_string(number_format(abs($value), 12, '.', '') . ' ' . $unit)->invert($inverted)->cascade());
        }
        $base = Carbon_Immutable::parse('2000-01-01 00:00:00', 'UTC')->round_unit($unit, $precision, $function);
        $next = $base->add($this);
        $inverted = $next < $base;
        if ($inverted) {
            $next = $base->sub($this);
        }
        $this->copy_properties($next->round_unit($unit, $precision, $function)->diff($base));
        return $this->invert($inverted);
    }
    /**
     * Truncate the current instance at the given unit with given precision if specified.
     *
     * @param float|int|string|DateInterval|null $precision
     *
     * @throws Exception
     * @return $this
     */
    public function floor_unit(string $unit, \DateInterval|string|int|float $precision = 1): static
    {
        return $this->round_unit($unit, $precision, 'floor');
    }
    /**
     * Ceil the current instance at the given unit with given precision if specified.
     *
     * @param float|int|string|DateInterval|null $precision
     *
     * @throws Exception
     * @return $this
     */
    public function ceil_unit(string $unit, \DateInterval|string|int|float $precision = 1): static
    {
        return $this->round_unit($unit, $precision, 'ceil');
    }
    /**
     * Round the current instance second with given precision if specified.
     *
     * @param float|int|string|DateInterval|null $precision
     *
     * @throws Exception
     * @return $this
     */
    public function round(\DateInterval|string|float|int $precision = 1, string $function = 'round'): static
    {
        return $this->round_with($precision, $function);
    }
    /**
     * Round the current instance second with given precision if specified.
     *
     * @throws Exception
     *
     * @return $this
     */
    public function floor(DateInterval|string|float|int $precision = 1): static
    {
        return $this->round($precision, 'floor');
    }
    /**
     * Ceil the current instance second with given precision if specified.
     *
     * @throws Exception
     *
     * @return $this
     */
    public function ceil(DateInterval|string|float|int $precision = 1): static
    {
        return $this->round($precision, 'ceil');
    }
    public function __unserialize(array $data): void
    {
        $properties = array_combine(array_map(static fn(mixed $key): string|int|array => \is_string($key) ? str_replace('tzName', 'timezoneSetting', $key) : $key, array_keys($data)), $data);
        if (method_exists(parent::class, '__unserialize')) {
            // PHP >= 8.2
            parent::__unserialize($properties);
            return;
        }
        // PHP <= 8.1
        // @codeCoverageIgnoreStart
        $properties = array_combine(array_map(static fn(string $property): ?string => preg_replace('/^\0.+\0/', '', $property), array_keys($data)), $data);
        $local_strict_mode = $this->local_strict_mode_enabled;
        $this->local_strict_mode_enabled = false;
        $days = $properties['days'] ?? false;
        $this->days = $days === false ? false : (int) $days;
        $this->y = (int) ($properties['y'] ?? 0);
        $this->m = (int) ($properties['m'] ?? 0);
        $this->d = (int) ($properties['d'] ?? 0);
        $this->h = (int) ($properties['h'] ?? 0);
        $this->i = (int) ($properties['i'] ?? 0);
        $this->s = (int) ($properties['s'] ?? 0);
        $this->f = (float) ($properties['f'] ?? 0.0);
        // @phpstan-ignore-next-line
        $this->weekday = (int) ($properties['weekday'] ?? 0);
        // @phpstan-ignore-next-line
        $this->weekday_behavior = (int) ($properties['weekday_behavior'] ?? 0);
        // @phpstan-ignore-next-line
        $this->first_last_day_of = (int) ($properties['first_last_day_of'] ?? 0);
        $this->invert = (int) ($properties['invert'] ?? 0);
        // @phpstan-ignore-next-line
        $this->special_type = (int) ($properties['special_type'] ?? 0);
        // @phpstan-ignore-next-line
        $this->special_amount = (int) ($properties['special_amount'] ?? 0);
        // @phpstan-ignore-next-line
        $this->have_weekday_relative = (int) ($properties['have_weekday_relative'] ?? 0);
        // @phpstan-ignore-next-line
        $this->have_special_relative = (int) ($properties['have_special_relative'] ?? 0);
        parent::__construct(self::get_date_interval_spec($this));
        foreach ($properties as $property => $value) {
            if ($property === 'localStrictModeEnabled') {
                continue;
            }
            $this->{$property} = $value;
        }
        $this->local_strict_mode_enabled = $properties['localStrictModeEnabled'] ?? $local_strict_mode;
        // @codeCoverageIgnoreEnd
    }
    /**
     * @template T
     *
     * @param T     $interval
     *
     * @return T
     */
    private static function with_original(mixed $interval, mixed $original): mixed
    {
        if ($interval instanceof self) {
            $interval->original_input = $original;
        }
        return $interval;
    }
    private static function standardize_unit(string $unit): string
    {
        $unit = rtrim($unit, 'sz') . 's';
        return $unit === 'days' ? 'dayz' : $unit;
    }
    private static function get_flip_cascade_factors(): array
    {
        if (!self::$flip_cascade_factors) {
            self::$flip_cascade_factors = [];
            foreach (self::get_cascade_factors() as $to => [$factor, $from]) {
                self::$flip_cascade_factors[self::standardize_unit($from)] = [self::standardize_unit($to), $factor];
            }
        }
        return self::$flip_cascade_factors;
    }
    /**
     * @template T of DateInterval
     *
     *
     * @psalm-param class-string<T> $className
     * @return T
     */
    private static function cast_interval_to_class(DateInterval $interval, string $class_name, array $skip = []): object
    {
        $main_class = DateInterval::class;
        if (!is_a($class_name, $main_class, true)) {
            throw new Invalid_Cast_Exception("{$class_name} is not a sub-class of {$main_class}.");
        }
        $microseconds = $interval->f;
        $instance = self::build_instance($interval, $class_name, $skip);
        if ($instance instanceof self) {
            $instance->original_input = $interval;
        }
        if ($microseconds) {
            $instance->f = $microseconds;
        }
        if ($interval instanceof self && is_a($class_name, self::class, true)) {
            self::copy_step($interval, $instance);
        }
        self::copy_negative_units($interval, $instance);
        return self::with_original($instance, $interval);
    }
    /**
     * @template T of DateInterval
     *
     *
     * @psalm-param class-string<T> $className
     * @return T
     */
    private static function build_instance(DateInterval $interval, string $class_name, array $skip = []): object
    {
        $serialization = self::build_serialization_string($interval, $class_name, $skip);
        return match ($serialization) {
            null => new $class_name(static::get_date_interval_spec($interval, false, $skip)),
            default => unserialize($serialization),
        };
    }
    /**
     * As demonstrated by rlanvin (https://github.com/rlanvin) in
     * https://github.com/briannesbitt/Carbon/issues/3018#issuecomment-2888538438
     *
     * Modifying the output of serialize() to change the class name and unserializing
     * the tweaked string allows creating new interval instances where the ->days
     * property can be set. It's not possible neither with `new` nto with `__set_state`.
     *
     * It has a non-negligible performance cost, so we'll use this method only if
     * $interval->days !== false.
     */
    private static function build_serialization_string(DateInterval $interval, string $class_name, array $skip = []): ?string
    {
        if ($interval->days === false || PHP_VERSION_ID < 80200 || $skip !== []) {
            return null;
        }
        // De-enhance CarbonInterval objects to be serializable back to DateInterval
        if ($interval instanceof self && !is_a($class_name, self::class, true)) {
            $interval = clone $interval;
            unset($interval->timezone_setting);
            unset($interval->original_input);
            unset($interval->start_date);
            unset($interval->end_date);
            unset($interval->raw_interval);
            unset($interval->absolute);
            unset($interval->initial_values);
            unset($interval->clock);
            unset($interval->step);
            unset($interval->local_months_overflow);
            unset($interval->local_years_overflow);
            unset($interval->local_strict_mode_enabled);
            unset($interval->local_human_diff_options);
            unset($interval->local_to_string_format);
            unset($interval->local_serializer);
            unset($interval->local_macros);
            unset($interval->local_generic_macros);
            unset($interval->local_format_function);
            unset($interval->local_translator);
        }
        $serialization = serialize($interval);
        $input_class = $interval::class;
        $expected_start = 'O:' . \strlen($input_class) . ':"' . $input_class . '":';
        if (!str_starts_with($serialization, $expected_start)) {
            return null;
            // @codeCoverageIgnore
        }
        return 'O:' . \strlen($class_name) . ':"' . $class_name . '":' . substr($serialization, \strlen($expected_start));
    }
    private static function copy_step(self $from, self $to): void
    {
        $to->set_step($from->get_step());
    }
    private static function copy_negative_units(DateInterval $from, DateInterval $to): void
    {
        $to->invert = $from->invert;
        foreach (['y', 'm', 'd', 'h', 'i', 's'] as $unit) {
            if ($from->{$unit} < 0) {
                self::set_interval_unit($to, $unit, $to->{$unit} * self::NEGATIVE);
            }
        }
    }
    private function invert_cascade(array $values): static
    {
        return $this->set(array_map(fn($value) => -$value, $values))->do_cascade(true)->invert();
    }
    private function do_cascade(bool $deep): static
    {
        $original_data = $this->to_array();
        $original_data['milliseconds'] = (int) ($original_data['microseconds'] / static::get_microseconds_per_millisecond());
        $original_data['microseconds'] = $original_data['microseconds'] % static::get_microseconds_per_millisecond();
        $original_data['weeks'] = (int) ($this->d / static::get_days_per_week());
        $original_data['daysExcludeWeeks'] = fmod($this->d, static::get_days_per_week());
        unset($original_data['days']);
        $new_data = $original_data;
        $previous = [];
        foreach (self::get_flip_cascade_factors() as $source => [$target, $factor]) {
            foreach (['source', 'target'] as $key) {
                if (${$key} === 'dayz') {
                    ${$key} = 'daysExcludeWeeks';
                }
            }
            $value = $new_data[$source];
            $modulo = fmod($factor + fmod($value, $factor), $factor);
            $new_data[$source] = $modulo;
            $new_data[$target] += ($value - $modulo) / $factor;
            $decimal_part = fmod($new_data[$source], 1);
            if ($decimal_part !== 0.0) {
                $unit = $source;
                foreach ($previous as [$sub_unit, $sub_factor]) {
                    $new_data[$unit] -= $decimal_part;
                    $new_data[$sub_unit] += $decimal_part * $sub_factor;
                    $decimal_part = fmod($new_data[$sub_unit], 1);
                    if ($decimal_part === 0.0) {
                        break;
                    }
                    $unit = $sub_unit;
                }
            }
            array_unshift($previous, [$source, $factor]);
        }
        $positive = null;
        if (!$deep) {
            foreach ($new_data as $value) {
                if ($value) {
                    if ($positive === null) {
                        $positive = $value > 0;
                        continue;
                    }
                    if ($value > 0 !== $positive) {
                        return $this->invert_cascade($original_data)->solve_negative_interval();
                    }
                }
            }
        }
        return $this->set($new_data)->solve_negative_interval();
    }
    private function needs_declension(string $mode, int $index, int $parts): bool
    {
        return match ($mode) {
            'last' => $index === $parts - 1,
            default => true,
        };
    }
    private function check_integer_value(string $name, mixed $value): void
    {
        if (\is_int($value)) {
            return;
        }
        $this->assert_safe_for_integer($name, $value);
        if (\is_float($value) && (float) (int) $value === $value) {
            return;
        }
        if (!self::$float_setters_enabled) {
            $type = \gettype($value);
            @trigger_error("Since 2.70.0, it's deprecated to pass {$type} value for {$name}.\n" . "It's truncated when stored as an integer interval unit.\n" . "From 3.0.0, decimal part will no longer be truncated and will be cascaded to smaller units.\n" . "- To maintain the current behavior, use explicit cast: {$name}((int) \$value)\n" . "- To adopt the new behavior globally, call CarbonInterval::enableFloatSetters()\n", \E_USER_DEPRECATED);
        }
    }
    /**
     * Throw an exception if precision loss when storing the given value as an integer would be >= 1.0.
     */
    private function assert_safe_for_integer(string $name, mixed $value): void
    {
        if ($value && !\is_int($value) && ($value >= 0x7fffffffffffffff || $value <= -0x7fffffffffffffff)) {
            throw new OutOfRangeException($name, -0x7fffffffffffffff, 0x7fffffffffffffff, $value);
        }
    }
    private function handle_decimal_part(string $unit, mixed $value, mixed $integer_value): void
    {
        if (self::$float_setters_enabled) {
            $float_value = (float) $value;
            $base = (float) $integer_value;
            if ($float_value === $base) {
                return;
            }
            $units = ['y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
            $upper = true;
            foreach ($units as $property => $name) {
                if ($name === $unit) {
                    $upper = false;
                    continue;
                }
                if (!$upper && $this->{$property} !== 0) {
                    throw new RuntimeException("You cannot set {$unit} to a float value as {$name} would be overridden, " . 'set it first to 0 explicitly if you really want to erase its value');
                }
            }
            $this->add($unit, $float_value - $base);
        }
    }
    private function get_inner_values(): array
    {
        return [$this->y, $this->m, $this->d, $this->h, $this->i, $this->s, $this->f, $this->invert, $this->days];
    }
    private function check_start_and_end(): void
    {
        if ($this->initial_values !== null && ($this->start_date !== null || $this->end_date !== null) && $this->initial_values !== $this->get_inner_values()) {
            $this->absolute = false;
            $this->start_date = null;
            $this->end_date = null;
            $this->raw_interval = null;
        }
    }
    /** @return $this */
    private function set_setting(string $setting, mixed $value): self
    {
        switch ($setting) {
            case 'timezoneSetting':
                return $value === null ? $this : $this->set_timezone($value);
            case 'step':
                $this->set_step($value);
                return $this;
            case 'localMonthsOverflow':
                return $value === null ? $this : $this->settings(['monthOverflow' => $value]);
            case 'localYearsOverflow':
                return $value === null ? $this : $this->settings(['yearOverflow' => $value]);
            case 'localStrictModeEnabled':
            case 'localHumanDiffOptions':
            case 'localToStringFormat':
            case 'localSerializer':
            case 'localMacros':
            case 'localGenericMacros':
            case 'localFormatFunction':
            case 'localTranslator':
                $this->{$setting} = $value;
                return $this;
            default:
                // Drop unknown settings
                return $this;
        }
    }
    private static function increment_unit(DateInterval $instance, string $unit, int $value): void
    {
        if ($value === 0) {
            return;
        }
        // @codeCoverageIgnoreStart
        if (PHP_VERSION_ID !== 80320) {
            $instance->{$unit} += $value;
            return;
        }
        // Cannot use +=, nor set to a negative value directly as it segfaults in PHP 8.3.20
        self::set_interval_unit($instance, $unit, ($instance->{$unit} ?? 0) + $value);
        // @codeCoverageIgnoreEnd
    }
    /** @codeCoverageIgnore */
    private static function set_interval_unit(DateInterval $instance, string $unit, mixed $value): void
    {
        switch ($unit) {
            case 'y':
                $instance->y = $value;
                break;
            case 'm':
                $instance->m = $value;
                break;
            case 'd':
                $instance->d = $value;
                break;
            case 'h':
                $instance->h = $value;
                break;
            case 'i':
                $instance->i = $value;
                break;
            case 's':
                $instance->s = $value;
                break;
            default:
                $instance->{$unit} = $value;
        }
    }
}