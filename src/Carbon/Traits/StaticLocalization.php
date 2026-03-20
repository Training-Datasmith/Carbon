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
use Symfony\Contracts\Translation\Translator_Interface;
/**
 * Static config for localization.
 */
trait Static_Localization
{
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather use the ->settings() method.
     * @see settings
     */
    public static function set_human_diff_options(int $human_diff_options): void
    {
        Factory_Immutable::get_default_instance()->set_human_diff_options($human_diff_options);
    }
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather use the ->settings() method.
     * @see settings
     */
    public static function enable_human_diff_option(int $human_diff_option): void
    {
        Factory_Immutable::get_default_instance()->enable_human_diff_option($human_diff_option);
    }
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather use the ->settings() method.
     * @see settings
     */
    public static function disable_human_diff_option(int $human_diff_option): void
    {
        Factory_Immutable::get_default_instance()->disable_human_diff_option($human_diff_option);
    }
    /**
     * Return default humanDiff() options (merged flags as integer).
     */
    public static function get_human_diff_options(): int
    {
        return Factory_Immutable::get_instance()->get_human_diff_options();
    }
    /**
     * Set the default translator instance to use.
     *
     *
     */
    public static function set_translator(Translator_Interface $translator): void
    {
        Factory_Immutable::get_default_instance()->set_translator($translator);
    }
    /**
     * Initialize the default translator instance if necessary.
     */
    public static function get_translator(): Translator_Interface
    {
        return Factory_Immutable::get_instance()->get_translator();
    }
}