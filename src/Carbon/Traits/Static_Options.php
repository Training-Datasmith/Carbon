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

use Carbon\Factory_Immutable;
/**
 * Options related to a static variable.
 */
trait Static_Options
{
    ///////////////////////////////////////////////////////////////////
    ///////////// Behavior customization for sub-classes //////////////
    ///////////////////////////////////////////////////////////////////
    /**
     * Function to call instead of format.
     *
     * @var string|callable|null
     */
    protected static $format_function;
    /**
     * Function to call instead of createFromFormat.
     *
     * @var string|callable|null
     */
    protected static $create_from_format_function;
    /**
     * Function to call instead of parse.
     *
     * @var string|callable|null
     */
    protected static $parse_function;
    ///////////////////////////////////////////////////////////////////
    ///////////// Use default factory for static options //////////////
    ///////////////////////////////////////////////////////////////////
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather use the ->settings() method.
     * @see settings
     *
     * Enable the strict mode (or disable with passing false).
     */
    public static function use_strict_mode(bool $strict_mode_enabled = true): void
    {
        Factory_Immutable::get_default_instance()->use_strict_mode($strict_mode_enabled);
    }
    /**
     * Returns true if the strict mode is globally in use, false else.
     * (It can be overridden in specific instances.)
     */
    public static function is_strict_mode_enabled(): bool
    {
        return Factory_Immutable::get_instance()->is_strict_mode_enabled();
    }
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather use the ->settings() method.
     *             Or you can use method variants: addMonthsWithOverflow/addMonthsNoOverflow, same variants
     *             are available for quarters, years, decade, centuries, millennia (singular and plural forms).
     * @see settings
     *
     * Indicates if months should be calculated with overflow.
     *
     *
     */
    public static function use_months_overflow(bool $months_overflow = true): void
    {
        Factory_Immutable::get_default_instance()->use_months_overflow($months_overflow);
    }
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather use the ->settings() method.
     *             Or you can use method variants: addMonthsWithOverflow/addMonthsNoOverflow, same variants
     *             are available for quarters, years, decade, centuries, millennia (singular and plural forms).
     * @see settings
     *
     * Reset the month overflow behavior.
     */
    public static function reset_months_overflow(): void
    {
        Factory_Immutable::get_default_instance()->reset_months_overflow();
    }
    /**
     * Get the month overflow global behavior (can be overridden in specific instances).
     */
    public static function should_overflow_months(): bool
    {
        return Factory_Immutable::get_instance()->should_overflow_months();
    }
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather use the ->settings() method.
     *             Or you can use method variants: addYearsWithOverflow/addYearsNoOverflow, same variants
     *             are available for quarters, years, decade, centuries, millennia (singular and plural forms).
     * @see settings
     *
     * Indicates if years should be calculated with overflow.
     *
     *
     */
    public static function use_years_overflow(bool $years_overflow = true): void
    {
        Factory_Immutable::get_default_instance()->use_years_overflow($years_overflow);
    }
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather use the ->settings() method.
     *             Or you can use method variants: addYearsWithOverflow/addYearsNoOverflow, same variants
     *             are available for quarters, years, decade, centuries, millennia (singular and plural forms).
     * @see settings
     *
     * Reset the month overflow behavior.
     */
    public static function reset_years_overflow(): void
    {
        Factory_Immutable::get_default_instance()->reset_years_overflow();
    }
    /**
     * Get the month overflow global behavior (can be overridden in specific instances).
     */
    public static function should_overflow_years(): bool
    {
        return Factory_Immutable::get_instance()->should_overflow_years();
    }
}