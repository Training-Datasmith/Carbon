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
 * Trait Macros.
 *
 * Allows users to register macros within the Carbon class.
 */
trait Macro
{
    use Mixin;
    /**
     * Register a custom macro.
     *
     * Pass null macro to remove it.
     *
     * @example
     * ```
     * $userSettings = [
     *   'locale' => 'pt',
     *   'timezone' => 'America/Sao_Paulo',
     * ];
     * Carbon::macro('userFormat', function () use ($userSettings) {
     *   return $this->copy()->locale($userSettings['locale'])->tz($userSettings['timezone'])->calendar();
     * });
     * echo Carbon::yesterday()->hours(11)->userFormat();
     * ```
     *
     * @param-closure-this static $macro
     */
    public static function macro(string $name, ?callable $macro): void
    {
        Factory_Immutable::get_default_instance()->macro($name, $macro);
    }
    /**
     * Remove all macros and generic macros.
     */
    public static function reset_macros(): void
    {
        Factory_Immutable::get_default_instance()->reset_macros();
    }
    /**
     * Register a custom macro.
     *
     * @param int      $priority marco with higher priority is tried first
     *
     */
    public static function generic_macro(callable $macro, int $priority = 0): void
    {
        Factory_Immutable::get_default_instance()->generic_macro($macro, $priority);
    }
    /**
     * Checks if macro is registered globally.
     *
     *
     */
    public static function has_macro(string $name): bool
    {
        return Factory_Immutable::get_instance()->has_macro($name);
    }
    /**
     * Get the raw callable macro registered globally for a given name.
     */
    public static function get_macro(string $name): ?callable
    {
        return Factory_Immutable::get_instance()->get_macro($name);
    }
    /**
     * Checks if macro is registered globally or locally.
     */
    public function has_local_macro(string $name): bool
    {
        return $this->local_macros && isset($this->local_macros[$name]) || $this->transmit_factory(static fn(): bool => static::has_macro($name));
    }
    /**
     * Get the raw callable macro registered globally or locally for a given name.
     */
    public function get_local_macro(string $name): ?callable
    {
        return ($this->local_macros ?? [])[$name] ?? $this->transmit_factory(static fn(): ?callable => static::get_macro($name));
    }
}