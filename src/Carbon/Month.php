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

use Carbon\Exceptions\Invalid_Format_Exception;
enum Month : int
{
    // Using constants is only safe starting from PHP 8.2
    case January = 1;
    // CarbonInterface::JANUARY
    case February = 2;
    // CarbonInterface::FEBRUARY
    case March = 3;
    // CarbonInterface::MARCH
    case April = 4;
    // CarbonInterface::APRIL
    case May = 5;
    // CarbonInterface::MAY
    case June = 6;
    // CarbonInterface::JUNE
    case July = 7;
    // CarbonInterface::JULY
    case August = 8;
    // CarbonInterface::AUGUST
    case September = 9;
    // CarbonInterface::SEPTEMBER
    case October = 10;
    // CarbonInterface::OCTOBER
    case November = 11;
    // CarbonInterface::NOVEMBER
    case December = 12;
    // CarbonInterface::DECEMBER
    public static function int(self|int|null $value): ?int
    {
        return $value instanceof self ? $value->value : $value;
    }
    public static function from_number(int $number): self
    {
        $month = $number % Carbon_Interface::MONTHS_PER_YEAR;
        return self::from($month + ($month < 1 ? Carbon_Interface::MONTHS_PER_YEAR : 0));
    }
    public static function from_name(string $name, ?string $locale = null): self
    {
        try {
            return self::from(Carbon_Immutable::parse_from_locale("{$name} 1", $locale)->month);
        } catch (Invalid_Format_Exception $exception) {
            // Possibly current language expect a dot after short name, but it's missing
            if ($locale !== null && !mb_strlen($name) < 4 && !str_ends_with($name, '.')) {
                try {
                    return self::from(Carbon_Immutable::parse_from_locale("{$name}. 1", $locale)->month);
                } catch (Invalid_Format_Exception) {
                    // Throw previous error
                }
            }
            throw $exception;
        }
    }
    public function of_the_year(Carbon_Immutable|int|null $now = null): Carbon_Immutable
    {
        if (\is_int($now)) {
            return Carbon_Immutable::create($now, $this->value);
        }
        $modifier = $this->name . ' 1st';
        return $now?->modify($modifier) ?? new Carbon_Immutable($modifier);
    }
    public function locale(string $locale, ?Carbon_Immutable $now = null): Carbon_Immutable
    {
        return $this->of_the_year($now)->locale($locale);
    }
}