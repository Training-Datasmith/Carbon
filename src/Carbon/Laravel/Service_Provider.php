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
namespace Carbon\Laravel;

use Carbon\Carbon;
use Carbon\Carbon_Immutable;
use Carbon\Carbon_Interval;
use Carbon\Carbon_Period;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Events\Event_Dispatcher;
use Illuminate\Support\Carbon as IlluminateCarbon;
use Illuminate\Support\Facades\Date;
use Throwable;
class Service_Provider extends \Illuminate\Support\Service_Provider
{
    /** @var callable|null */
    protected $app_getter;
    /** @var callable|null */
    protected $locale_getter;
    /** @var callable|null */
    protected $fallback_locale_getter;
    public function set_app_getter(?callable $app_getter): void
    {
        $this->app_getter = $app_getter;
    }
    public function set_locale_getter(?callable $locale_getter): void
    {
        $this->locale_getter = $locale_getter;
    }
    public function set_fallback_locale_getter(?callable $fallback_locale_getter): void
    {
        $this->fallback_locale_getter = $fallback_locale_getter;
    }
    public function boot(): void
    {
        $this->update_locale();
        $this->update_fallback_locale();
        if (!$this->app->bound('events')) {
            return;
        }
        $service = $this;
        $events = $this->app['events'];
        if ($this->is_event_dispatcher($events)) {
            $events->listen(class_exists('Illuminate\Foundation\Events\LocaleUpdated') ? 'Illuminate\Foundation\Events\LocaleUpdated' : 'locale.changed', function () use ($service): void {
                $service->update_locale();
            });
        }
    }
    public function update_locale(): void
    {
        $locale = $this->get_locale();
        if ($locale === null) {
            return;
        }
        Carbon::set_locale($locale);
        Carbon_Immutable::set_locale($locale);
        Carbon_Period::set_locale($locale);
        Carbon_Interval::set_locale($locale);
        if (class_exists(Illuminate_Carbon::class)) {
            Illuminate_Carbon::set_locale($locale);
        }
        if (class_exists(Date::class)) {
            try {
                $root = Date::get_facade_root();
                $root->set_locale($locale);
            } catch (Throwable) {
                // Non Carbon class in use in Date facade
            }
        }
    }
    public function update_fallback_locale(): void
    {
        $locale = $this->get_fallback_locale();
        if ($locale === null) {
            return;
        }
        Carbon::set_fallback_locale($locale);
        Carbon_Immutable::set_fallback_locale($locale);
        Carbon_Period::set_fallback_locale($locale);
        Carbon_Interval::set_fallback_locale($locale);
        if (class_exists(Illuminate_Carbon::class) && method_exists(Illuminate_Carbon::class, 'setFallbackLocale')) {
            Illuminate_Carbon::set_fallback_locale($locale);
        }
        if (class_exists(Date::class)) {
            try {
                $root = Date::get_facade_root();
                $root->set_fallback_locale($locale);
            } catch (Throwable) {
                // @codeCoverageIgnore
                // Non Carbon class in use in Date facade
            }
        }
    }
    public function register(): void
    {
        // Needed for Laravel < 5.3 compatibility
    }
    protected function get_locale()
    {
        if ($this->locale_getter) {
            return ($this->locale_getter)();
        }
        $app = $this->get_app();
        $app = $app && method_exists($app, 'getLocale') ? $app : $this->get_global_app('translator');
        return $app ? $app->get_locale() : null;
    }
    protected function get_fallback_locale()
    {
        if ($this->fallback_locale_getter) {
            return ($this->fallback_locale_getter)();
        }
        $app = $this->get_app();
        return $app && method_exists($app, 'getFallbackLocale') ? $app->get_fallback_locale() : $this->get_global_app('translator')?->get_fallback();
    }
    protected function get_app()
    {
        if ($this->app_getter) {
            return ($this->app_getter)();
        }
        return $this->app ?? $this->get_global_app();
    }
    protected function get_global_app(...$args)
    {
        return \function_exists('app') ? \app(...$args) : null;
    }
    protected function is_event_dispatcher($instance)
    {
        return $instance instanceof Event_Dispatcher || $instance instanceof Dispatcher || $instance instanceof Dispatcher_Contract;
    }
}