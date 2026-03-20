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

enum Unit : string
{
    case Microsecond = 'microsecond';
    case Millisecond = 'millisecond';
    case Second = 'second';
    case Minute = 'minute';
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Quarter = 'quarter';
    case Year = 'year';
    case Decade = 'decade';
    case Century = 'century';
    case Millennium = 'millennium';
    public static function to_name(self|string $unit): string
    {
        return $unit instanceof self ? $unit->value : $unit;
    }
    /** @internal */
    public static function to_name_if_unit(mixed $unit): mixed
    {
        return $unit instanceof self ? $unit->value : $unit;
    }
    public static function from_name(string $name, ?string $locale = null): self
    {
        if ($locale !== null) {
            $messages = Translator::get($locale)->get_messages($locale) ?? [];
            if ($messages !== []) {
                $lower_name = mb_strtolower($name);
                foreach (self::cases() as $unit) {
                    foreach (['', '_from_now', '_ago', '_after', '_before'] as $suffix) {
                        $message = $messages[$unit->value . $suffix] ?? null;
                        if (\is_string($message)) {
                            $words = explode('|', mb_strtolower((string) preg_replace('/[{\[\]].+?[}\[\]]/', '', str_replace(':count', '', $message))));
                            foreach ($words as $word) {
                                if (trim($word) === $lower_name) {
                                    return $unit;
                                }
                            }
                        }
                    }
                }
            }
        }
        return self::from(Carbon_Immutable::singular_unit($name));
    }
    public function singular(?string $locale = null): string
    {
        if ($locale !== null) {
            return trim((string) Translator::get($locale)->trans($this->value, ['%count%' => 1, ':count' => 1]), "1 \n\r\t\v\x00");
        }
        return $this->value;
    }
    public function plural(?string $locale = null): string
    {
        if ($locale !== null) {
            return trim((string) Translator::get($locale)->trans($this->value, ['%count%' => 9, ':count' => 9]), "9 \n\r\t\v\x00");
        }
        return Carbon_Immutable::plural_unit($this->value);
    }
    public function interval(int|float $value = 1): Carbon_Interval
    {
        return Carbon_Interval::from_string("{$value} {$this->name}");
    }
    public function locale(string $locale): Carbon_Interval
    {
        return $this->interval()->locale($locale);
    }
    public function to_period(...$params): Carbon_Period
    {
        return $this->interval()->to_period(...$params);
    }
    public function step_by(mixed $interval, Unit|string|null $unit = null): Carbon_Period
    {
        return $this->interval()->step_by($interval, $unit);
    }
}