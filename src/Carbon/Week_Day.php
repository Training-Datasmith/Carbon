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
enum Week_Day : int
{
    // Using constants is only safe starting from PHP 8.2
    case Sunday = 0;
    // CarbonInterface::SUNDAY
    case Monday = 1;
    // CarbonInterface::MONDAY
    case Tuesday = 2;
    // CarbonInterface::TUESDAY
    case Wednesday = 3;
    // CarbonInterface::WEDNESDAY
    case Thursday = 4;
    // CarbonInterface::THURSDAY
    case Friday = 5;
    // CarbonInterface::FRIDAY
    case Saturday = 6;
    // CarbonInterface::SATURDAY
    public static function int(self|int|null $value): ?int
    {
        return $value instanceof self ? $value->value : $value;
    }
    public static function from_number(int $number): self
    {
        $day = $number % Carbon_Interface::DAYS_PER_WEEK;
        return self::from($day + ($day < 0 ? Carbon_Interface::DAYS_PER_WEEK : 0));
    }
    public static function from_name(string $name, ?string $locale = null): self
    {
        try {
            return self::from(Carbon_Immutable::parse_from_locale($name, $locale)->day_of_week);
        } catch (Invalid_Format_Exception $exception) {
            // Possibly current language expect a dot after short name, but it's missing
            if ($locale !== null && !mb_strlen($name) < 4 && !str_ends_with($name, '.')) {
                try {
                    return self::from(Carbon_Immutable::parse_from_locale($name . '.', $locale)->day_of_week);
                } catch (Invalid_Format_Exception) {
                    // Throw previous error
                }
            }
            throw $exception;
        }
    }
    public function next(?Carbon_Immutable $now = null): Carbon_Immutable
    {
        return $now?->modify($this->name) ?? new Carbon_Immutable($this->name);
    }
    public function locale(string $locale, ?Carbon_Immutable $now = null): Carbon_Immutable
    {
        return $this->next($now)->locale($locale);
    }
}