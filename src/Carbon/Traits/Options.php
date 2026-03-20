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
use DateTimeInterface;
use Throwable;
/**
 * Trait Options.
 *
 * Embed base methods to change settings of Carbon classes.
 *
 * Depends on the following methods:
 *
 * @method static shiftTimezone($timezone) Set the timezone
 */
trait Options
{
    use Static_Options;
    use Localization;
    /**
     * Indicates if months should be calculated with overflow.
     * Specific setting.
     */
    protected ?bool $local_months_overflow = null;
    /**
     * Indicates if years should be calculated with overflow.
     * Specific setting.
     */
    protected ?bool $local_years_overflow = null;
    /**
     * Indicates if the strict mode is in use.
     * Specific setting.
     */
    protected ?bool $local_strict_mode_enabled = null;
    /**
     * Options for diffForHumans and forHumans methods.
     */
    protected ?int $local_human_diff_options = null;
    /**
     * Format to use on string cast.
     *
     * @var string|callable|null
     */
    protected $local_to_string_format;
    /**
     * Format to use on JSON serialization.
     *
     * @var string|callable|null
     */
    protected $local_serializer;
    /**
     * Instance-specific macros.
     */
    protected ?array $local_macros = null;
    /**
     * Instance-specific generic macros.
     */
    protected ?array $local_generic_macros = null;
    /**
     * Function to call instead of format.
     *
     * @var string|callable|null
     */
    protected $local_format_function;
    /**
     * Set specific options.
     *  - strictMode: true|false|null
     *  - monthOverflow: true|false|null
     *  - yearOverflow: true|false|null
     *  - humanDiffOptions: int|null
     *  - toStringFormat: string|Closure|null
     *  - toJsonFormat: string|Closure|null
     *  - locale: string|null
     *  - timezone: \DateTimeZone|string|int|null
     *  - macros: array|null
     *  - genericMacros: array|null
     *
     *
     */
    public function settings(array $settings): static
    {
        $this->local_strict_mode_enabled = $settings['strictMode'] ?? null;
        $this->local_months_overflow = $settings['monthOverflow'] ?? null;
        $this->local_years_overflow = $settings['yearOverflow'] ?? null;
        $this->local_human_diff_options = $settings['humanDiffOptions'] ?? null;
        $this->local_to_string_format = $settings['toStringFormat'] ?? null;
        $this->local_serializer = $settings['toJsonFormat'] ?? null;
        $this->local_macros = $settings['macros'] ?? null;
        $this->local_generic_macros = $settings['genericMacros'] ?? null;
        $this->local_format_function = $settings['formatFunction'] ?? null;
        if (isset($settings['locale'])) {
            $locales = $settings['locale'];
            if (!\is_array($locales)) {
                $locales = [$locales];
            }
            $this->locale(...$locales);
        } elseif (isset($settings['translator']) && property_exists($this, 'localTranslator')) {
            $this->local_translator = $settings['translator'];
        }
        if (isset($settings['innerTimezone'])) {
            return $this->set_timezone($settings['innerTimezone']);
        }
        if (isset($settings['timezone'])) {
            return $this->shift_timezone($settings['timezone']);
        }
        return $this;
    }
    /**
     * Returns current local settings.
     */
    public function get_settings(): array
    {
        $settings = [];
        $map = ['localStrictModeEnabled' => 'strictMode', 'localMonthsOverflow' => 'monthOverflow', 'localYearsOverflow' => 'yearOverflow', 'localHumanDiffOptions' => 'humanDiffOptions', 'localToStringFormat' => 'toStringFormat', 'localSerializer' => 'toJsonFormat', 'localMacros' => 'macros', 'localGenericMacros' => 'genericMacros', 'locale' => 'locale', 'tzName' => 'timezone', 'localFormatFunction' => 'formatFunction'];
        foreach ($map as $property => $key) {
            $value = $this->{$property} ?? null;
            if ($value !== null && ($key !== 'locale' || $value !== 'en' || $this->local_translator)) {
                $settings[$key] = $value;
            }
        }
        return $settings;
    }
    /**
     * Show truthy properties on var_dump().
     */
    public function __debugInfo(): array
    {
        $infos = array_filter(get_object_vars($this), static fn($var) => $var);
        foreach (['dumpProperties', 'constructedObjectId', 'constructed', 'originalInput'] as $property) {
            if (isset($infos[$property])) {
                unset($infos[$property]);
            }
        }
        $this->add_extra_debug_infos($infos);
        foreach (["\x00*\x00", ''] as $prefix) {
            $key = $prefix . 'carbonRecurrences';
            if (\array_key_exists($key, $infos)) {
                $infos['recurrences'] = $infos[$key];
                unset($infos[$key]);
            }
        }
        return $infos;
    }
    protected function is_local_strict_mode_enabled(): bool
    {
        return $this->local_strict_mode_enabled ?? $this->transmit_factory(static fn(): bool => static::is_strict_mode_enabled());
    }
    protected function add_extra_debug_infos(array &$infos): void
    {
        if ($this instanceof DateTimeInterface) {
            try {
                $infos['date'] ??= $this->format(Carbon_Interface::MOCK_DATETIME_FORMAT);
                $infos['timezone'] ??= $this->tz_name ?? $this->timezone_setting ?? $this->timezone ?? null;
            } catch (Throwable) {
                // noop
            }
        }
    }
}