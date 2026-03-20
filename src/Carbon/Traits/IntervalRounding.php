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

use Carbon\Carbon_Interval;
use Carbon\Exceptions\Invalid_Interval_Exception;
use DateInterval;
/**
 * Trait to call rounding methods to interval or the interval of a period.
 */
trait Interval_Rounding
{
    protected function call_round_method(string $method, array $parameters): ?static
    {
        $action = substr($method, 0, 4);
        if ($action !== 'ceil') {
            $action = substr($method, 0, 5);
        }
        if (\in_array($action, ['round', 'floor', 'ceil'])) {
            return $this->{$action . 'Unit'}(substr($method, \strlen($action)), ...$parameters);
        }
        return null;
    }
    protected function round_with(DateInterval|string|float|int $precision, callable|string $function): ?static
    {
        $unit = 'second';
        if ($precision instanceof DateInterval) {
            $precision = Carbon_Interval::instance($precision)->for_humans(['locale' => 'en']);
        }
        if (\is_string($precision) && preg_match('/^\s*(?<precision>\d+)?\s*(?<unit>\w+)(?<other>\W.*)?$/', $precision, $match)) {
            if (trim($match['other'] ?? '') !== '') {
                throw new Invalid_Interval_Exception('Rounding is only possible with single unit intervals.');
            }
            $precision = (int) ($match['precision'] ?: 1);
            $unit = $match['unit'];
        }
        return $this->round_unit($unit, $precision, $function);
    }
}