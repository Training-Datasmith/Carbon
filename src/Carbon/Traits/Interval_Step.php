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

use Carbon\Callback;
use Carbon\Carbon;
use Carbon\Carbon_Immutable;
use Carbon\Carbon_Interface;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
trait Interval_Step
{
    /**
     * Step to apply instead of a fixed interval to get the new date.
     *
     * @var Closure|null
     */
    protected $step;
    /**
     * Get the dynamic step in use.
     *
     * @return Closure
     */
    public function get_step(): ?Closure
    {
        return $this->step;
    }
    /**
     * Set a step to apply instead of a fixed interval to get the new date.
     *
     * Or pass null to switch to fixed interval.
     */
    public function set_step(?Closure $step): void
    {
        $this->step = $step;
    }
    /**
     * Take a date and apply either the step if set, or the current interval else.
     *
     * The interval/step is applied negatively (typically subtraction instead of addition) if $negated is true.
     *
     *
     */
    public function convert_date(DateTimeInterface $date_time, bool $negated = false): Carbon_Interface
    {
        /** @var CarbonInterface $carbonDate */
        $carbon_date = $date_time instanceof Carbon_Interface ? $date_time : $this->resolve_carbon($date_time);
        if ($this->step) {
            $carbon_date = Callback::parameter($this->step, $carbon_date->avoid_mutation());
            return $carbon_date->modify(($this->step)($carbon_date, $negated)->format('Y-m-d H:i:s.u e O'));
        }
        if ($negated) {
            return $carbon_date->raw_sub($this);
        }
        return $carbon_date->raw_add($this);
    }
    /**
     * Convert DateTimeImmutable instance to CarbonImmutable instance and DateTime instance to Carbon instance.
     */
    private function resolve_carbon(DateTimeInterface $date_time): Carbon|Carbon_Immutable
    {
        if ($date_time instanceof DateTimeImmutable) {
            return Carbon_Immutable::instance($date_time);
        }
        return Carbon::instance($date_time);
    }
}