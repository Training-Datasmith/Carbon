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
use Carbon\Exceptions\End_Less_Period_Exception;
use Carbon\Exceptions\Invalid_Cast_Exception;
use Carbon\Exceptions\Invalid_Interval_Exception;
use Carbon\Exceptions\Invalid_Period_Date_Exception;
use Carbon\Exceptions\Invalid_Period_Parameter_Exception;
use Carbon\Exceptions\Not_A_Carbon_Class_Exception;
use Carbon\Exceptions\Not_A_Period_Exception;
use Carbon\Exceptions\Unknown_Getter_Exception;
use Carbon\Exceptions\Unknown_Method_Exception;
use Carbon\Exceptions\Unreachable_Exception;
use Carbon\Traits\Deprecated_Period_Properties;
use Carbon\Traits\Interval_Rounding;
use Carbon\Traits\Local_Factory;
use Carbon\Traits\Mixin;
use Carbon\Traits\Options;
use Carbon\Traits\To_String_Format;
use Closure;
use Countable;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use InvalidArgumentException;
use JsonSerializable;
use Reflection_Exception;
use Return_Type_Will_Change;
use RuntimeException;
use Throwable;
// @codeCoverageIgnoreStart
require PHP_VERSION < 8.199999999999999 ? __DIR__ . '/../../lazy/Carbon/ProtectedDatePeriod.php' : __DIR__ . '/../../lazy/Carbon/UnprotectedDatePeriod.php';
// @codeCoverageIgnoreEnd
/**
 * Substitution of DatePeriod with some modifications and many more features.
 *
 * @method static static|CarbonInterface start($date = null, $inclusive = null) Create instance specifying start date or modify the start date if called on an instance.
 * @method static static since($date = null, $inclusive = null) Alias for start().
 * @method static static sinceNow($inclusive = null) Create instance with start date set to now or set the start date to now if called on an instance.
 * @method static static|CarbonInterface end($date = null, $inclusive = null) Create instance specifying end date or modify the end date if called on an instance.
 * @method static static until($date = null, $inclusive = null) Alias for end().
 * @method static static untilNow($inclusive = null) Create instance with end date set to now or set the end date to now if called on an instance.
 * @method static static dates($start, $end = null) Create instance with start and end dates or modify the start and end dates if called on an instance.
 * @method static static between($start, $end = null) Create instance with start and end dates or modify the start and end dates if called on an instance.
 * @method static static recurrences($recurrences = null) Create instance with maximum number of recurrences or modify the number of recurrences if called on an instance.
 * @method static static times($recurrences = null) Alias for recurrences().
 * @method static static|int|null options($options = null) Create instance with options or modify the options if called on an instance.
 * @method static static toggle($options, $state = null) Create instance with options toggled on or off, or toggle options if called on an instance.
 * @method static static filter($callback, $name = null) Create instance with filter added to the stack or append a filter if called on an instance.
 * @method static static push($callback, $name = null) Alias for filter().
 * @method static static prepend($callback, $name = null) Create instance with filter prepended to the stack or prepend a filter if called on an instance.
 * @method static static|array filters(array $filters = []) Create instance with filters stack or replace the whole filters stack if called on an instance.
 * @method static static|CarbonInterval interval($interval = null) Create instance with given date interval or modify the interval if called on an instance.
 * @method static static each($interval) Create instance with given date interval or modify the interval if called on an instance.
 * @method static static every($interval) Create instance with given date interval or modify the interval if called on an instance.
 * @method static static step($interval) Create instance with given date interval or modify the interval if called on an instance.
 * @method static static stepBy($interval) Create instance with given date interval or modify the interval if called on an instance.
 * @method static static invert() Create instance with inverted date interval or invert the interval if called on an instance.
 * @method static static years($years = 1) Create instance specifying a number of years for date interval or replace the interval by the given a number of years if called on an instance.
 * @method static static year($years = 1) Alias for years().
 * @method static static months($months = 1) Create instance specifying a number of months for date interval or replace the interval by the given a number of months if called on an instance.
 * @method static static month($months = 1) Alias for months().
 * @method static static weeks($weeks = 1) Create instance specifying a number of weeks for date interval or replace the interval by the given a number of weeks if called on an instance.
 * @method static static week($weeks = 1) Alias for weeks().
 * @method static static days($days = 1) Create instance specifying a number of days for date interval or replace the interval by the given a number of days if called on an instance.
 * @method static static dayz($days = 1) Alias for days().
 * @method static static day($days = 1) Alias for days().
 * @method static static hours($hours = 1) Create instance specifying a number of hours for date interval or replace the interval by the given a number of hours if called on an instance.
 * @method static static hour($hours = 1) Alias for hours().
 * @method static static minutes($minutes = 1) Create instance specifying a number of minutes for date interval or replace the interval by the given a number of minutes if called on an instance.
 * @method static static minute($minutes = 1) Alias for minutes().
 * @method static static seconds($seconds = 1) Create instance specifying a number of seconds for date interval or replace the interval by the given a number of seconds if called on an instance.
 * @method static static second($seconds = 1) Alias for seconds().
 * @method static static milliseconds($milliseconds = 1) Create instance specifying a number of milliseconds for date interval or replace the interval by the given a number of milliseconds if called on an instance.
 * @method static static millisecond($milliseconds = 1) Alias for milliseconds().
 * @method static static microseconds($microseconds = 1) Create instance specifying a number of microseconds for date interval or replace the interval by the given a number of microseconds if called on an instance.
 * @method static static microsecond($microseconds = 1) Alias for microseconds().
 * @method $this roundYear(float $precision = 1, string $function = "round") Round the current instance year with given precision using the given function.
 * @method $this roundYears(float $precision = 1, string $function = "round") Round the current instance year with given precision using the given function.
 * @method $this floorYear(float $precision = 1) Truncate the current instance year with given precision.
 * @method $this floorYears(float $precision = 1) Truncate the current instance year with given precision.
 * @method $this ceilYear(float $precision = 1) Ceil the current instance year with given precision.
 * @method $this ceilYears(float $precision = 1) Ceil the current instance year with given precision.
 * @method $this roundMonth(float $precision = 1, string $function = "round") Round the current instance month with given precision using the given function.
 * @method $this roundMonths(float $precision = 1, string $function = "round") Round the current instance month with given precision using the given function.
 * @method $this floorMonth(float $precision = 1) Truncate the current instance month with given precision.
 * @method $this floorMonths(float $precision = 1) Truncate the current instance month with given precision.
 * @method $this ceilMonth(float $precision = 1) Ceil the current instance month with given precision.
 * @method $this ceilMonths(float $precision = 1) Ceil the current instance month with given precision.
 * @method $this roundWeek(float $precision = 1, string $function = "round") Round the current instance day with given precision using the given function.
 * @method $this roundWeeks(float $precision = 1, string $function = "round") Round the current instance day with given precision using the given function.
 * @method $this floorWeek(float $precision = 1) Truncate the current instance day with given precision.
 * @method $this floorWeeks(float $precision = 1) Truncate the current instance day with given precision.
 * @method $this ceilWeek(float $precision = 1) Ceil the current instance day with given precision.
 * @method $this ceilWeeks(float $precision = 1) Ceil the current instance day with given precision.
 * @method $this roundDay(float $precision = 1, string $function = "round") Round the current instance day with given precision using the given function.
 * @method $this roundDays(float $precision = 1, string $function = "round") Round the current instance day with given precision using the given function.
 * @method $this floorDay(float $precision = 1) Truncate the current instance day with given precision.
 * @method $this floorDays(float $precision = 1) Truncate the current instance day with given precision.
 * @method $this ceilDay(float $precision = 1) Ceil the current instance day with given precision.
 * @method $this ceilDays(float $precision = 1) Ceil the current instance day with given precision.
 * @method $this roundHour(float $precision = 1, string $function = "round") Round the current instance hour with given precision using the given function.
 * @method $this roundHours(float $precision = 1, string $function = "round") Round the current instance hour with given precision using the given function.
 * @method $this floorHour(float $precision = 1) Truncate the current instance hour with given precision.
 * @method $this floorHours(float $precision = 1) Truncate the current instance hour with given precision.
 * @method $this ceilHour(float $precision = 1) Ceil the current instance hour with given precision.
 * @method $this ceilHours(float $precision = 1) Ceil the current instance hour with given precision.
 * @method $this roundMinute(float $precision = 1, string $function = "round") Round the current instance minute with given precision using the given function.
 * @method $this roundMinutes(float $precision = 1, string $function = "round") Round the current instance minute with given precision using the given function.
 * @method $this floorMinute(float $precision = 1) Truncate the current instance minute with given precision.
 * @method $this floorMinutes(float $precision = 1) Truncate the current instance minute with given precision.
 * @method $this ceilMinute(float $precision = 1) Ceil the current instance minute with given precision.
 * @method $this ceilMinutes(float $precision = 1) Ceil the current instance minute with given precision.
 * @method $this roundSecond(float $precision = 1, string $function = "round") Round the current instance second with given precision using the given function.
 * @method $this roundSeconds(float $precision = 1, string $function = "round") Round the current instance second with given precision using the given function.
 * @method $this floorSecond(float $precision = 1) Truncate the current instance second with given precision.
 * @method $this floorSeconds(float $precision = 1) Truncate the current instance second with given precision.
 * @method $this ceilSecond(float $precision = 1) Ceil the current instance second with given precision.
 * @method $this ceilSeconds(float $precision = 1) Ceil the current instance second with given precision.
 * @method $this roundMillennium(float $precision = 1, string $function = "round") Round the current instance millennium with given precision using the given function.
 * @method $this roundMillennia(float $precision = 1, string $function = "round") Round the current instance millennium with given precision using the given function.
 * @method $this floorMillennium(float $precision = 1) Truncate the current instance millennium with given precision.
 * @method $this floorMillennia(float $precision = 1) Truncate the current instance millennium with given precision.
 * @method $this ceilMillennium(float $precision = 1) Ceil the current instance millennium with given precision.
 * @method $this ceilMillennia(float $precision = 1) Ceil the current instance millennium with given precision.
 * @method $this roundCentury(float $precision = 1, string $function = "round") Round the current instance century with given precision using the given function.
 * @method $this roundCenturies(float $precision = 1, string $function = "round") Round the current instance century with given precision using the given function.
 * @method $this floorCentury(float $precision = 1) Truncate the current instance century with given precision.
 * @method $this floorCenturies(float $precision = 1) Truncate the current instance century with given precision.
 * @method $this ceilCentury(float $precision = 1) Ceil the current instance century with given precision.
 * @method $this ceilCenturies(float $precision = 1) Ceil the current instance century with given precision.
 * @method $this roundDecade(float $precision = 1, string $function = "round") Round the current instance decade with given precision using the given function.
 * @method $this roundDecades(float $precision = 1, string $function = "round") Round the current instance decade with given precision using the given function.
 * @method $this floorDecade(float $precision = 1) Truncate the current instance decade with given precision.
 * @method $this floorDecades(float $precision = 1) Truncate the current instance decade with given precision.
 * @method $this ceilDecade(float $precision = 1) Ceil the current instance decade with given precision.
 * @method $this ceilDecades(float $precision = 1) Ceil the current instance decade with given precision.
 * @method $this roundQuarter(float $precision = 1, string $function = "round") Round the current instance quarter with given precision using the given function.
 * @method $this roundQuarters(float $precision = 1, string $function = "round") Round the current instance quarter with given precision using the given function.
 * @method $this floorQuarter(float $precision = 1) Truncate the current instance quarter with given precision.
 * @method $this floorQuarters(float $precision = 1) Truncate the current instance quarter with given precision.
 * @method $this ceilQuarter(float $precision = 1) Ceil the current instance quarter with given precision.
 * @method $this ceilQuarters(float $precision = 1) Ceil the current instance quarter with given precision.
 * @method $this roundMillisecond(float $precision = 1, string $function = "round") Round the current instance millisecond with given precision using the given function.
 * @method $this roundMilliseconds(float $precision = 1, string $function = "round") Round the current instance millisecond with given precision using the given function.
 * @method $this floorMillisecond(float $precision = 1) Truncate the current instance millisecond with given precision.
 * @method $this floorMilliseconds(float $precision = 1) Truncate the current instance millisecond with given precision.
 * @method $this ceilMillisecond(float $precision = 1) Ceil the current instance millisecond with given precision.
 * @method $this ceilMilliseconds(float $precision = 1) Ceil the current instance millisecond with given precision.
 * @method $this roundMicrosecond(float $precision = 1, string $function = "round") Round the current instance microsecond with given precision using the given function.
 * @method $this roundMicroseconds(float $precision = 1, string $function = "round") Round the current instance microsecond with given precision using the given function.
 * @method $this floorMicrosecond(float $precision = 1) Truncate the current instance microsecond with given precision.
 * @method $this floorMicroseconds(float $precision = 1) Truncate the current instance microsecond with given precision.
 * @method $this ceilMicrosecond(float $precision = 1) Ceil the current instance microsecond with given precision.
 * @method $this ceilMicroseconds(float $precision = 1) Ceil the current instance microsecond with given precision.
 *
 * @mixin DeprecatedPeriodProperties
 *
 * @SuppressWarnings(TooManyFields)
 * @SuppressWarnings(CamelCasePropertyName)
 * @SuppressWarnings(CouplingBetweenObjects)
 */
