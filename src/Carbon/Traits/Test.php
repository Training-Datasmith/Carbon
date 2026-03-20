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
use Carbon\Carbon_Time_Zone;
use Carbon\Factory;
use Carbon\Factory_Immutable;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
trait Test
{
    ///////////////////////////////////////////////////////////////////
    ///////////////////////// TESTING AIDS ////////////////////////////
    ///////////////////////////////////////////////////////////////////
    /**
     * Set a Carbon instance (real or mock) to be returned when a "now"
     * instance is created.  The provided instance will be returned
     * specifically under the following conditions:
     *   - A call to the static now() method, ex. Carbon::now()
     *   - When a null (or blank string) is passed to the constructor or parse(), ex. new Carbon(null)
     *   - When the string "now" is passed to the constructor or parse(), ex. new Carbon('now')
     *   - When a string containing the desired time is passed to Carbon::parse().
     *
     * Note the timezone parameter was left out of the examples above and
     * has no affect as the mock value will be returned regardless of its value.
     *
     * Only the moment is mocked with setTestNow(), the timezone will still be the one passed
     * as parameter of date_default_timezone_get() as a fallback (see setTestNowAndTimezone()).
     *
     * To clear the test instance call this method using the default
     * parameter of null.
     *
     * /!\ Use this method for unit tests only.
     *
     * @param DateTimeInterface|Closure|static|string|false|null $testNow real or mock Carbon instance
     */
    public static function set_test_now(mixed $test_now = null): void
    {
        Factory_Immutable::get_default_instance()->set_test_now($test_now);
    }
    /**
     * Set a Carbon instance (real or mock) to be returned when a "now"
     * instance is created.  The provided instance will be returned
     * specifically under the following conditions:
     *   - A call to the static now() method, ex. Carbon::now()
     *   - When a null (or blank string) is passed to the constructor or parse(), ex. new Carbon(null)
     *   - When the string "now" is passed to the constructor or parse(), ex. new Carbon('now')
     *   - When a string containing the desired time is passed to Carbon::parse().
     *
     * It will also align default timezone (e.g. call date_default_timezone_set()) with
     * the second argument or if null, with the timezone of the given date object.
     *
     * To clear the test instance call this method using the default
     * parameter of null.
     *
     * /!\ Use this method for unit tests only.
     *
     * @param DateTimeInterface|Closure|static|string|false|null $testNow real or mock Carbon instance
     */
    public static function set_test_now_and_timezone($test_now = null, $timezone = null): void
    {
        Factory_Immutable::get_default_instance()->set_test_now_and_timezone($test_now, $timezone);
    }
    /**
     * Temporarily sets a static date to be used within the callback.
     * Using setTestNow to set the date, executing the callback, then
     * clearing the test instance.
     *
     * /!\ Use this method for unit tests only.
     *
     * @template T
     *
     * @param DateTimeInterface|Closure|static|string|false|null $testNow  real or mock Carbon instance
     * @param Closure(): T                                       $callback
     *
     * @return T
     */
    public static function with_test_now(mixed $test_now, callable $callback): mixed
    {
        return Factory_Immutable::get_default_instance()->with_test_now($test_now, $callback);
    }
    /**
     * Get the Carbon instance (real or mock) to be returned when a "now"
     * instance is created.
     *
     * @return Closure|CarbonInterface|null the current instance used for testing
     */
    public static function get_test_now(): Closure|Carbon_Interface|null
    {
        return Factory_Immutable::get_instance()->get_test_now();
    }
    /**
     * Determine if there is a valid test instance set. A valid test instance
     * is anything that is not null.
     *
     * @return bool true if there is a test instance, otherwise false
     */
    public static function has_test_now(): bool
    {
        return Factory_Immutable::get_instance()->has_test_now();
    }
    /**
     * Get the mocked date passed in setTestNow() and if it's a Closure, execute it.
     */
    protected static function get_mocked_test_now(DateTimeZone|string|int|null $timezone): ?Carbon_Interface
    {
        $test_now = Factory_Immutable::get_instance()->handle_test_now_closure(static::get_test_now(), $timezone);
        if ($test_now === null) {
            return null;
        }
        $test_now = $test_now->avoid_mutation();
        return $timezone ? $test_now->set_timezone($timezone) : $test_now;
    }
    private function mock_constructor_parameters(&$time, ?Carbon_Time_Zone $timezone): void
    {
        $clock = $this->clock?->unwrap();
        $now = $clock instanceof Factory ? $clock->get_test_now() : $this->now_from_clock($timezone);
        $test_instance = $now ?? self::get_mocked_test_now_clone($timezone);
        if (!$test_instance) {
            return;
        }
        if ($test_instance instanceof DateTimeInterface) {
            $test_instance = $test_instance->set_timezone($timezone ?? date_default_timezone_get());
        }
        if (static::has_relative_keywords($time)) {
            $test_instance = $test_instance->modify($time);
        }
        $factory = $this->get_clock()?->unwrap();
        if (!$factory instanceof Factory) {
            $factory = Factory_Immutable::get_instance();
        }
        $test_instance = $factory->handle_test_now_closure($test_instance, $timezone);
        $time = $test_instance instanceof self ? $test_instance->raw_format(static::MOCK_DATETIME_FORMAT) : $test_instance->format(static::MOCK_DATETIME_FORMAT);
    }
    private static function get_mocked_test_now_clone(\DateTimeZone|string|int|null $timezone): ?\Carbon\Carbon_Interface
    {
        $mock = static::get_mocked_test_now($timezone);
        return $mock ? clone $mock : null;
    }
    private function now_from_clock(?Carbon_Time_Zone $timezone): ?DateTimeImmutable
    {
        $now = $this->clock?->now();
        return $now && $timezone ? $now->set_timezone($timezone) : null;
    }
}