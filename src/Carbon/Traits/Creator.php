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
use Carbon\Exceptions\Invalid_Date_Exception;
use Carbon\Exceptions\Invalid_Format_Exception;
use Carbon\Exceptions\Invalid_Time_Zone_Exception;
use Carbon\Exceptions\OutOfRangeException;
use Carbon\Exceptions\Unit_Exception;
use Carbon\Month;
use Carbon\Translator;
use Carbon\Week_Day;
use Closure;
use Date_Malformed_String_Exception;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Return_Type_Will_Change;
use Symfony\Contracts\Translation\Translator_Interface;
/**
 * Trait Creator.
 *
 * Static creators.
 *
 * Depends on the following methods:
 *
 * @method static Carbon|CarbonImmutable getTestNow()
 */
trait Creator
{
    use Object_Initialisation;
    use Local_Factory;
    /**
     * The errors that can occur.
     */
    protected static array|bool $last_errors = false;
    /**
     * Create a new Carbon instance.
     *
     * Please see the testing aids section (specifically static::setTestNow())
     * for more on the possibility of this constructor returning a test instance.
     *
     * @throws InvalidFormatException
     */
    public function __construct(DateTimeInterface|Week_Day|Month|string|int|float|null $time = null, DateTimeZone|string|int|null $timezone = null)
    {
        $this->init_local_factory();
        if ($time instanceof Month) {
            $time = $time->name . ' 1';
        } elseif ($time instanceof Week_Day) {
            $time = $time->name;
        } elseif ($time instanceof DateTimeInterface) {
            $time = $this->construct_timezone_from_date_time($time, $timezone)->format('Y-m-d H:i:s.u');
        }
        if (\is_string($time) && str_starts_with($time, '@')) {
            $time = static::create_from_timestamp_utc(substr($time, 1))->format('Y-m-d\TH:i:s.uP');
        } elseif (is_numeric($time) && (!\is_string($time) || !preg_match('/^\d{1,14}$/', $time))) {
            $time = static::create_from_timestamp_utc($time)->format('Y-m-d\TH:i:s.uP');
        }
        // If the class has a test now set, and we are trying to create a now()
        // instance then override as required
        $is_now = \in_array($time, [null, '', 'now'], true);
        $timezone = static::safe_create_date_time_zone($timezone) ?? null;
        if (($this->clock || method_exists(static::class, 'hasTestNow') && method_exists(static::class, 'getTestNow') && static::has_test_now()) && ($is_now || static::has_relative_keywords($time))) {
            $this->mock_constructor_parameters($time, $timezone);
        }
        try {
            parent::__construct($time ?? 'now', $timezone);
        } catch (Exception $exception) {
            throw new Invalid_Format_Exception($exception->get_message(), 0, $exception);
        }
        $this->constructed_object_id = spl_object_hash($this);
        self::set_last_errors(parent::get_last_errors());
    }
    /**
     * Get timezone from a datetime instance.
     */
    private function construct_timezone_from_date_time(DateTimeInterface $date, DateTimeZone|string|int|null &$timezone): DateTimeInterface
    {
        if ($timezone !== null) {
            $safe_tz = static::safe_create_date_time_zone($timezone);
            if ($safe_tz) {
                return ($date instanceof DateTimeImmutable ? $date : clone $date)->set_timezone($safe_tz);
            }
            return $date;
        }
        $timezone = $date->get_timezone();
        return $date;
    }
    /**
     * Update constructedObjectId on cloned.
     */
    public function __clone(): void
    {
        $this->constructed_object_id = spl_object_hash($this);
    }
    /**
     * Create a Carbon instance from a DateTime one.
     */
    public static function instance(DateTimeInterface $date): static
    {
        if ($date instanceof static) {
            return clone $date;
        }
        $instance = parent::create_from_format('U.u', $date->format('U.u'))->set_timezone($date->get_timezone());
        if ($date instanceof Carbon_Interface) {
            $settings = $date->get_settings();
            if (!$date->has_local_translator()) {
                unset($settings['locale']);
            }
            $instance->settings($settings);
        }
        return $instance;
    }
    /**
     * Create a carbon instance from a string.
     *
     * This is an alias for the constructor that allows better fluent syntax
     * as it allows you to do Carbon::parse('Monday next week')->fn() rather
     * than (new Carbon('Monday next week'))->fn().
     *
     * @throws InvalidFormatException
     */
    public static function raw_parse(DateTimeInterface|Week_Day|Month|string|int|float|null $time, DateTimeZone|string|int|null $timezone = null): static
    {
        if ($time instanceof DateTimeInterface) {
            return static::instance($time);
        }
        try {
            return new static($time, $timezone);
        } catch (Exception $exception) {
            // @codeCoverageIgnoreStart
            try {
                $date = @static::now($timezone)->change($time);
            } catch (Date_Malformed_String_Exception|Invalid_Format_Exception) {
                $date = null;
            }
            // @codeCoverageIgnoreEnd
            return $date ?? throw new Invalid_Format_Exception("Could not parse '{$time}': " . $exception->get_message(), 0, $exception);
        }
    }
    /**
     * Create a carbon instance from a string.
     *
     * This is an alias for the constructor that allows better fluent syntax
     * as it allows you to do Carbon::parse('Monday next week')->fn() rather
     * than (new Carbon('Monday next week'))->fn().
     *
     * @throws InvalidFormatException
     */
    public static function parse(DateTimeInterface|Week_Day|Month|string|int|float|null $time, DateTimeZone|string|int|null $timezone = null): static
    {
        $function = static::$parse_function;
        if (!$function) {
            return static::raw_parse($time, $timezone);
        }
        if (\is_string($function) && method_exists(static::class, $function)) {
            $function = [static::class, $function];
        }
        return $function(...\func_get_args());
    }
    /**
     * Create a carbon instance from a localized string (in French, Japanese, Arabic, etc.).
     *
     * @param string                       $time     date/time string in the given language (may also contain English).
     * @param string|null                  $locale   if locale is null or not specified, current global locale will be
     *                                               used instead.
     * @param DateTimeZone|string|int|null $timezone optional timezone for the new instance.
     *
     * @throws InvalidFormatException
     */
    public static function parse_from_locale(string $time, ?string $locale = null, DateTimeZone|string|int|null $timezone = null): static
    {
        return static::raw_parse(static::translate_time_string($time, $locale, static::DEFAULT_LOCALE), $timezone);
    }
    /**
     * Get a Carbon instance for the current date and time.
     */
    public static function now(DateTimeZone|string|int|null $timezone = null): static
    {
        return new static(null, $timezone);
    }
    /**
     * Create a Carbon instance for today.
     */
    public static function today(DateTimeZone|string|int|null $timezone = null): static
    {
        return static::raw_parse('today', $timezone);
    }
    /**
     * Create a Carbon instance for tomorrow.
     */
    public static function tomorrow(DateTimeZone|string|int|null $timezone = null): static
    {
        return static::raw_parse('tomorrow', $timezone);
    }
    /**
     * Create a Carbon instance for yesterday.
     */
    public static function yesterday(DateTimeZone|string|int|null $timezone = null): static
    {
        return static::raw_parse('yesterday', $timezone);
    }
    private static function assert_between($unit, $value, $min, $max): void
    {
        if (static::is_strict_mode_enabled() && ($value < $min || $value > $max)) {
            throw new OutOfRangeException($unit, $min, $max, $value);
        }
    }
    private static function create_now_instance($timezone)
    {
        if (!static::has_test_now()) {
            return static::now($timezone);
        }
        $now = static::get_test_now();
        if ($now instanceof Closure) {
            return $now(static::now($timezone));
        }
        $now = $now->avoid_mutation();
        return $timezone === null ? $now : $now->set_timezone($timezone);
    }
    /**
     * Create a new Carbon instance from a specific date and time.
     *
     * If any of $year, $month or $day are set to null their now() values will
     * be used.
     *
     * If $hour is null it will be set to its now() value and the default
     * values for $minute and $second will be their now() values.
     *
     * If $hour is not null then the default values for $minute and $second
     * will be 0.
     *
     * @param DateTimeInterface|string|int|null $year
     * @param int|null                          $month
     * @param int|null                          $day
     * @param int|null                          $hour
     * @param int|null                          $minute
     * @param int|null                          $second
     * @param DateTimeZone|string|int|null      $timezone
     *
     * @throws InvalidFormatException
     */
    public static function create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $timezone = null): ?static
    {
        $month = self::month_to_int($month);
        if (\is_string($year) && !is_numeric($year) || $year instanceof DateTimeInterface) {
            return static::parse($year, $timezone ?? (\is_string($month) || $month instanceof DateTimeZone ? $month : null));
        }
        $defaults = null;
        $get_default = function ($unit) use ($timezone, &$defaults) {
            if ($defaults === null) {
                $now = self::create_now_instance($timezone);
                $defaults = array_combine(['year', 'month', 'day', 'hour', 'minute', 'second'], explode('-', $now->raw_format('Y-n-j-G-i-s.u')));
            }
            return $defaults[$unit];
        };
        $year ??= $get_default('year');
        $month ??= $get_default('month');
        $day ??= $get_default('day');
        $hour ??= $get_default('hour');
        $minute ??= $get_default('minute');
        $second = (float) ($second ?? $get_default('second'));
        self::assert_between('month', $month, 0, 99);
        self::assert_between('day', $day, 0, 99);
        self::assert_between('hour', $hour, 0, 99);
        self::assert_between('minute', $minute, 0, 99);
        self::assert_between('second', $second, 0, 99);
        $fix_year = null;
        if ($year < 0) {
            $fix_year = $year;
            $year = 0;
        } elseif ($year > 9999) {
            $fix_year = $year - 9999;
            $year = 9999;
        }
        $second = ($second < 10 ? '0' : '') . number_format($second, 6);
        $instance = static::raw_create_from_format('!Y-n-j G:i:s.u', \sprintf('%s-%s-%s %s:%02s:%02s', $year, $month, $day, $hour, $minute, $second), $timezone);
        if ($instance && $fix_year !== null) {
            $instance = $instance->add_years($fix_year);
        }
        return $instance ?? null;
    }
    /**
     * Create a new safe Carbon instance from a specific date and time.
     *
     * If any of $year, $month or $day are set to null their now() values will
     * be used.
     *
     * If $hour is null it will be set to its now() value and the default
     * values for $minute and $second will be their now() values.
     *
     * If $hour is not null then the default values for $minute and $second
     * will be 0.
     *
     * If one of the set values is not valid, an InvalidDateException
     * will be thrown.
     *
     * @param int|null                     $year
     * @param int|null                     $month
     * @param int|null                     $day
     * @param int|null                     $hour
     * @param int|null                     $minute
     * @param int|null                     $second
     * @param DateTimeZone|string|int|null $timezone
     *
     * @throws InvalidDateException
     */
    public static function create_safe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $timezone = null): ?static
    {
        $month = self::month_to_int($month);
        $fields = static::get_ranges_by_unit();
        foreach ($fields as $field => $range) {
            if (${$field} !== null && (!\is_int(${$field}) || ${$field} < $range[0] || ${$field} > $range[1])) {
                if (static::is_strict_mode_enabled()) {
                    throw new Invalid_Date_Exception($field, ${$field});
                }
                return null;
            }
        }
        $instance = static::create($year, $month, $day, $hour, $minute, $second, $timezone);
        foreach (array_reverse($fields) as $field => $range) {
            if (${$field} !== null && (!\is_int(${$field}) || ${$field} !== $instance->{$field})) {
                if (static::is_strict_mode_enabled()) {
                    throw new Invalid_Date_Exception($field, ${$field});
                }
                return null;
            }
        }
        return $instance;
    }
    /**
     * Create a new Carbon instance from a specific date and time using strict validation.
     *
     * @see create()
     *
     * @param DateTimeZone|string|int|null $timezone
     *
     * @throws InvalidFormatException
     *
     */
    public static function create_strict(?int $year = 0, ?int $month = 1, ?int $day = 1, ?int $hour = 0, ?int $minute = 0, ?int $second = 0, $timezone = null): static
    {
        $initial_strict_mode = static::is_strict_mode_enabled();
        static::use_strict_mode(true);
        try {
            $date = static::create($year, $month, $day, $hour, $minute, $second, $timezone);
        } finally {
            static::use_strict_mode($initial_strict_mode);
        }
        return $date;
    }
    /**
     * Create a Carbon instance from just a date. The time portion is set to now.
     *
     * @param int|null                     $year
     * @param int|null                     $month
     * @param int|null                     $day
     * @param DateTimeZone|string|int|null $timezone
     *
     * @throws InvalidFormatException
     *
     * @return static
     */
    public static function create_from_date($year = null, $month = null, $day = null, $timezone = null): ?self
    {
        return static::create($year, $month, $day, null, null, null, $timezone);
    }
    /**
     * Create a Carbon instance from just a date. The time portion is set to midnight.
     *
     * @param int|null                     $year
     * @param int|null                     $month
     * @param int|null                     $day
     * @param DateTimeZone|string|int|null $timezone
     *
     * @throws InvalidFormatException
     *
     * @return static
     */
    public static function create_midnight_date($year = null, $month = null, $day = null, $timezone = null): ?self
    {
        return static::create($year, $month, $day, 0, 0, 0, $timezone);
    }
    /**
     * Create a Carbon instance from just a time. The date portion is set to today.
     *
     * @param int|null                     $hour
     * @param int|null                     $minute
     * @param int|null                     $second
     * @param DateTimeZone|string|int|null $timezone
     *
     * @throws InvalidFormatException
     */
    public static function create_from_time($hour = 0, $minute = 0, $second = 0, $timezone = null): static
    {
        return static::create(null, null, null, $hour, $minute, $second, $timezone);
    }
    /**
     * Create a Carbon instance from a time string. The date portion is set to today.
     *
     * @throws InvalidFormatException
     */
    public static function create_from_time_string(string $time, DateTimeZone|string|int|null $timezone = null): static
    {
        return static::today($timezone)->set_time_from_time_string($time);
    }
    private static function create_from_format_and_timezone(string $format, string $time, DateTimeZone|string|int|null $original_timezone): ?DateTimeInterface
    {
        if ($original_timezone === null) {
            return parent::create_from_format($format, $time) ?: null;
        }
        $timezone = \is_int($original_timezone) ? self::get_offset_timezone($original_timezone) : $original_timezone;
        $timezone = static::safe_create_date_time_zone($timezone, $original_timezone);
        return parent::create_from_format($format, $time, $timezone) ?: null;
    }
    private static function get_offset_timezone(int $offset): string
    {
        $minutes = (int) ($offset * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE);
        return @timezone_name_from_abbr('', $minutes, 1) ?: throw new Invalid_Time_Zone_Exception("Invalid offset timezone {$offset}");
    }
    /**
     * Create a Carbon instance from a specific format.
     *
     * @param string                       $format   Datetime format
     * @param DateTimeZone|string|int|null $timezone
     *
     * @throws InvalidFormatException
     *
     */
    public static function raw_create_from_format(string $format, string $time, $timezone = null): ?static
    {
        // Work-around for https://bugs.php.net/bug.php?id=80141
        $format = preg_replace('/(?<!\\\\)((?:\\\\{2})*)c/', '$1Y-m-d\TH:i:sP', $format);
        if (preg_match('/(?<!\\\\)(?:\\\\{2})*(a|A)/', (string) $format, $a_matches, PREG_OFFSET_CAPTURE) && preg_match('/(?<!\\\\)(?:\\\\{2})*(h|g|H|G)/', (string) $format, $h_matches, PREG_OFFSET_CAPTURE) && $a_matches[1][1] < $h_matches[1][1] && preg_match('/(am|pm|AM|PM)/', $time)) {
            $format = preg_replace('/^(.*)(?<!\\\\)((?:\\\\{2})*)(a|A)(.*)$/U', '$1$2$4 $3', (string) $format);
            $time = preg_replace('/^(.*)(am|pm|AM|PM)(.*)$/U', '$1$3 $2', $time);
        }
        if ($timezone === false) {
            $timezone = null;
        }
        // First attempt to create an instance, so that error messages are based on the unmodified format.
        $date = self::create_from_format_and_timezone($format, $time, $timezone);
        $last_errors = parent::get_last_errors();
        /** @var \Carbon\CarbonImmutable|\Carbon\Carbon|null $mock */
        $mock = static::get_mocked_test_now($timezone);
        if ($mock && $date instanceof DateTimeInterface) {
            // Set timezone from mock if custom timezone was neither given directly nor as a part of format.
            // First let's skip the part that will be ignored by the parser.
            $non_escaped = '(?<!\\\\)(\\\\{2})*';
            $non_ignored = preg_replace("/^.*{$non_escaped}!/s", '', (string) $format);
            if ($timezone === null && !preg_match("/{$non_escaped}[eOPT]/", (string) $non_ignored)) {
                $timezone = clone $mock->get_timezone();
            }
            $mock = $mock->copy();
            // Prepend mock datetime only if the format does not contain non escaped unix epoch reset flag.
            if (!preg_match("/{$non_escaped}[!|]/", (string) $format)) {
                if (preg_match('/[HhGgisvuB]/', (string) $format)) {
                    $mock = $mock->set_time(0, 0);
                }
                $format = static::MOCK_DATETIME_FORMAT . ' ' . $format;
                $time = ($mock instanceof self ? $mock->raw_format(static::MOCK_DATETIME_FORMAT) : $mock->format(static::MOCK_DATETIME_FORMAT)) . ' ' . $time;
            }
            // Regenerate date from the modified format to base result on the mocked instance instead of now.
            $date = self::create_from_format_and_timezone($format, $time, $timezone);
        }
        if ($date instanceof DateTimeInterface) {
            $instance = static::instance($date);
            $instance::set_last_errors($last_errors);
            return $instance;
        }
        if (static::is_strict_mode_enabled()) {
            throw new Invalid_Format_Exception(implode(PHP_EOL, (array) $last_errors['errors']));
        }
        return null;
    }
    /**
     * Create a Carbon instance from a specific format.
     *
     * @param string                       $format   Datetime format
     * @param string                       $time
     * @param DateTimeZone|string|int|null $timezone
     *
     * @throws InvalidFormatException
     */
    #[Return_Type_Will_Change]
    public static function create_from_format($format, $time, $timezone = null): ?static
    {
        $function = static::$create_from_format_function;
        // format is a single numeric unit
        if (\is_int($time) && \in_array(ltrim($format, '!'), ['U', 'Y', 'y', 'X', 'x', 'm', 'n', 'd', 'j', 'w', 'W', 'H', 'h', 'G', 'g', 'i', 's', 'u', 'z', 'v'], true)) {
            $time = (string) $time;
        }
        if (!\is_string($time)) {
            @trigger_error('createFromFormat() $time parameter will only accept string or integer for 1-letter format representing a numeric unit in the next version', \E_USER_DEPRECATED);
            $time = (string) $time;
        }
        if (!$function) {
            return static::raw_create_from_format($format, $time, $timezone);
        }
        if (\is_string($function) && method_exists(static::class, $function)) {
            $function = [static::class, $function];
        }
        return $function(...\func_get_args());
    }
    /**
     * Create a Carbon instance from a specific ISO format (same replacements as ->isoFormat()).
     *
     * @param string                       $format     Datetime format
     * @param DateTimeZone|string|int|null $timezone   optional timezone
     * @param string|null                  $locale     locale to be used for LTS, LT, LL, LLL, etc. macro-formats (en by fault, unneeded if no such macro-format in use)
     * @param TranslatorInterface|null     $translator optional custom translator to use for macro-formats
     *
     * @throws InvalidFormatException
     *
     */
    public static function create_from_iso_format(string $format, string $time, $timezone = null, ?string $locale = Carbon_Interface::DEFAULT_LOCALE, ?Translator_Interface $translator = null): ?static
    {
        $format = preg_replace_callback('/(?<!\\\\)(\\\\{2})*(LTS|LT|[Ll]{1,4})/', function ($match) use ($locale, $translator) {
            [$code] = $match;
            static $formats = null;
            if ($formats === null) {
                $translator ??= Translator::get($locale);
                $formats = ['LT' => static::get_translation_message_with($translator, 'formats.LT', $locale), 'LTS' => static::get_translation_message_with($translator, 'formats.LTS', $locale), 'L' => static::get_translation_message_with($translator, 'formats.L', $locale), 'LL' => static::get_translation_message_with($translator, 'formats.LL', $locale), 'LLL' => static::get_translation_message_with($translator, 'formats.LLL', $locale), 'LLLL' => static::get_translation_message_with($translator, 'formats.LLLL', $locale)];
            }
            return $formats[$code] ?? preg_replace_callback('/MMMM|MM|DD|dddd/', static fn(array $code): string => mb_substr((string) $code[0], 1), $formats[strtoupper($code)] ?? '');
        }, $format);
        $format = preg_replace_callback('/(?<!\\\\)(\\\\{2})*(' . Carbon_Interface::ISO_FORMAT_REGEXP . '|[A-Za-z])/', function ($match) {
            [$code] = $match;
            static $replacements = null;
            if ($replacements === null) {
                $replacements = ['OD' => 'd', 'OM' => 'M', 'OY' => 'Y', 'OH' => 'G', 'Oh' => 'g', 'Om' => 'i', 'Os' => 's', 'D' => 'd', 'DD' => 'd', 'Do' => 'd', 'd' => '!', 'dd' => '!', 'ddd' => 'D', 'dddd' => 'D', 'DDD' => 'z', 'DDDD' => 'z', 'DDDo' => 'z', 'e' => '!', 'E' => '!', 'H' => 'G', 'HH' => 'H', 'h' => 'g', 'hh' => 'h', 'k' => 'G', 'kk' => 'G', 'hmm' => 'gi', 'hmmss' => 'gis', 'Hmm' => 'Gi', 'Hmmss' => 'Gis', 'm' => 'i', 'mm' => 'i', 'a' => 'a', 'A' => 'a', 's' => 's', 'ss' => 's', 'S' => '*', 'SS' => '*', 'SSS' => '*', 'SSSS' => '*', 'SSSSS' => '*', 'SSSSSS' => 'u', 'SSSSSSS' => 'u*', 'SSSSSSSS' => 'u*', 'SSSSSSSSS' => 'u*', 'M' => 'm', 'MM' => 'm', 'MMM' => 'M', 'MMMM' => 'M', 'Mo' => 'm', 'Q' => '!', 'Qo' => '!', 'G' => '!', 'GG' => '!', 'GGG' => '!', 'GGGG' => '!', 'GGGGG' => '!', 'g' => '!', 'gg' => '!', 'ggg' => '!', 'gggg' => '!', 'ggggg' => '!', 'W' => '!', 'WW' => '!', 'Wo' => '!', 'w' => '!', 'ww' => '!', 'wo' => '!', 'x' => 'U???', 'X' => 'U', 'Y' => 'Y', 'YY' => 'y', 'YYYY' => 'Y', 'YYYYY' => 'Y', 'YYYYYY' => 'Y', 'z' => 'e', 'zz' => 'e', 'Z' => 'e', 'ZZ' => 'e'];
            }
            $format = $replacements[$code] ?? '?';
            if ($format === '!') {
                throw new Invalid_Format_Exception("Format {$code} not supported for creation.");
            }
            return $format;
        }, (string) $format);
        return static::raw_create_from_format($format, $time, $timezone);
    }
    /**
     * Create a Carbon instance from a specific format and a string in a given language.
     *
     * @param string                       $format   Datetime format
     * @param DateTimeZone|string|int|null $timezone
     * @throws InvalidFormatException
     *
     */
    public static function create_from_locale_format(string $format, string $locale, string $time, $timezone = null): ?static
    {
        $format = preg_replace_callback('/(?:\\\\[a-zA-Z]|[bfkqCEJKQRV]){2,}/', static function (array $match) use ($locale): string {
            $word = str_replace('\\', '', $match[0]);
            $translated_word = static::translate_time_string($word, $locale, static::DEFAULT_LOCALE);
            return $word === $translated_word ? $match[0] : preg_replace('/[a-zA-Z]/', '\\\\$0', $translated_word);
        }, $format);
        return static::raw_create_from_format($format, static::translate_time_string($time, $locale, static::DEFAULT_LOCALE), $timezone);
    }
    /**
     * Create a Carbon instance from a specific ISO format and a string in a given language.
     *
     * @param string                       $format   Datetime ISO format
     * @param DateTimeZone|string|int|null $timezone
     * @throws InvalidFormatException
     *
     */
    public static function create_from_locale_iso_format(string $format, string $locale, string $time, $timezone = null): ?static
    {
        $time = static::translate_time_string($time, $locale, static::DEFAULT_LOCALE, Carbon_Interface::TRANSLATE_MONTHS | Carbon_Interface::TRANSLATE_DAYS | Carbon_Interface::TRANSLATE_MERIDIEM);
        return static::create_from_iso_format($format, $time, $timezone, $locale);
    }
    /**
     * Make a Carbon instance from given variable if possible.
     *
     * Always return a new instance. Parse only strings and only these likely to be dates (skip intervals
     * and recurrences). Throw an exception for invalid format, but otherwise return null.
     *
     * @param mixed $var
     *
     * @throws InvalidFormatException
     */
    public static function make($var, DateTimeZone|string|null $timezone = null): ?static
    {
        if ($var instanceof DateTimeInterface) {
            return static::instance($var);
        }
        $date = null;
        if (\is_string($var)) {
            $var = trim($var);
            if (!preg_match('/^P[\dT]/', $var) && !preg_match('/^R\d/', $var) && preg_match('/[a-z\d]/i', $var)) {
                $date = static::parse($var, $timezone);
            }
        }
        return $date;
    }
    /**
     * Set last errors.
     *
     *
     */
    private static function set_last_errors(bool|array $last_errors): void
    {
        static::$last_errors = $last_errors;
    }
    /**
     * {@inheritdoc}
     */
    public static function get_last_errors(): array|false
    {
        return static::$last_errors;
    }
    private static function month_to_int(mixed $value, string $unit = 'month'): mixed
    {
        if ($value instanceof Month) {
            if ($unit !== 'month') {
                throw new Unit_Exception("Month enum cannot be used to set {$unit}");
            }
            return Month::int($value);
        }
        return $value;
    }
}