class Carbon_Period extends Date_Period_Base implements Countable, JsonSerializable, Unit_Value
{
    use Local_Factory;
    use Interval_Rounding;
    use Mixin {
        Mixin::mixin as baseMixin;
    }
    use Options {
        Options::__debugInfo as baseDebugInfo;
    }
    use To_String_Format;
    /**
     * Built-in filter for limit by recurrences.
     *
     * @var callable
     */
    public const RECURRENCES_FILTER = [self::class, 'filterRecurrences'];
    /**
     * Built-in filter for limit to an end.
     *
     * @var callable
     */
    public const END_DATE_FILTER = [self::class, 'filterEndDate'];
    /**
     * Special value which can be returned by filters to end iteration. Also a filter.
     *
     * @var callable
     */
    public const END_ITERATION = [self::class, 'endIteration'];
    /**
     * Exclude end date from iteration.
     *
     * @var int
     */
    public const EXCLUDE_END_DATE = 8;
    /**
     * Yield CarbonImmutable instances.
     *
     * @var int
     */
    public const IMMUTABLE = 4;
    /**
     * Number of maximum attempts before giving up on finding next valid date.
     *
     * @var int
     */
    public const NEXT_MAX_ATTEMPTS = 1000;
    /**
     * Number of maximum attempts before giving up on finding end date.
     *
     * @var int
     */
    public const END_MAX_ATTEMPTS = 10000;
    /**
     * Default date class of iteration items.
     *
     * @var string
     */
    protected const DEFAULT_DATE_CLASS = Carbon::class;
    /**
     * The registered macros.
     */
    protected static array $macros = [];
    /**
     * Date class of iteration items.
     */
    protected string $date_class = Carbon::class;
    /**
     * Underlying date interval instance. Always present, one day by default.
     */
    protected ?Carbon_Interval $date_interval = null;
    /**
     * True once __construct is finished.
     */
    protected bool $constructed = false;
    /**
     * Whether current date interval was set by default.
     */
    protected bool $is_default_interval = false;
    /**
     * The filters stack.
     */
    protected array $filters = [];
    /**
     * Period start date. Applied on rewind. Always present, now by default.
     */
    protected ?Carbon_Interface $start_date = null;
    /**
     * Period end date. For inverted interval should be before the start date. Applied via a filter.
     */
    protected ?Carbon_Interface $end_date = null;
    /**
     * Limit for number of recurrences. Applied via a filter.
     */
    protected int|float|null $carbon_recurrences = null;
    /**
     * Iteration options.
     */
    protected ?int $options = null;
    /**
     * Index of current date. Always sequential, even if some dates are skipped by filters.
     * Equal to null only before the first iteration.
     */
    protected int $key = 0;
    /**
     * Current date. May temporarily hold unaccepted value when looking for a next valid date.
     * Equal to null only before the first iteration.
     */
    protected ?Carbon_Interface $carbon_current = null;
    /**
     * Timezone of current date. Taken from the start date.
     */
    protected ?DateTimeZone $timezone = null;
    /**
     * The cached validation result for current date.
     */
    protected array|string|bool|null $validation_result = null;
    /**
     * Timezone handler for settings() method.
     */
    protected DateTimeZone|string|int|null $timezone_setting = null;
    public function getIterator(): Generator
    {
        $this->rewind();
        while ($this->valid()) {
            $key = $this->key();
            $value = $this->current();
            yield $key => $value;
            $this->next();
        }
    }
    /**
     * Make a CarbonPeriod instance from given variable if possible.
     */
    public static function make(mixed $var): ?static
    {
        try {
            return static::instance($var);
        } catch (Not_A_Period_Exception) {
            return static::create($var);
        }
    }
    /**
     * Create a new instance from a DatePeriod or CarbonPeriod object.
     */
    public static function instance(mixed $period): static
    {
        if ($period instanceof static) {
            return $period->copy();
        }
        if ($period instanceof self) {
            return new static($period->get_start_date(), $period->get_end_date() ?? $period->get_recurrences(), $period->get_date_interval(), $period->get_options());
        }
        if ($period instanceof DatePeriod) {
            return new static($period->start, $period->end ?: $period->recurrences - 1, $period->interval, $period->include_start_date ? 0 : static::EXCLUDE_START_DATE);
        }
        $class = static::class;
        $type = \gettype($period);
        $chunks = explode('::', __METHOD__);
        throw new Not_A_Period_Exception('Argument 1 passed to ' . $class . '::' . end($chunks) . '() ' . 'must be an instance of DatePeriod or ' . $class . ', ' . ($type === 'object' ? 'instance of ' . \get_class($period) : $type) . ' given.');
    }
    /**
     * Create a new instance.
     */
    public static function create(...$params): static
    {
        return static::create_from_array($params);
    }
    /**
     * Create a new instance from an array of parameters.
     */
    public static function create_from_array(array $params): static
    {
        return new static(...$params);
    }
    /**
     * Create CarbonPeriod from ISO 8601 string.
     */
    public static function create_from_iso(string $iso, ?int $options = null): static
    {
        $params = static::parse_iso8601($iso);
        $instance = static::create_from_array($params);
        $instance->options = ($instance instanceof Carbon_Period_Immutable ? static::IMMUTABLE : 0) | $options;
        $instance->handle_changed_parameters();
        return $instance;
    }
    public static function create_from_iso8601string(string $iso, ?int $options = null): static
    {
        return self::create_from_iso($iso, $options);
    }
    /**
     * Return whether the given interval contains non-zero value of any time unit.
     */
    protected static function interval_has_time(DateInterval $interval): bool
    {
        return $interval->h || $interval->i || $interval->s || $interval->f;
    }
    /**
     * Return whether given variable is an ISO 8601 specification.
     *
     * Note: Check is very basic, as actual validation will be done later when parsing.
     * We just want to ensure that variable is not any other type of valid parameter.
     */
    protected static function is_iso8601(mixed $var): bool
    {
        if (!\is_string($var)) {
            return false;
        }
        // Match slash but not within a timezone name.
        $part = '[a-z]+(?:[_-][a-z]+)*';
        preg_match("#\\b{$part}/{$part}\\b|(/)#i", $var, $match);
        return isset($match[1]);
    }
    /**
     * Parse given ISO 8601 string into an array of arguments.
     *
     * @SuppressWarnings(ElseExpression)
     */
    protected static function parse_iso8601(string $iso): array
    {
        $result = [];
        $interval = null;
        $start = null;
        $end = null;
        $date_class = static::DEFAULT_DATE_CLASS;
        foreach (explode('/', $iso) as $key => $part) {
            if ($key === 0 && preg_match('/^R(\d*|INF)$/', $part, $match)) {
                $parsed = \strlen($match[1]) ? $match[1] !== 'INF' ? (int) $match[1] : INF : null;
            } elseif ($interval === null && $parsed = self::make_interval($part)) {
                $interval = $part;
            } elseif ($start === null && $parsed = $date_class::make($part)) {
                $start = $part;
            } elseif ($end === null && $parsed = $date_class::make(static::add_missing_parts($start ?? '', $part))) {
                $end = $part;
            } else {
                throw new Invalid_Period_Parameter_Exception("Invalid ISO 8601 specification: {$iso}.");
            }
            $result[] = $parsed;
        }
        return $result;
    }
    /**
     * Add missing parts of the target date from the source date.
     */
    protected static function add_missing_parts(string $source, string $target): string
    {
        $pattern = '/' . preg_replace('/\d+/', '[0-9]+', preg_quote($target, '/')) . '$/';
        $result = preg_replace($pattern, $target, $source, 1, $count);
        return $count ? $result : $target;
    }
    private static function make_interval(mixed $input): ?Carbon_Interval
    {
        try {
            return Carbon_Interval::make($input);
        } catch (Throwable) {
            return null;
        }
    }
    private static function make_timezone(mixed $input): ?Carbon_Time_Zone
    {
        if (!\is_string($input)) {
            return null;
        }
        try {
            return Carbon_Time_Zone::create($input);
        } catch (Throwable) {
            return null;
        }
    }
    /**
     * Register a custom macro.
     *
     * Pass null macro to remove it.
     *
     * @example
     * ```
     * CarbonPeriod::macro('middle', function () {
     *   return $this->getStartDate()->average($this->getEndDate());
     * });
     * echo CarbonPeriod::since('2011-05-12')->until('2011-06-03')->middle();
     * ```
     *
     * @param-closure-this  static  $macro
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
     * CarbonPeriod::mixin(new class {
     *   public function addDays() {
     *     return function ($count = 1) {
     *       return $this->setStartDate(
     *         $this->getStartDate()->addDays($count)
     *       )->setEndDate(
     *         $this->getEndDate()->addDays($count)
     *       );
     *     };
     *   }
     *   public function subDays() {
     *     return function ($count = 1) {
     *       return $this->setStartDate(
     *         $this->getStartDate()->subDays($count)
     *       )->setEndDate(
     *         $this->getEndDate()->subDays($count)
     *       );
     *     };
     *   }
     * });
     * echo CarbonPeriod::create('2000-01-01', '2000-02-01')->addDays(5)->subDays(3);
     * ```
     *
     * @throws ReflectionException
     */
    public static function mixin(object|string $mixin): void
    {
        static::base_mixin($mixin);
    }
    /**
     * Check if macro is registered.
     */
    public static function has_macro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }
    /**
     * Provide static proxy for instance aliases.
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        $date = new static();
        if (static::has_macro($method)) {
            return static::bind_macro_context(null, static fn() => $date->call_macro($method, $parameters));
        }
        return $date->{$method}(...$parameters);
    }
    /**
     * CarbonPeriod constructor.
     *
     * @SuppressWarnings(ElseExpression)
     *
     * @throws InvalidArgumentException
     */
    public function __construct(...$arguments)
    {
        $raw = null;
        if (isset($arguments['raw'])) {
            $raw = $arguments['raw'];
            $this->is_default_interval = $arguments['isDefaultInterval'] ?? false;
            if (isset($arguments['dateClass'])) {
                $this->date_class = $arguments['dateClass'];
            }
            $arguments = $raw;
        }
        // Parse and assign arguments one by one. First argument may be an ISO 8601 spec,
        // which will be first parsed into parts and then processed the same way.
        $arguments_count = \count($arguments);
        if ($arguments_count && static::is_iso8601($iso = $arguments[0])) {
            array_splice($arguments, 0, 1, static::parse_iso8601($iso));
        }
        if ($arguments_count === 1) {
            if ($arguments[0] instanceof self) {
                $arguments = [$arguments[0]->get_start_date(), $arguments[0]->get_end_date() ?? $arguments[0]->get_recurrences(), $arguments[0]->get_date_interval(), $arguments[0]->get_options()];
            } elseif ($arguments[0] instanceof DatePeriod) {
                $arguments = [$arguments[0]->start, $arguments[0]->end ?: $arguments[0]->recurrences - 1, $arguments[0]->interval, $arguments[0]->include_start_date ? 0 : static::EXCLUDE_START_DATE];
            }
        }
        if (is_a($this->date_class, DateTimeImmutable::class, true)) {
            $this->options = static::IMMUTABLE;
        }
        $options_set = false;
        $original_arguments = [];
        $sorted_arguments = [];
        foreach ($arguments as $argument) {
            $parsed_date = null;
            if ($argument instanceof DateTimeZone) {
                $sorted_arguments = $this->configure_timezone($argument, $sorted_arguments, $original_arguments);
            } elseif (!isset($sorted_arguments['interval']) && (\is_string($argument) && preg_match('/^(-?\d(\d(?![\/-])|[^\d\/-]([\/-])?)*|P[T\d].*|(?:\h*\d+(?:\.\d+)?\h*[a-z]+)+)$/i', $argument) || $argument instanceof DateInterval || $argument instanceof Closure || $argument instanceof Unit) && $parsed_interval = self::make_interval($argument)) {
                $sorted_arguments['interval'] = $parsed_interval;
            } elseif (!isset($sorted_arguments['start']) && $parsed_date = $this->make_date_time($argument)) {
                $sorted_arguments['start'] = $parsed_date;
                $original_arguments['start'] = $argument;
            } elseif (!isset($sorted_arguments['end']) && $parsed_date = $parsed_date ?? $this->make_date_time($argument)) {
                $sorted_arguments['end'] = $parsed_date;
                $original_arguments['end'] = $argument;
            } elseif (!isset($sorted_arguments['recurrences']) && !isset($sorted_arguments['end']) && (\is_int($argument) || \is_float($argument)) && $argument >= 0) {
                $sorted_arguments['recurrences'] = $argument;
            } elseif (!$options_set && (\is_int($argument) || $argument === null)) {
                $options_set = true;
                $sorted_arguments['options'] = (int) $this->options | (int) $argument;
            } elseif ($parsed_timezone = self::make_timezone($argument)) {
                $sorted_arguments = $this->configure_timezone($parsed_timezone, $sorted_arguments, $original_arguments);
            } else {
                throw new Invalid_Period_Parameter_Exception('Invalid constructor parameters.');
            }
        }
        if ($raw === null && isset($sorted_arguments['start'])) {
            $end = $sorted_arguments['end'] ?? max(1, $sorted_arguments['recurrences'] ?? 1);
            if (\is_float($end)) {
                $end = $end === INF ? PHP_INT_MAX : (int) round($end);
            }
            $raw = [$sorted_arguments['start'], $sorted_arguments['interval'] ?? Carbon_Interval::day(), $end];
        }
        $this->set_from_associative_array($sorted_arguments);
        if ($this->start_date === null) {
            $date_class = $this->date_class;
            $this->set_start_date($date_class::now());
        }
        if ($this->date_interval === null) {
            $this->set_date_interval(Carbon_Interval::day());
            $this->is_default_interval = true;
        }
        if ($this->options === null) {
            $this->set_options(0);
        }
        parent::__construct($this->start_date, $this->date_interval, $this->end_date ?? max(1, min(2147483639, $this->recurrences ?? 1)), $this->options);
        $this->constructed = true;
    }
    /**
     * Get a copy of the instance.
     */
    public function copy(): static
    {
        return clone $this;
    }
    /**
     * Prepare the instance to be set (self if mutable to be mutated,
     * copy if immutable to generate a new instance).
     */
    protected function copy_if_immutable(): static
    {
        return $this;
    }
    /**
     * Get the getter for a property allowing both `DatePeriod` snakeCase and camelCase names.
     */
    protected function get_getter(string $name): ?callable
    {
        return match (strtolower(preg_replace('/[A-Z]/', '_$0', $name))) {
            'start', 'start_date' => [$this, 'getStartDate'],
            'end', 'end_date' => [$this, 'getEndDate'],
            'interval', 'date_interval' => [$this, 'getDateInterval'],
            'recurrences' => [$this, 'getRecurrences'],
            'include_start_date' => [$this, 'isStartIncluded'],
            'include_end_date' => [$this, 'isEndIncluded'],
            'current' => [$this, 'current'],
            'locale' => [$this, 'locale'],
            'tzname', 'tz_name' => fn() => match (true) {
                $this->timezone_setting === null => null,
                \is_string($this->timezone_setting) => $this->timezone_setting,
                $this->timezone_setting instanceof DateTimeZone => $this->timezone_setting->get_name(),
                default => Carbon_Time_Zone::instance($this->timezone_setting)->get_name(),
            },
            default => null,
        };
    }
    /**
     * Get a property allowing both `DatePeriod` snakeCase and camelCase names.
     *
     * @param string $name
     *
     * @return bool|CarbonInterface|CarbonInterval|int|null
     */
    public function get(string $name)
    {
        $getter = $this->get_getter($name);
        if ($getter) {
            return $getter();
        }
        throw new Unknown_Getter_Exception($name);
    }
    /**
     * Get a property allowing both `DatePeriod` snakeCase and camelCase names.
     *
     * @param string $name
     *
     * @return bool|CarbonInterface|CarbonInterval|int|null
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }
    /**
     * Check if an attribute exists on the object
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->get_getter($name) !== null;
    }
    /**
     * @alias copy
     *
     * Get a copy of the instance.
     *
     * @return static
     */
    public function clone()
    {
        return clone $this;
    }
    /**
     * Set the iteration item class.
     *
     * @param string $dateClass
     *
     * @return static
     */
    public function set_date_class(string $date_class)
    {
        if (!is_a($date_class, Carbon_Interface::class, true)) {
            throw new Not_A_Carbon_Class_Exception($date_class);
        }
        $self = $this->copy_if_immutable();
        $self->date_class = $date_class;
        if (is_a($date_class, Carbon::class, true)) {
            $self->options = $self->options & ~static::IMMUTABLE;
        } elseif (is_a($date_class, Carbon_Immutable::class, true)) {
            $self->options = $self->options | static::IMMUTABLE;
        }
        return $self;
    }
    /**
     * Returns iteration item date class.
     *
     * @return string
     */
    public function get_date_class(): string
    {
        return $this->date_class;
    }
    /**
     * Change the period date interval.
     *
     * @param DateInterval|Unit|string|int $interval
     * @param Unit|string                  $unit     the unit of $interval if it's a number
     *
     * @throws InvalidIntervalException
     *
     * @return static
     */
    public function set_date_interval(mixed $interval, Unit|string|null $unit = null): static
    {
        if ($interval instanceof Unit) {
            $interval = $interval->interval();
        }
        if ($unit instanceof Unit) {
            $unit = $unit->name;
        }
        if (!$interval = Carbon_Interval::make($interval, $unit)) {
            throw new Invalid_Interval_Exception('Invalid interval.');
        }
        if ($interval->spec() === 'PT0S' && !$interval->f && !$interval->get_step()) {
            throw new Invalid_Interval_Exception('Empty interval is not accepted.');
        }
        $self = $this->copy_if_immutable();
        $self->date_interval = $interval;
        $self->is_default_interval = false;
        $self->handle_changed_parameters();
        return $self;
    }
    /**
     * Reset the date interval to the default value.
     *
     * Difference with simply setting interval to 1-day is that P1D will not appear when calling toIso8601String()
     * and also next adding to the interval won't include the default 1-day.
     */
    public function reset_date_interval(): static
    {
        $self = $this->copy_if_immutable();
        $self->set_date_interval(Carbon_Interval::day());
        $self->is_default_interval = true;
        return $self;
    }
    /**
     * Invert the period date interval.
     */
    public function invert_date_interval(): static
    {
        return $this->set_date_interval($this->date_interval->invert());
    }
    /**
     * Set start and end date.
     *
     * @param DateTime|DateTimeInterface|string      $start
     * @param DateTime|DateTimeInterface|string|null $end
     *
     * @return static
     */
    public function set_dates(mixed $start, mixed $end): static
    {
        return $this->set_start_date($start)->set_end_date($end);
    }
    /**
     * Change the period options.
     *
     * @param int|null $options
     *
     * @return static
     */
    public function set_options(?int $options): static
    {
        $self = $this->copy_if_immutable();
        $self->options = $options ?? 0;
        $self->handle_changed_parameters();
        return $self;
    }
    /**
     * Get the period options.
     */
    public function get_options(): int
    {
        return $this->options ?? 0;
    }
    /**
     * Toggle given options on or off.
     *
     * @param int       $options
     * @param bool|null $state
     *
     * @throws InvalidArgumentException
     *
     * @return static
     */
    public function toggle_options(int $options, ?bool $state = null): static
    {
        $self = $this->copy_if_immutable();
        if ($state === null) {
            $state = ($this->options & $options) !== $options;
        }
        return $self->set_options($state ? $this->options | $options : $this->options & ~$options);
    }
    /**
     * Toggle EXCLUDE_START_DATE option.
     */
    public function exclude_start_date(bool $state = true): static
    {
        return $this->toggle_options(static::EXCLUDE_START_DATE, $state);
    }
    /**
     * Toggle EXCLUDE_END_DATE option.
     */
    public function exclude_end_date(bool $state = true): static
    {
        return $this->toggle_options(static::EXCLUDE_END_DATE, $state);
    }
    /**
     * Get the underlying date interval.
     */
    public function get_date_interval(): Carbon_Interval
    {
        return $this->date_interval->copy();
    }
    /**
     * Get start date of the period.
     *
     * @param string|null $rounding Optional rounding 'floor', 'ceil', 'round' using the period interval.
     */
    public function get_start_date(?string $rounding = null): Carbon_Interface
    {
        $date = $this->start_date->avoid_mutation();
        return $rounding ? $date->round($this->get_date_interval(), $rounding) : $date;
    }
    /**
     * Get end date of the period.
     *
     * @param string|null $rounding Optional rounding 'floor', 'ceil', 'round' using the period interval.
     */
    public function get_end_date(?string $rounding = null): ?Carbon_Interface
    {
        if (!$this->end_date) {
            return null;
        }
        $date = $this->end_date->avoid_mutation();
        return $rounding ? $date->round($this->get_date_interval(), $rounding) : $date;
    }
    /**
     * Get number of recurrences.
     */
    #[Return_Type_Will_Change]
    public function get_recurrences(): int|float|null
    {
        return $this->carbon_recurrences;
    }
    /**
     * Returns true if the start date should be excluded.
     */
    public function is_start_excluded(): bool
    {
        return ($this->options & static::EXCLUDE_START_DATE) !== 0;
    }
    /**
     * Returns true if the end date should be excluded.
     */
    public function is_end_excluded(): bool
    {
        return ($this->options & static::EXCLUDE_END_DATE) !== 0;
    }
    /**
     * Returns true if the start date should be included.
     */
    public function is_start_included(): bool
    {
        return !$this->is_start_excluded();
    }
    /**
     * Returns true if the end date should be included.
     */
    public function is_end_included(): bool
    {
        return !$this->is_end_excluded();
    }
    /**
     * Return the start if it's included by option, else return the start + 1 period interval.
     */
    public function get_included_start_date(): Carbon_Interface
    {
        $start = $this->get_start_date();
        if ($this->is_start_excluded()) {
            return $start->add($this->get_date_interval());
        }
        return $start;
    }
    /**
     * Return the end if it's included by option, else return the end - 1 period interval.
     * Warning: if the period has no fixed end, this method will iterate the period to calculate it.
     */
    public function get_included_end_date(): Carbon_Interface
    {
        $end = $this->get_end_date();
        if (!$end) {
            return $this->calculate_end();
        }
        if ($this->is_end_excluded()) {
            return $end->sub($this->get_date_interval());
        }
        return $end;
    }
    /**
     * Add a filter to the stack.
     *
     * @SuppressWarnings(UnusedFormalParameter)
     */
    public function add_filter(callable|string $callback, ?string $name = null): static
    {
        $self = $this->copy_if_immutable();
        $tuple = $self->create_filter_tuple(\func_get_args());
        $self->filters[] = $tuple;
        $self->handle_changed_parameters();
        return $self;
    }
    /**
     * Prepend a filter to the stack.
     *
     * @SuppressWarnings(UnusedFormalParameter)
     */
    public function prepend_filter(callable|string $callback, ?string $name = null): static
    {
        $self = $this->copy_if_immutable();
        $tuple = $self->create_filter_tuple(\func_get_args());
        array_unshift($self->filters, $tuple);
        $self->handle_changed_parameters();
        return $self;
    }
    /**
     * Remove a filter by instance or name.
     */
    public function remove_filter(callable|string $filter): static
    {
        $self = $this->copy_if_immutable();
        $key = \is_callable($filter) ? 0 : 1;
        $self->filters = array_values(array_filter($this->filters, static fn($tuple) => $tuple[$key] !== $filter));
        $self->update_internal_state();
        $self->handle_changed_parameters();
        return $self;
    }
    /**
     * Return whether given instance or name is in the filter stack.
     */
    public function has_filter(callable|string $filter): bool
    {
        $key = \is_callable($filter) ? 0 : 1;
        foreach ($this->filters as $tuple) {
            if ($tuple[$key] === $filter) {
                return true;
            }
        }
        return false;
    }
    /**
     * Get filters stack.
     */
    public function get_filters(): array
    {
        return $this->filters;
    }
    /**
     * Set filters stack.
     */
    public function set_filters(array $filters): static
    {
        $self = $this->copy_if_immutable();
        $self->filters = $filters;
        $self->update_internal_state();
        $self->handle_changed_parameters();
        return $self;
    }
    /**
     * Reset filters stack.
     */
    public function reset_filters(): static
    {
        $self = $this->copy_if_immutable();
        $self->filters = [];
        if ($self->end_date !== null) {
            $self->filters[] = [static::END_DATE_FILTER, null];
        }
        if ($self->carbon_recurrences !== null) {
            $self->filters[] = [static::RECURRENCES_FILTER, null];
        }
        $self->handle_changed_parameters();
        return $self;
    }
    /**
     * Add a recurrences filter (set maximum number of recurrences).
     *
     * @throws InvalidArgumentException
     */
    public function set_recurrences(int|float|null $recurrences): static
    {
        if ($recurrences === null) {
            return $this->remove_filter(static::RECURRENCES_FILTER);
        }
        if ($recurrences < 0) {
            throw new Invalid_Period_Parameter_Exception('Invalid number of recurrences.');
        }
        /** @var self $self */
        $self = $this->copy_if_immutable();
        $self->carbon_recurrences = $recurrences === INF ? INF : (int) $recurrences;
        if (!$self->has_filter(static::RECURRENCES_FILTER)) {
            return $self->add_filter(static::RECURRENCES_FILTER);
        }
        $self->handle_changed_parameters();
        return $self;
    }
    /**
     * Change the period start date.
     *
     * @param DateTime|DateTimeInterface|string $date
     * @param bool|null                         $inclusive
     *
     * @throws InvalidPeriodDateException
     *
     * @return static
     */
    public function set_start_date(mixed $date, ?bool $inclusive = null): static
    {
        if (!$this->is_infinite_date($date) && !$date = [$this->date_class, 'make']($date, $this->timezone)) {
            throw new Invalid_Period_Date_Exception('Invalid start date.');
        }
        $self = $this->copy_if_immutable();
        $self->start_date = $date;
        if ($inclusive !== null) {
            $self = $self->toggle_options(static::EXCLUDE_START_DATE, !$inclusive);
        }
        return $self;
    }
    /**
     * Change the period end date.
     *
     * @param DateTime|DateTimeInterface|string|null $date
     * @param bool|null                              $inclusive
     *
     * @throws \InvalidArgumentException
     *
     * @return static
     */
    public function set_end_date(mixed $date, ?bool $inclusive = null): static
    {
        if ($date !== null && !$this->is_infinite_date($date) && !$date = [$this->date_class, 'make']($date, $this->timezone)) {
            throw new Invalid_Period_Date_Exception('Invalid end date.');
        }
        if (!$date) {
            return $this->remove_filter(static::END_DATE_FILTER);
        }
        $self = $this->copy_if_immutable();
        $self->end_date = $date;
        if ($inclusive !== null) {
            $self = $self->toggle_options(static::EXCLUDE_END_DATE, !$inclusive);
        }
        if (!$self->has_filter(static::END_DATE_FILTER)) {
            return $self->add_filter(static::END_DATE_FILTER);
        }
        $self->handle_changed_parameters();
        return $self;
    }
    /**
     * Check if the current position is valid.
     */
    public function valid(): bool
    {
        return $this->validate_current_date() === true;
    }
    /**
     * Return the current key.
     */
    public function key(): ?int
    {
        return $this->valid() ? $this->key : null;
    }
    /**
     * Return the current date.
     */
    public function current(): ?Carbon_Interface
    {
        return $this->valid() ? $this->prepare_for_return($this->carbon_current) : null;
    }
    /**
     * Move forward to the next date.
     *
     * @throws RuntimeException
     */
    public function next(): void
    {
        if ($this->carbon_current === null) {
            $this->rewind();
        }
        if ($this->validation_result !== static::END_ITERATION) {
            $this->key++;
            $this->increment_current_date_until_valid();
        }
    }
    /**
     * Rewind to the start date.
     *
     * Iterating over a date in the UTC timezone avoids bug during backward DST change.
     *
     * @see https://bugs.php.net/bug.php?id=72255
     * @see https://bugs.php.net/bug.php?id=74274
     * @see https://wiki.php.net/rfc/datetime_and_daylight_saving_time
     *
     * @throws RuntimeException
     */
    public function rewind(): void
    {
        $this->key = 0;
        $this->carbon_current = [$this->date_class, 'make']($this->start_date);
        $settings = $this->get_settings();
        if ($this->has_local_translator()) {
            $settings['locale'] = $this->get_translator_locale();
        }
        $this->carbon_current->settings($settings);
        $this->timezone = static::interval_has_time($this->date_interval) ? $this->carbon_current->get_timezone() : null;
        if ($this->timezone) {
            $this->carbon_current = $this->carbon_current->utc();
        }
        $this->validation_result = null;
        if ($this->is_start_excluded() || $this->validate_current_date() === false) {
            $this->increment_current_date_until_valid();
        }
    }
    /**
     * Skip iterations and returns iteration state (false if ended, true if still valid).
     *
     * @param int $count steps number to skip (1 by default)
     *
     * @return bool
     */
    public function skip(int $count = 1): bool
    {
        for ($i = $count; $this->valid() && $i > 0; $i--) {
            $this->next();
        }
        return $this->valid();
    }
    /**
     * Format the date period as ISO 8601.
     */
    public function to_iso8601string(): string
    {
        $parts = [];
        if ($this->carbon_recurrences !== null) {
            $parts[] = 'R' . $this->carbon_recurrences;
        }
        $parts[] = $this->start_date->to_iso8601string();
        if (!$this->is_default_interval) {
            $parts[] = $this->date_interval->spec();
        }
        if ($this->end_date !== null) {
            $parts[] = $this->end_date->to_iso8601string();
        }
        return implode('/', $parts);
    }
    /**
     * Convert the date period into a string.
     */
    public function to_string(): string
    {
        $format = $this->local_to_string_format ?? $this->get_factory()->get_settings()['toStringFormat'] ?? null;
        if ($format instanceof Closure) {
            return $format($this);
        }
        $translator = [$this->date_class, 'getTranslator']();
        $parts = [];
        $format = $format ?? (!$this->start_date->is_start_of_day() || $this->end_date && !$this->end_date->is_start_of_day() ? 'Y-m-d H:i:s' : 'Y-m-d');
        if ($this->carbon_recurrences !== null) {
            $parts[] = $this->translate('period_recurrences', [], $this->carbon_recurrences, $translator);
        }
        $parts[] = $this->translate('period_interval', [':interval' => $this->date_interval->for_humans(['join' => true])], null, $translator);
        $parts[] = $this->translate('period_start_date', [':date' => $this->start_date->raw_format($format)], null, $translator);
        if ($this->end_date !== null) {
            $parts[] = $this->translate('period_end_date', [':date' => $this->end_date->raw_format($format)], null, $translator);
        }
        $result = implode(' ', $parts);
        return mb_strtoupper(mb_substr($result, 0, 1)) . mb_substr($result, 1);
    }
    /**
     * Format the date period as ISO 8601.
     */
    public function spec(): string
    {
        return $this->to_iso8601string();
    }
    /**
     * Cast the current instance into the given class.
     *
     * @param string $className The $className::instance() method will be called to cast the current object.
     *
     * @return DatePeriod|object
     */
    public function cast(string $class_name): object
    {
        if (!method_exists($class_name, 'instance')) {
            if (is_a($class_name, DatePeriod::class, true)) {
                return new $class_name($this->raw_date($this->get_start_date()), $this->get_date_interval(), $this->get_end_date() ? $this->raw_date($this->get_included_end_date()) : $this->get_recurrences(), $this->is_start_excluded() ? DatePeriod::EXCLUDE_START_DATE : 0);
            }
            throw new Invalid_Cast_Exception("{$class_name} has not the instance() method needed to cast the date.");
        }
        return $class_name::instance($this);
    }
    /**
     * Return native DatePeriod PHP object matching the current instance.
     *
     * @example
     * ```
     * var_dump(CarbonPeriod::create('2021-01-05', '2021-02-15')->toDatePeriod());
     * ```
     */
    public function to_date_period(): DatePeriod
    {
        return $this->cast(DatePeriod::class);
    }
    /**
     * Return `true` if the period has no custom filter and is guaranteed to be endless.
     *
     * Note that we can't check if a period is endless as soon as it has custom filters
     * because filters can emit `CarbonPeriod::END_ITERATION` to stop the iteration in
     * a way we can't predict without actually iterating the period.
     */
    public function is_unfiltered_and_end_less(): bool
    {
        foreach ($this->filters as $filter) {
            switch ($filter) {
                case [static::RECURRENCES_FILTER, null]:
                    if ($this->carbon_recurrences !== null && is_finite($this->carbon_recurrences)) {
                        return false;
                    }
                    break;
                case [static::END_DATE_FILTER, null]:
                    if ($this->end_date !== null && !$this->end_date->is_end_of_time()) {
                        return false;
                    }
                    break;
                default:
                    return false;
            }
        }
        return true;
    }
    /**
     * Convert the date period into an array without changing current iteration state.
     *
     * @return CarbonInterface[]
     */
    public function to_array(): array
    {
        if ($this->is_unfiltered_and_end_less()) {
            throw new End_Less_Period_Exception("Endless period can't be converted to array nor counted.");
        }
        $state = [$this->key, $this->carbon_current ? $this->carbon_current->avoid_mutation() : null, $this->validation_result];
        $result = iterator_to_array($this);
        [$this->key, $this->carbon_current, $this->validation_result] = $state;
        return $result;
    }
    /**
     * Count dates in the date period.
     */
    public function count(): int
    {
        return \count($this->to_array());
    }
    /**
     * Return the first date in the date period.
     */
    public function first(): ?Carbon_Interface
    {
        if ($this->is_unfiltered_and_end_less()) {
            foreach ($this as $date) {
                $this->rewind();
                return $date;
            }
            return null;
        }
        return ($this->to_array() ?: [])[0] ?? null;
    }
    /**
     * Return the last date in the date period.
     */
    public function last(): ?Carbon_Interface
    {
        $array = $this->to_array();
        return $array ? $array[\count($array) - 1] : null;
    }
    /**
     * Convert the date period into a string.
     */
    public function __toString(): string
    {
        return $this->to_string();
    }
    /**
     * Add aliases for setters.
     *
     * CarbonPeriod::days(3)->hours(5)->invert()
     *     ->sinceNow()->until('2010-01-10')
     *     ->filter(...)
     *     ->count()
     *
     * Note: We use magic method to let static and instance aliases with the same names.
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (static::has_macro($method)) {
            return static::bind_macro_context($this, fn() => $this->call_macro($method, $parameters));
        }
        $rounded_value = $this->call_round_method($method, $parameters);
        if ($rounded_value !== null) {
            return $rounded_value;
        }
        $count = \count($parameters);
        switch ($method) {
            case 'start':
            case 'since':
                if ($count === 0) {
                    return $this->get_start_date();
                }
                self::set_default_parameters($parameters, [[0, 'date', null]]);
                return $this->set_start_date(...$parameters);
            case 'sinceNow':
                return $this->set_start_date(new Carbon(), ...$parameters);
            case 'end':
            case 'until':
                if ($count === 0) {
                    return $this->get_end_date();
                }
                self::set_default_parameters($parameters, [[0, 'date', null]]);
                return $this->set_end_date(...$parameters);
            case 'untilNow':
                return $this->set_end_date(new Carbon(), ...$parameters);
            case 'dates':
            case 'between':
                self::set_default_parameters($parameters, [[0, 'start', null], [1, 'end', null]]);
                return $this->set_dates(...$parameters);
            case 'recurrences':
            case 'times':
                if ($count === 0) {
                    return $this->get_recurrences();
                }
                self::set_default_parameters($parameters, [[0, 'recurrences', null]]);
                return $this->set_recurrences(...$parameters);
            case 'options':
                if ($count === 0) {
                    return $this->get_options();
                }
                self::set_default_parameters($parameters, [[0, 'options', null]]);
                return $this->set_options(...$parameters);
            case 'toggle':
                self::set_default_parameters($parameters, [[0, 'options', null]]);
                return $this->toggle_options(...$parameters);
            case 'filter':
            case 'push':
                return $this->add_filter(...$parameters);
            case 'prepend':
                return $this->prepend_filter(...$parameters);
            case 'filters':
                if ($count === 0) {
                    return $this->get_filters();
                }
                self::set_default_parameters($parameters, [[0, 'filters', []]]);
                return $this->set_filters(...$parameters);
            case 'interval':
            case 'each':
            case 'every':
            case 'step':
            case 'stepBy':
                if ($count === 0) {
                    return $this->get_date_interval();
                }
                return $this->set_date_interval(...$parameters);
            case 'invert':
                return $this->invert_date_interval();
            case 'years':
            case 'year':
            case 'months':
            case 'month':
            case 'weeks':
            case 'week':
            case 'days':
            case 'dayz':
            case 'day':
            case 'hours':
            case 'hour':
            case 'minutes':
            case 'minute':
            case 'seconds':
            case 'second':
            case 'milliseconds':
            case 'millisecond':
            case 'microseconds':
            case 'microsecond':
                return $this->set_date_interval([$this->is_default_interval ? new Carbon_Interval('PT0S') : $this->date_interval, $method](...$parameters));
        }
        $date_class = $this->date_class;
        if ($this->local_strict_mode_enabled ?? $date_class::is_strict_mode_enabled()) {
            throw new Unknown_Method_Exception($method);
        }
        return $this;
    }
    /**
     * Set the instance's timezone from a string or object and apply it to start/end.
     */
    public function set_timezone(DateTimeZone|string|int $timezone): static
    {
        $self = $this->copy_if_immutable();
        $self->timezone_setting = $timezone;
        $self->timezone = Carbon_Time_Zone::instance($timezone);
        if ($self->start_date) {
            $self = $self->set_start_date($self->start_date->set_timezone($timezone));
        }
        if ($self->end_date) {
            $self = $self->set_end_date($self->end_date->set_timezone($timezone));
        }
        return $self;
    }
    /**
     * Set the instance's timezone from a string or object and add/subtract the offset difference to start/end.
     */
    public function shift_timezone(DateTimeZone|string|int $timezone): static
    {
        $self = $this->copy_if_immutable();
        $self->timezone_setting = $timezone;
        $self->timezone = Carbon_Time_Zone::instance($timezone);
        if ($self->start_date) {
            $self = $self->set_start_date($self->start_date->shift_timezone($timezone));
        }
        if ($self->end_date) {
            $self = $self->set_end_date($self->end_date->shift_timezone($timezone));
        }
        return $self;
    }
    /**
     * Returns the end is set, else calculated from start and recurrences.
     *
     * @param string|null $rounding Optional rounding 'floor', 'ceil', 'round' using the period interval.
     *
     * @return CarbonInterface
     */
    public function calculate_end(?string $rounding = null): Carbon_Interface
    {
        if ($end = $this->get_end_date($rounding)) {
            return $end;
        }
        if ($this->date_interval->is_empty()) {
            return $this->get_start_date($rounding);
        }
        $date = $this->get_end_from_recurrences() ?? $this->iterate_until_end();
        if ($date && $rounding) {
            $date = $date->avoid_mutation()->round($this->get_date_interval(), $rounding);
        }
        return $date;
    }
    private function get_end_from_recurrences(): ?Carbon_Interface
    {
        if ($this->carbon_recurrences === null) {
            throw new Unreachable_Exception("Could not calculate period end without either explicit end or recurrences.\n" . "If you're looking for a forever-period, use ->setRecurrences(INF).");
        }
        if ($this->carbon_recurrences === INF) {
            $start = $this->get_start_date();
            return $start < $start->avoid_mutation()->add($this->get_date_interval()) ? Carbon_Immutable::end_of_time() : Carbon_Immutable::start_of_time();
        }
        if ($this->filters === [[static::RECURRENCES_FILTER, null]]) {
            return $this->get_start_date()->avoid_mutation()->add($this->get_date_interval()->times($this->carbon_recurrences - ($this->is_start_excluded() ? 0 : 1)));
        }
        return null;
    }
    private function iterate_until_end(): ?Carbon_Interface
    {
        $attempts = 0;
        $date = null;
        foreach ($this as $date) {
            if (++$attempts > static::END_MAX_ATTEMPTS) {
                throw new Unreachable_Exception('Could not calculate period end after iterating ' . static::END_MAX_ATTEMPTS . ' times.');
            }
        }
        return $date;
    }
    /**
     * Returns true if the current period overlaps the given one (if 1 parameter passed)
     * or the period between 2 dates (if 2 parameters passed).
     *
     * @param CarbonPeriod|\DateTimeInterface|Carbon|CarbonImmutable|string $rangeOrRangeStart
     * @param \DateTimeInterface|Carbon|CarbonImmutable|string|null         $rangeEnd
     *
     * @return bool
     */
    public function overlaps(mixed $range_or_range_start, mixed $range_end = null): bool
    {
        $range = $range_end ? static::create($range_or_range_start, $range_end) : $range_or_range_start;
        if (!$range instanceof self) {
            $range = static::create($range);
        }
        [$start, $end] = $this->order_couple($this->get_start_date(), $this->calculate_end());
        [$range_start, $range_end] = $this->order_couple($range->get_start_date(), $range->calculate_end());
        return $end > $range_start && $range_end > $start;
    }
    /**
     * Execute a given function on each date of the period.
     *
     * @example
     * ```
     * Carbon::create('2020-11-29')->daysUntil('2020-12-24')->forEach(function (Carbon $date) {
     *   echo $date->diffInDays('2020-12-25')." days before Christmas!\n";
     * });
     * ```
     */
    public function for_each(callable $callback): void
    {
        foreach ($this as $date) {
            $callback($date);
        }
    }
    /**
     * Execute a given function on each date of the period and yield the result of this function.
     *
     * @example
     * ```
     * $period = Carbon::create('2020-11-29')->daysUntil('2020-12-24');
     * echo implode("\n", iterator_to_array($period->map(function (Carbon $date) {
     *   return $date->diffInDays('2020-12-25').' days before Christmas!';
     * })));
     * ```
     */
    public function map(callable $callback): Generator
    {
        foreach ($this as $date) {
            yield $callback($date);
        }
    }
    /**
     * Determines if the instance is equal to another.
     * Warning: if options differ, instances will never be equal.
     *
     * @see equalTo()
     */
    public function eq(mixed $period): bool
    {
        return $this->equal_to($period);
    }
    /**
     * Determines if the instance is equal to another.
     * Warning: if options differ, instances will never be equal.
     */
    public function equal_to(mixed $period): bool
    {
        if (!$period instanceof self) {
            $period = self::make($period);
        }
        $end = $this->get_end_date();
        return $period !== null && $this->get_date_interval()->eq($period->get_date_interval()) && $this->get_start_date()->eq($period->get_start_date()) && ($end ? $end->eq($period->get_end_date()) : $this->get_recurrences() === $period->get_recurrences()) && ($this->get_options() & ~static::IMMUTABLE) === ($period->get_options() & ~static::IMMUTABLE);
    }
    /**
     * Determines if the instance is not equal to another.
     * Warning: if options differ, instances will never be equal.
     *
     * @see notEqualTo()
     */
    public function ne(mixed $period): bool
    {
        return $this->not_equal_to($period);
    }
    /**
     * Determines if the instance is not equal to another.
     * Warning: if options differ, instances will never be equal.
     */
    public function not_equal_to(mixed $period): bool
    {
        return !$this->eq($period);
    }
    /**
     * Determines if the start date is before another given date.
     * (Rather start/end are included by options is ignored.)
     */
    public function starts_before(mixed $date = null): bool
    {
        return $this->get_start_date()->less_than($this->resolve_carbon($date));
    }
    /**
     * Determines if the start date is before or the same as a given date.
     * (Rather start/end are included by options is ignored.)
     */
    public function starts_before_or_at(mixed $date = null): bool
    {
        return $this->get_start_date()->less_than_or_equal_to($this->resolve_carbon($date));
    }
    /**
     * Determines if the start date is after another given date.
     * (Rather start/end are included by options is ignored.)
     */
    public function starts_after(mixed $date = null): bool
    {
        return $this->get_start_date()->greater_than($this->resolve_carbon($date));
    }
    /**
     * Determines if the start date is after or the same as a given date.
     * (Rather start/end are included by options is ignored.)
     */
    public function starts_after_or_at(mixed $date = null): bool
    {
        return $this->get_start_date()->greater_than_or_equal_to($this->resolve_carbon($date));
    }
    /**
     * Determines if the start date is the same as a given date.
     * (Rather start/end are included by options is ignored.)
     */
    public function starts_at(mixed $date = null): bool
    {
        return $this->get_start_date()->equal_to($this->resolve_carbon($date));
    }
    /**
     * Determines if the end date is before another given date.
     * (Rather start/end are included by options is ignored.)
     */
    public function ends_before(mixed $date = null): bool
    {
        return $this->calculate_end()->less_than($this->resolve_carbon($date));
    }
    /**
     * Determines if the end date is before or the same as a given date.
     * (Rather start/end are included by options is ignored.)
     */
    public function ends_before_or_at(mixed $date = null): bool
    {
        return $this->calculate_end()->less_than_or_equal_to($this->resolve_carbon($date));
    }
    /**
     * Determines if the end date is after another given date.
     * (Rather start/end are included by options is ignored.)
     */
    public function ends_after(mixed $date = null): bool
    {
        return $this->calculate_end()->greater_than($this->resolve_carbon($date));
    }
    /**
     * Determines if the end date is after or the same as a given date.
     * (Rather start/end are included by options is ignored.)
     */
    public function ends_after_or_at(mixed $date = null): bool
    {
        return $this->calculate_end()->greater_than_or_equal_to($this->resolve_carbon($date));
    }
    /**
     * Determines if the end date is the same as a given date.
     * (Rather start/end are included by options is ignored.)
     */
    public function ends_at(mixed $date = null): bool
    {
        return $this->calculate_end()->equal_to($this->resolve_carbon($date));
    }
    /**
     * Return true if start date is now or later.
     * (Rather start/end are included by options is ignored.)
     */
    public function is_started(): bool
    {
        return $this->starts_before_or_at();
    }
    /**
     * Return true if end date is now or later.
     * (Rather start/end are included by options is ignored.)
     */
    public function is_ended(): bool
    {
        return $this->ends_before_or_at();
    }
    /**
     * Return true if now is between start date (included) and end date (excluded).
     * (Rather start/end are included by options is ignored.)
     */
    public function is_in_progress(): bool
    {
        return $this->is_started() && !$this->is_ended();
    }
    /**
     * Round the current instance at the given unit with given precision if specified and the given function.
     */
    public function round_unit(string $unit, DateInterval|float|int|string|null $precision = 1, callable|string $function = 'round'): static
    {
        $self = $this->copy_if_immutable();
        $self = $self->set_start_date($self->get_start_date()->round_unit($unit, $precision, $function));
        if ($self->end_date) {
            $self = $self->set_end_date($self->get_end_date()->round_unit($unit, $precision, $function));
        }
        return $self->set_date_interval($self->get_date_interval()->round_unit($unit, $precision, $function));
    }
    /**
     * Truncate the current instance at the given unit with given precision if specified.
     */
    public function floor_unit(string $unit, DateInterval|float|int|string|null $precision = 1): static
    {
        return $this->round_unit($unit, $precision, 'floor');
    }
    /**
     * Ceil the current instance at the given unit with given precision if specified.
     */
    public function ceil_unit(string $unit, DateInterval|float|int|string|null $precision = 1): static
    {
        return $this->round_unit($unit, $precision, 'ceil');
    }
    /**
     * Round the current instance second with given precision if specified (else period interval is used).
     */
    public function round(DateInterval|float|int|string|null $precision = null, callable|string $function = 'round'): static
    {
        return $this->round_with($precision ?? $this->get_date_interval()->set_local_translator(Translator_Immutable::get('en'))->for_humans(), $function);
    }
    /**
     * Round the current instance second with given precision if specified (else period interval is used).
     */
    public function floor(DateInterval|float|int|string|null $precision = null): static
    {
        return $this->round($precision, 'floor');
    }
    /**
     * Ceil the current instance second with given precision if specified (else period interval is used).
     */
    public function ceil(DateInterval|float|int|string|null $precision = null): static
    {
        return $this->round($precision, 'ceil');
    }
    /**
     * Specify data which should be serialized to JSON.
     *
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return CarbonInterface[]
     */
    public function jsonSerialize(): array
    {
        return $this->to_array();
    }
    /**
     * Return true if the given date is between start and end.
     */
    public function contains(mixed $date = null): bool
    {
        $start_method = 'startsBefore' . ($this->is_start_included() ? 'OrAt' : '');
        $end_method = 'endsAfter' . ($this->is_end_included() ? 'OrAt' : '');
        return $this->{$start_method}($date) && $this->{$end_method}($date);
    }
    /**
     * Return true if the current period follows a given other period (with no overlap).
     * For instance, [2019-08-01 -> 2019-08-12] follows [2019-07-29 -> 2019-07-31]
     * Note than in this example, follows() would be false if 2019-08-01 or 2019-07-31 was excluded by options.
     */
    public function follows(mixed $period, mixed ...$arguments): bool
    {
        $period = $this->resolve_carbon_period($period, ...$arguments);
        return $this->get_included_start_date()->equal_to($period->get_included_end_date()->add($period->get_date_interval()));
    }
    /**
     * Return true if the given other period follows the current one (with no overlap).
     * For instance, [2019-07-29 -> 2019-07-31] is followed by [2019-08-01 -> 2019-08-12]
     * Note than in this example, isFollowedBy() would be false if 2019-08-01 or 2019-07-31 was excluded by options.
     */
    public function is_followed_by(mixed $period, mixed ...$arguments): bool
    {
        $period = $this->resolve_carbon_period($period, ...$arguments);
        return $period->follows($this);
    }
    /**
     * Return true if the given period either follows or is followed by the current one.
     *
     * @see follows()
     * @see isFollowedBy()
     */
    public function is_consecutive_with(mixed $period, mixed ...$arguments): bool
    {
        return $this->follows($period, ...$arguments) || $this->is_followed_by($period, ...$arguments);
    }
    public function __debugInfo(): array
    {
        $info = $this->base_debug_info();
        unset($info['start'], $info['end'], $info['interval'], $info['include_start_date'], $info['include_end_date'], $info['constructed'], $info["\x00*\x00constructed"]);
        return $info;
    }
    public function __unserialize(array $data): void
    {
        try {
            $values = array_combine(array_map(static fn(string $key): string => preg_replace('/^\0\*\0/', '', $key), array_keys($data)), $data);
            $this->initialize_serialization($values);
            foreach ($values as $key => $value) {
                if ($value === null) {
                    continue;
                }
                $property = match ($key) {
                    'tzName' => $this->set_timezone(...),
                    'options' => $this->set_options(...),
                    'recurrences' => $this->set_recurrences(...),
                    'current' => function (mixed $current): void {
                        if (!$current instanceof Carbon_Interface) {
                            $current = $this->resolve_carbon($current);
                        }
                        $this->carbon_current = $current;
                    },
                    'start' => 'startDate',
                    'interval' => $this->set_date_interval(...),
                    'end' => 'endDate',
                    'key' => null,
                    'include_start_date' => function (bool $included): void {
                        $this->exclude_start_date(!$included);
                    },
                    'include_end_date' => function (bool $included): void {
                        $this->exclude_end_date(!$included);
                    },
                    default => null,
                };
                if ($property === null) {
                    continue;
                }
                if (\is_callable($property)) {
                    $property($value);
                    continue;
                }
                if ($value instanceof DateTimeInterface && !$value instanceof Carbon_Interface) {
                    $value = $value instanceof DateTime ? Carbon::instance($value) : Carbon_Immutable::instance($value);
                }
                try {
                    $this->{$property} = $value;
                } catch (Throwable) {
                    // Must be ignored for backward-compatibility
                }
            }
            if (\array_key_exists('carbonRecurrences', $values)) {
                $this->carbon_recurrences = $values['carbonRecurrences'];
            } elseif ((int) ($values['recurrences'] ?? 0) <= 1 && $this->end_date !== null) {
                $this->carbon_recurrences = null;
            }
        } catch (Throwable $e) {
            // @codeCoverageIgnoreStart
            if (!method_exists(parent::class, '__unserialize')) {
                throw $e;
            }
            parent::__unserialize($data);
            // @codeCoverageIgnoreEnd
        }
    }
    /**
     * Update properties after removing built-in filters.
     */
    protected function update_internal_state(): void
    {
        if (!$this->has_filter(static::END_DATE_FILTER)) {
            $this->end_date = null;
        }
        if (!$this->has_filter(static::RECURRENCES_FILTER)) {
            $this->carbon_recurrences = null;
        }
    }
    /**
     * Create a filter tuple from raw parameters.
     *
     * Will create an automatic filter callback for one of Carbon's is* methods.
     */
    protected function create_filter_tuple(array $parameters): array
    {
        $method = array_shift($parameters);
        if (!$this->is_carbon_predicate_method($method)) {
            return [$method, array_shift($parameters)];
        }
        return [static fn($date) => [$date, $method](...$parameters), $method];
    }
    /**
     * Return whether given callable is a string pointing to one of Carbon's is* methods
     * and should be automatically converted to a filter callback.
     */
    protected function is_carbon_predicate_method(callable|string $callable): bool
    {
        return \is_string($callable) && str_starts_with($callable, 'is') && (method_exists($this->date_class, $callable) || [$this->date_class, 'hasMacro']($callable));
    }
    /**
     * Recurrences filter callback (limits number of recurrences).
     *
     * @SuppressWarnings(UnusedFormalParameter)
     */
    protected function filter_recurrences(Carbon_Interface $current, int $key): bool|callable
    {
        if ($key < $this->carbon_recurrences) {
            return true;
        }
        return static::END_ITERATION;
    }
    /**
     * End date filter callback.
     *
     * @return bool|static::END_ITERATION
     */
    protected function filter_end_date(Carbon_Interface $current): bool|callable
    {
        if (!$this->is_end_excluded() && $current == $this->end_date) {
            return true;
        }
        if ($this->date_interval->invert ? $current > $this->end_date : $current < $this->end_date) {
            return true;
        }
        return static::END_ITERATION;
    }
    /**
     * End iteration filter callback.
     *
     * @return static::END_ITERATION
     */
    protected function end_iteration(): callable
    {
        return static::END_ITERATION;
    }
    /**
     * Handle change of the parameters.
     */
    protected function handle_changed_parameters(): void
    {
        if ($this->get_options() & static::IMMUTABLE && $this->date_class === Carbon::class) {
            $this->date_class = Carbon_Immutable::class;
        } elseif (!($this->get_options() & static::IMMUTABLE) && $this->date_class === Carbon_Immutable::class) {
            $this->date_class = Carbon::class;
        }
        $this->validation_result = null;
    }
    /**
     * Validate current date and stop iteration when necessary.
     *
     * Returns true when current date is valid, false if it is not, or static::END_ITERATION
     * when iteration should be stopped.
     *
     * @return bool|static::END_ITERATION
     */
    protected function validate_current_date(): bool|callable
    {
        if ($this->carbon_current === null) {
            $this->rewind();
        }
        // Check after the first rewind to avoid repeating the initial validation.
        return $this->validation_result ?? $this->validation_result = $this->check_filters();
    }
    /**
     * Check whether current value and key pass all the filters.
     *
     * @return bool|static::END_ITERATION
     */
    protected function check_filters(): bool|callable
    {
        $current = $this->prepare_for_return($this->carbon_current);
        foreach ($this->filters as $tuple) {
            $result = \call_user_func($tuple[0], $current->avoid_mutation(), $this->key, $this);
            if ($result === static::END_ITERATION) {
                return static::END_ITERATION;
            }
            if (!$result) {
                return false;
            }
        }
        return true;
    }
    /**
     * Prepare given date to be returned to the external logic.
     *
     * @param CarbonInterface $date
     *
     * @return CarbonInterface
     */
    protected function prepare_for_return(Carbon_Interface $date)
    {
        $date = [$this->date_class, 'make']($date);
        if ($this->timezone) {
            return $date->set_timezone($this->timezone);
        }
        return $date;
    }
    /**
     * Keep incrementing the current date until a valid date is found or the iteration is ended.
     *
     * @throws RuntimeException
     */
    protected function increment_current_date_until_valid(): void
    {
        $attempts = 0;
        do {
            $this->carbon_current = $this->carbon_current->add($this->date_interval);
            $this->validation_result = null;
            if (++$attempts > static::NEXT_MAX_ATTEMPTS) {
                throw new Unreachable_Exception('Could not find next valid date.');
            }
        } while ($this->validate_current_date() === false);
    }
    /**
     * Call given macro.
     */
    protected function call_macro(string $name, array $parameters): mixed
    {
        $macro = static::$macros[$name];
        if ($macro instanceof Closure) {
            $bound_macro = @$macro->bind_to($this, static::class) ?: @$macro->bind_to(null, static::class);
            return ($bound_macro ?: $macro)(...$parameters);
        }
        return $macro(...$parameters);
    }
    /**
     * Return the Carbon instance passed through, a now instance in the same timezone
     * if null given or parse the input if string given.
     *
     * @param \Carbon\Carbon|\Carbon\CarbonPeriod|\Carbon\CarbonInterval|\DateInterval|\DatePeriod|\DateTimeInterface|string|null $date
     *
     * @return \Carbon\CarbonInterface
     */
    protected function resolve_carbon($date = null)
    {
        return $this->get_start_date()->now_with_same_tz()->carbonize($date);
    }
    /**
     * Resolve passed arguments or DatePeriod to a CarbonPeriod object.
     */
    protected function resolve_carbon_period(mixed $period, mixed ...$arguments): self
    {
        if ($period instanceof self) {
            return $period;
        }
        return $period instanceof DatePeriod ? static::instance($period) : static::create($period, ...$arguments);
    }
    private function order_couple($first, $second): array
    {
        return $first > $second ? [$second, $first] : [$first, $second];
    }
    private function make_date_time($value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }
        if ($value instanceof Week_Day || $value instanceof Month) {
            $date_class = $this->date_class;
            return new $date_class($value, $this->timezone_setting);
        }
        if (\is_string($value)) {
            $value = trim($value);
            if (!preg_match('/^P[\dT]/', $value) && !preg_match('/^R\d/', $value) && preg_match('/[a-z\d]/i', $value)) {
                $date_class = $this->date_class;
                return $date_class::parse($value, $this->timezone_setting);
            }
        }
        return null;
    }
    private function is_infinite_date($date): bool
    {
        return $date instanceof Carbon_Interface && ($date->is_end_of_time() || $date->is_start_of_time());
    }
    private function raw_date($date): ?DateTimeInterface
    {
        if ($date === false || $date === null) {
            return null;
        }
        if ($date instanceof Carbon_Interface) {
            return $date->is_mutable() ? $date->to_date_time() : $date->to_date_time_immutable();
        }
        if (\in_array(\get_class($date), [DateTime::class, DateTimeImmutable::class], true)) {
            return $date;
        }
        $class = $date instanceof DateTime ? DateTime::class : DateTimeImmutable::class;
        return new $class($date->format('Y-m-d H:i:s.u'), $date->get_timezone());
    }
    private static function set_default_parameters(array &$parameters, array $defaults): void
    {
        foreach ($defaults as [$index, $name, $value]) {
            if (!\array_key_exists($index, $parameters) && !\array_key_exists($name, $parameters)) {
                $parameters[$index] = $value;
            }
        }
    }
    private function set_from_associative_array(array $parameters): void
    {
        if (isset($parameters['start'])) {
            $this->set_start_date($parameters['start']);
        }
        if (isset($parameters['start'])) {
            $this->set_start_date($parameters['start']);
        }
        if (isset($parameters['end'])) {
            $this->set_end_date($parameters['end']);
        }
        if (isset($parameters['recurrences'])) {
            $this->set_recurrences($parameters['recurrences']);
        }
        if (isset($parameters['interval'])) {
            $this->set_date_interval($parameters['interval']);
        }
        if (isset($parameters['options'])) {
            $this->set_options($parameters['options']);
        }
    }
    private function configure_timezone(DateTimeZone $timezone, array $sorted_arguments, array $original_arguments): array
    {
        $this->set_timezone($timezone);
        if (\is_string($original_arguments['start'] ?? null)) {
            $sorted_arguments['start'] = $this->make_date_time($original_arguments['start']);
        }
        if (\is_string($original_arguments['end'] ?? null)) {
            $sorted_arguments['end'] = $this->make_date_time($original_arguments['end']);
        }
        return $sorted_arguments;
    }
    private function initialize_serialization(array $values): void
    {
        $serialization_base = ['start' => $values['start'] ?? $values['startDate'] ?? null, 'current' => $values['current'] ?? $values['carbonCurrent'] ?? null, 'end' => $values['end'] ?? $values['endDate'] ?? null, 'interval' => $values['interval'] ?? $values['dateInterval'] ?? null, 'recurrences' => max(1, (int) ($values['recurrences'] ?? $values['carbonRecurrences'] ?? 1)), 'include_start_date' => $values['include_start_date'] ?? true, 'include_end_date' => $values['include_end_date'] ?? false];
        foreach (['start', 'current', 'end'] as $date_property) {
            if ($serialization_base[$date_property] instanceof Carbon) {
                $serialization_base[$date_property] = $serialization_base[$date_property]->to_date_time();
            } elseif ($serialization_base[$date_property] instanceof Carbon_Interface) {
                $serialization_base[$date_property] = $serialization_base[$date_property]->to_date_time_immutable();
            }
        }
        if ($serialization_base['interval'] instanceof Carbon_Interval) {
            $serialization_base['interval'] = $serialization_base['interval']->to_date_interval();
        }
        // @codeCoverageIgnoreStart
        if (method_exists(parent::class, '__unserialize')) {
            parent::__unserialize($serialization_base);
            return;
        }
        $exclude_start = !($values['include_start_date'] ?? true);
        $include_end = $values['include_end_date'] ?? true;
        parent::__construct($serialization_base['start'], $serialization_base['interval'], $serialization_base['end'] ?? $serialization_base['recurrences'], ($exclude_start ? self::EXCLUDE_START_DATE : 0) | ($include_end && \defined('DatePeriod::INCLUDE_END_DATE') ? self::INCLUDE_END_DATE : 0));
        // @codeCoverageIgnoreEnd
    }
}