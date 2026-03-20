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
use Carbon\Exceptions\Invalid_Type_Exception;
use Carbon\Exceptions\Not_Locale_Aware_Exception;
use Carbon\Language;
use Carbon\Translator;
use Carbon\Translator_Strong_Type_Interface;
use Closure;
use Symfony\Component\Translation\Translator_Bag_Interface;
use Symfony\Contracts\Translation\Locale_Aware_Interface;
use Symfony\Contracts\Translation\Translator_Interface;
/**
 * Trait Localization.
 *
 * Embed default and locale translators and translation base methods.
 */
trait Localization
{
    use Static_Localization;
    /**
     * Specific translator of the current instance.
     */
    protected ?Translator_Interface $local_translator = null;
    /**
     * Return true if the current instance has its own translator.
     */
    public function has_local_translator(): bool
    {
        return isset($this->local_translator);
    }
    /**
     * Get the translator of the current instance or the default if none set.
     */
    public function get_local_translator(): Translator_Interface
    {
        return $this->local_translator ?? $this->transmit_factory(static fn(): \Symfony\Contracts\Translation\Translator_Interface => static::get_translator());
    }
    /**
     * Set the translator for the current instance.
     */
    public function set_local_translator(Translator_Interface $translator): self
    {
        $this->local_translator = $translator;
        return $this;
    }
    /**
     * Returns raw translation message for a given key.
     *
     * @param TranslatorInterface|null $translator the translator to use
     * @param string                   $key        key to find
     * @param string|null              $locale     current locale used if null
     * @param string|null              $default    default value if translation returns the key
     *
     * @return string|Closure|null
     */
    public static function get_translation_message_with($translator, string $key, ?string $locale = null, ?string $default = null)
    {
        if (!($translator instanceof Translator_Bag_Interface && $translator instanceof Translator_Interface)) {
            throw new Invalid_Type_Exception('Translator does not implement ' . Translator_Interface::class . ' and ' . Translator_Bag_Interface::class . '. ' . get_debug_type($translator) . ' has been given.');
        }
        if (!$locale && $translator instanceof Locale_Aware_Interface) {
            $locale = $translator->get_locale();
        }
        $result = self::get_from_catalogue($translator, $translator->get_catalogue($locale), $key);
        return $result === $key ? $default : $result;
    }
    /**
     * Returns raw translation message for a given key.
     *
     * @param string              $key        key to find
     * @param string|null         $locale     current locale used if null
     * @param string|null         $default    default value if translation returns the key
     * @param TranslatorInterface $translator an optional translator to use
     *
     * @return string
     */
    public function get_translation_message(string $key, ?string $locale = null, ?string $default = null, $translator = null)
    {
        return static::get_translation_message_with($translator ?? $this->get_local_translator(), $key, $locale, $default);
    }
    /**
     * Translate using translation string or callback available.
     *
     * @param TranslatorInterface $translator an optional translator to use
     * @param string              $key        key to find
     * @param array               $parameters replacement parameters
     * @param int|float|null      $number     number if plural
     */
    public static function translate_with(Translator_Interface $translator, string $key, array $parameters = [], $number = null): string
    {
        $message = static::get_translation_message_with($translator, $key, null, $key);
        if ($message instanceof Closure) {
            return (string) $message(...array_values($parameters));
        }
        if ($number !== null) {
            $parameters['%count%'] = $number;
        }
        if (isset($parameters['%count%'])) {
            $parameters[':count'] = $parameters['%count%'];
        }
        return (string) $translator->trans($key, $parameters);
    }
    /**
     * Translate using translation string or callback available.
     *
     * @param string                   $key        key to find
     * @param array                    $parameters replacement parameters
     * @param string|int|float|null    $number     number if plural
     * @param TranslatorInterface|null $translator an optional translator to use
     * @param bool                     $altNumbers pass true to use alternative numbers
     */
    public function translate(string $key, array $parameters = [], string|int|float|null $number = null, ?Translator_Interface $translator = null, bool $alt_numbers = false): string
    {
        $translation = static::translate_with($translator ?? $this->get_local_translator(), $key, $parameters, $number);
        if ($number !== null && $alt_numbers) {
            return str_replace((string) $number, $this->translate_number((int) $number), $translation);
        }
        return $translation;
    }
    /**
     * Returns the alternative number for a given integer if available in the current locale.
     *
     *
     */
    public function translate_number(int $number): string
    {
        $translate_key = "alt_numbers.{$number}";
        $symbol = $this->translate($translate_key);
        if ($symbol !== $translate_key) {
            return $symbol;
        }
        if ($number > 99 && $this->translate('alt_numbers.99') !== 'alt_numbers.99') {
            $start = '';
            foreach ([10000, 1000, 100] as $exp) {
                $key = "alt_numbers_pow.{$exp}";
                if ($number >= $exp && $number < $exp * 10 && ($pow = $this->translate($key)) !== $key) {
                    $unit = floor($number / $exp);
                    $number -= $unit * $exp;
                    $start .= ($unit > 1 ? $this->translate("alt_numbers.{$unit}") : '') . $pow;
                }
            }
            $result = '';
            while ($number) {
                $chunk = $number % 100;
                $result = $this->translate("alt_numbers.{$chunk}") . $result;
                $number = floor($number / 100);
            }
            return "{$start}{$result}";
        }
        if ($number > 9 && $this->translate('alt_numbers.9') !== 'alt_numbers.9') {
            $result = '';
            while ($number) {
                $chunk = $number % 10;
                $result = $this->translate("alt_numbers.{$chunk}") . $result;
                $number = floor($number / 10);
            }
            return $result;
        }
        return (string) $number;
    }
    /**
     * Translate a time string from a locale to an other.
     *
     * @param string      $timeString date/time/duration string to translate (may also contain English)
     * @param string|null $from       input locale of the $timeString parameter (`Carbon::getLocale()` by default)
     * @param string|null $to         output locale of the result returned (`"en"` by default)
     * @param int         $mode       specify what to translate with options:
     *                                - CarbonInterface::TRANSLATE_ALL (default)
     *                                - CarbonInterface::TRANSLATE_MONTHS
     *                                - CarbonInterface::TRANSLATE_DAYS
     *                                - CarbonInterface::TRANSLATE_UNITS
     *                                - CarbonInterface::TRANSLATE_MERIDIEM
     *                                You can use pipe to group: CarbonInterface::TRANSLATE_MONTHS | CarbonInterface::TRANSLATE_DAYS
     */
    public static function translate_time_string(string $time_string, ?string $from = null, ?string $to = null, int $mode = Carbon_Interface::TRANSLATE_ALL): string
    {
        // Fallback source and destination locales
        $from = $from ?: static::get_locale();
        $to = $to ?: Carbon_Interface::DEFAULT_LOCALE;
        if ($from === $to) {
            return $time_string;
        }
        // Standardize apostrophe
        $time_string = strtr($time_string, ['’' => "'"]);
        $from_translations = [];
        $to_translations = [];
        foreach (['from', 'to'] as $key) {
            $language = ${$key};
            $translator = Translator::get($language);
            $translations = $translator->get_messages();
            if (!isset($translations[$language])) {
                return $time_string;
            }
            $translation_key = $key . 'Translations';
            $messages = $translations[$language];
            $months = $messages['months'] ?? [];
            $weekdays = $messages['weekdays'] ?? [];
            $meridiem = $messages['meridiem'] ?? ['AM', 'PM'];
            if (isset($messages['ordinal_words'])) {
                $time_string = self::replace_ordinal_words($time_string, $key === 'from' ? array_flip($messages['ordinal_words']) : $messages['ordinal_words']);
            }
            if ($key === 'from') {
                foreach (['months', 'weekdays'] as $variable) {
                    $list = $messages[$variable . '_standalone'] ?? null;
                    if ($list) {
                        foreach (${$variable} as $index => &$name) {
                            $name .= '|' . $list[$index];
                        }
                    }
                }
            }
            ${$translation_key} = array_merge($mode & Carbon_Interface::TRANSLATE_MONTHS ? self::get_translation_array($months, static::MONTHS_PER_YEAR, $time_string) : [], $mode & Carbon_Interface::TRANSLATE_MONTHS ? self::get_translation_array($messages['months_short'] ?? [], static::MONTHS_PER_YEAR, $time_string) : [], $mode & Carbon_Interface::TRANSLATE_DAYS ? self::get_translation_array($weekdays, static::DAYS_PER_WEEK, $time_string) : [], $mode & Carbon_Interface::TRANSLATE_DAYS ? self::get_translation_array($messages['weekdays_short'] ?? [], static::DAYS_PER_WEEK, $time_string) : [], $mode & Carbon_Interface::TRANSLATE_DIFF ? self::translate_words_by_keys(['diff_now', 'diff_today', 'diff_yesterday', 'diff_tomorrow', 'diff_before_yesterday', 'diff_after_tomorrow'], $messages, $key) : [], $mode & Carbon_Interface::TRANSLATE_UNITS ? self::translate_words_by_keys(['year', 'month', 'week', 'day', 'hour', 'minute', 'second'], $messages, $key) : [], $mode & Carbon_Interface::TRANSLATE_MERIDIEM ? array_map(function (int $hour) use ($meridiem) {
                if (\is_array($meridiem)) {
                    return $meridiem[$hour < static::HOURS_PER_DAY / 2 ? 0 : 1];
                }
                return $meridiem($hour, 0, false);
            }, range(0, 23)) : []);
        }
        return substr((string) preg_replace_callback('/(?<=[\d\s+.\/,_-])(' . implode('|', $from_translations) . ')(?=[\d\s+.\/,_-])/iu', function ($match) use ($from_translations, $to_translations): string {
            [$chunk] = $match;
            foreach ($from_translations as $index => $word) {
                if (preg_match("/^{$word}\$/iu", $chunk)) {
                    return $to_translations[$index] ?? '';
                }
            }
            return $chunk;
            // @codeCoverageIgnore
        }, " {$time_string} "), 1, -1);
    }
    /**
     * Translate a time string from the current locale (`$date->locale()`) to another one.
     *
     * @param string      $timeString time string to translate
     * @param string|null $to         output locale of the result returned ("en" by default)
     */
    public function translate_time_string_to(string $time_string, ?string $to = null): string
    {
        return static::translate_time_string($time_string, $this->get_translator_locale(), $to);
    }
    /**
     * Get/set the locale for the current instance.
     *
     *
     * @return $this|string
     */
    public function locale(?string $locale = null, string ...$fallback_locales): static|string
    {
        if ($locale === null) {
            return $this->get_translator_locale();
        }
        if (!$this->local_translator || $this->get_translator_locale($this->local_translator) !== $locale) {
            $translator = Translator::get($locale);
            if (!empty($fallback_locales)) {
                $translator->set_fallback_locales($fallback_locales);
                foreach ($fallback_locales as $fallback_locale) {
                    $messages = Translator::get($fallback_locale)->get_messages();
                    if (isset($messages[$fallback_locale])) {
                        $translator->set_messages($fallback_locale, $messages[$fallback_locale]);
                    }
                }
            }
            $this->local_translator = $translator;
        }
        return $this;
    }
    /**
     * Get the current translator locale.
     */
    public static function get_locale(): string
    {
        return static::get_locale_aware_translator()->get_locale();
    }
    /**
     * Set the current translator locale and indicate if the source locale file exists.
     * Pass 'auto' as locale to use the closest language to the current LC_TIME locale.
     *
     * @param string $locale locale ex. en
     */
    public static function set_locale(string $locale): void
    {
        static::get_locale_aware_translator()->set_locale($locale);
    }
    /**
     * Set the fallback locale.
     *
     * @see https://symfony.com/doc/current/components/translation.html#fallback-locales
     */
    public static function set_fallback_locale(string $locale): void
    {
        $translator = static::get_translator();
        if (method_exists($translator, 'setFallbackLocales')) {
            $translator->set_fallback_locales([$locale]);
            if ($translator instanceof Translator) {
                $preferred_locale = $translator->get_locale();
                $fallback_messages = [];
                $preferred_messages = $translator->get_messages($preferred_locale);
                foreach (Translator::get($locale)->get_messages()[$locale] ?? [] as $key => $value) {
                    if (preg_match('/^(?:a_)?(.+)_(?:standalone|ago|from_now|before|after|short|min)$/', (string) $key, $match) && isset($preferred_messages[$match[1]])) {
                        continue;
                    }
                    $fallback_messages[$key] = $value;
                }
                $translator->set_messages($preferred_locale, array_replace_recursive($translator->get_messages()[$locale] ?? [], $fallback_messages, $preferred_messages));
            }
        }
    }
    /**
     * Get the fallback locale.
     *
     * @see https://symfony.com/doc/current/components/translation.html#fallback-locales
     */
    public static function get_fallback_locale(): ?string
    {
        $translator = static::get_translator();
        if (method_exists($translator, 'getFallbackLocales')) {
            return $translator->get_fallback_locales()[0] ?? null;
        }
        return null;
    }
    /**
     * Set the current locale to the given, execute the passed function, reset the locale to previous one,
     * then return the result of the closure (or null if the closure was void).
     *
     * @param string   $locale locale ex. en
     *
     */
    public static function execute_with_locale(string $locale, callable $func): mixed
    {
        $current_locale = static::get_locale();
        static::set_locale($locale);
        $new_locale = static::get_locale();
        $result = $func($new_locale === 'en' && strtolower(substr($locale, 0, 2)) !== 'en' ? false : $new_locale, static::get_translator());
        static::set_locale($current_locale);
        return $result;
    }
    /**
     * Returns true if the given locale is internally supported and has short-units support.
     * Support is considered enabled if either year, day or hour has a short variant translated.
     *
     * @param string $locale locale ex. en
     */
    public static function locale_has_short_units(string $locale): bool
    {
        return static::execute_with_locale($locale, fn($new_locale, Translator_Interface $translator) => $new_locale && (($y = static::translate_with($translator, 'y')) !== 'y' && $y !== static::translate_with($translator, 'year')) || ($y = static::translate_with($translator, 'd')) !== 'd' && $y !== static::translate_with($translator, 'day') || ($y = static::translate_with($translator, 'h')) !== 'h' && $y !== static::translate_with($translator, 'hour'));
    }
    /**
     * Returns true if the given locale is internally supported and has diff syntax support (ago, from now, before, after).
     * Support is considered enabled if the 4 sentences are translated in the given locale.
     *
     * @param string $locale locale ex. en
     */
    public static function locale_has_diff_syntax(string $locale): bool
    {
        return static::execute_with_locale($locale, function ($new_locale, Translator_Interface $translator): bool {
            if (!$new_locale) {
                return false;
            }
            foreach (['ago', 'from_now', 'before', 'after'] as $key) {
                if ($translator instanceof Translator_Bag_Interface && self::get_from_catalogue($translator, $translator->get_catalogue($new_locale), $key) instanceof Closure) {
                    continue;
                }
                if ($translator->trans($key) === $key) {
                    return false;
                }
            }
            return true;
        });
    }
    /**
     * Returns true if the given locale is internally supported and has words for 1-day diff (just now, yesterday, tomorrow).
     * Support is considered enabled if the 3 words are translated in the given locale.
     *
     * @param string $locale locale ex. en
     */
    public static function locale_has_diff_one_day_words(string $locale): bool
    {
        return static::execute_with_locale($locale, fn($new_locale, Translator_Interface $translator) => $new_locale && $translator->trans('diff_now') !== 'diff_now' && $translator->trans('diff_yesterday') !== 'diff_yesterday' && $translator->trans('diff_tomorrow') !== 'diff_tomorrow');
    }
    /**
     * Returns true if the given locale is internally supported and has words for 2-days diff (before yesterday, after tomorrow).
     * Support is considered enabled if the 2 words are translated in the given locale.
     *
     * @param string $locale locale ex. en
     */
    public static function locale_has_diff_two_day_words(string $locale): bool
    {
        return static::execute_with_locale($locale, fn($new_locale, Translator_Interface $translator) => $new_locale && $translator->trans('diff_before_yesterday') !== 'diff_before_yesterday' && $translator->trans('diff_after_tomorrow') !== 'diff_after_tomorrow');
    }
    /**
     * Returns true if the given locale is internally supported and has period syntax support (X times, every X, from X, to X).
     * Support is considered enabled if the 4 sentences are translated in the given locale.
     *
     * @param string $locale locale ex. en
     *
     * @return bool
     */
    public static function locale_has_period_syntax(string $locale): mixed
    {
        return static::execute_with_locale($locale, fn($new_locale, Translator_Interface $translator) => $new_locale && $translator->trans('period_recurrences') !== 'period_recurrences' && $translator->trans('period_interval') !== 'period_interval' && $translator->trans('period_start_date') !== 'period_start_date' && $translator->trans('period_end_date') !== 'period_end_date');
    }
    /**
     * Returns the list of internally available locales and already loaded custom locales.
     * (It will ignore custom translator dynamic loading.)
     */
    public static function get_available_locales(): array
    {
        $translator = static::get_locale_aware_translator();
        return $translator instanceof Translator ? $translator->get_available_locales() : [$translator->get_locale()];
    }
    /**
     * Returns list of Language object for each available locale. This object allow you to get the ISO name, native
     * name, region and variant of the locale.
     *
     * @return Language[]
     */
    public static function get_available_locales_info(): array
    {
        $languages = [];
        foreach (static::get_available_locales() as $id) {
            $languages[$id] = new Language($id);
        }
        return $languages;
    }
    /**
     * Get the locale of a given translator.
     *
     * If null or omitted, current local translator is used.
     * If no local translator is in use, current global translator is used.
     */
    protected function get_translator_locale($translator = null): ?string
    {
        if (\func_num_args() === 0) {
            $translator = $this->get_local_translator();
        }
        $translator = static::get_locale_aware_translator($translator);
        return $translator?->get_locale();
    }
    /**
     * Throw an error if passed object is not LocaleAwareInterface.
     *
     * @param LocaleAwareInterface|null $translator
     *
     * @return LocaleAwareInterface|null
     */
    protected static function get_locale_aware_translator($translator = null)
    {
        if (\func_num_args() === 0) {
            $translator = static::get_translator();
        }
        if ($translator && !($translator instanceof Locale_Aware_Interface || method_exists($translator, 'getLocale'))) {
            throw new Not_Locale_Aware_Exception($translator);
            // @codeCoverageIgnore
        }
        return $translator;
    }
    /**
     * @param mixed                                                    $translator
     * @param \Symfony\Component\Translation\MessageCatalogueInterface $catalogue
     *
     * @return mixed
     */
    private static function get_from_catalogue($translator, $catalogue, string $id, string $domain = 'messages')
    {
        return $translator instanceof Translator_Strong_Type_Interface ? $translator->get_from_catalogue($catalogue, $id, $domain) : $catalogue->get($id, $domain);
        // @codeCoverageIgnore
    }
    /**
     * Return the word cleaned from its translation codes.
     *
     * @param string $word
     */
    private static function clean_word_from_translation_string($word): string
    {
        $word = str_replace([':count', '%count', ':time'], '', $word);
        $word = strtr($word, ['’' => "'"]);
        $word = preg_replace('/\{(?:-?\d+(?:\.\d+)?|-?Inf)(?:,(?:-?\d+|-?Inf))?}|[\[\]](?:-?\d+(?:\.\d+)?|-?Inf)(?:,(?:-?\d+|-?Inf))?[\[\]]/', '', $word);
        return trim((string) $word);
    }
    /**
     * Translate a list of words.
     *
     * @param string[] $keys     keys to translate.
     * @param string[] $messages messages bag handling translations.
     * @param string   $key      'to' (to get the translation) or 'from' (to get the detection RegExp pattern).
     *
     * @return string[]
     */
    private static function translate_words_by_keys($keys, $messages, $key): array
    {
        return array_map(function (string $word_key) use ($messages, $key) {
            $message = $key === 'from' && isset($messages[$word_key . '_regexp']) ? $messages[$word_key . '_regexp'] : $messages[$word_key] ?? null;
            if (!$message) {
                return '>>DO NOT REPLACE<<';
            }
            $parts = explode('|', $message);
            return $key === 'to' ? self::clean_word_from_translation_string(end($parts)) : '(?:' . implode('|', array_map(static::clean_word_from_translation_string(...), $parts)) . ')';
        }, $keys);
    }
    /**
     * Get an array of translations based on the current date.
     *
     * @param callable $translation
     * @param int      $length
     * @param string   $timeString
     *
     * @return string[]
     */
    private static function get_translation_array($translation, $length, $time_string): array
    {
        $filler = '>>DO NOT REPLACE<<';
        if (\is_array($translation)) {
            return array_pad($translation, $length, $filler);
        }
        $list = [];
        $date = static::now();
        for ($i = 0; $i < $length; $i++) {
            $list[] = $translation($date, $time_string, $i) ?? $filler;
        }
        return $list;
    }
    private static function replace_ordinal_words(string $time_string, array $ordinal_words): string
    {
        return preg_replace_callback('/(?<![a-z])[a-z]+(?![a-z])/i', fn(array $match) => $ordinal_words[mb_strtolower((string) $match[0])] ?? $match[0], $time_string);
    }
}