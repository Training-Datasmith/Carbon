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

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Psr\Clock\Clock_Interface as PsrClockInterface;
use RuntimeException;
use Symfony\Component\Clock\Clock_Interface;
final class Wrapper_Clock implements Clock_Interface
{
    public function __construct(private Psr_Clock_Interface|Factory|DateTimeInterface $current_clock)
    {
    }
    public function unwrap(): Psr_Clock_Interface|Factory|DateTimeInterface
    {
        return $this->current_clock;
    }
    public function get_factory(): Factory
    {
        if ($this->current_clock instanceof Factory) {
            return $this->current_clock;
        }
        if ($this->current_clock instanceof DateTime) {
            $factory = new Factory();
            $factory->set_test_now_and_timezone($this->current_clock);
            return $factory;
        }
        if ($this->current_clock instanceof DateTimeImmutable) {
            $factory = new Factory_Immutable();
            $factory->set_test_now_and_timezone($this->current_clock);
            return $factory;
        }
        $factory = new Factory_Immutable();
        $factory->set_test_now_and_timezone(fn() => $this->current_clock->now());
        return $factory;
    }
    private function now_raw(): DateTimeInterface
    {
        if ($this->current_clock instanceof DateTimeInterface) {
            return $this->current_clock;
        }
        if ($this->current_clock instanceof Factory) {
            return $this->current_clock->__call('now', []);
        }
        return $this->current_clock->now();
    }
    public function now(): DateTimeImmutable
    {
        $now = $this->now_raw();
        return $now instanceof DateTimeImmutable ? $now : new Carbon_Immutable($now);
    }
    /**
     * @template T of CarbonInterface
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function now_as(string $class, DateTimeZone|string|int|null $timezone = null): Carbon_Interface
    {
        $now = $this->now_raw();
        $date = $now instanceof $class ? $now : $class::instance($now);
        return $timezone === null ? $date : $date->set_timezone($timezone);
    }
    public function now_as_carbon(DateTimeZone|string|int|null $timezone = null): Carbon_Interface
    {
        $now = $this->now_raw();
        return $now instanceof Carbon_Interface ? $timezone === null ? $now : $now->set_timezone($timezone) : $this->date_as_carbon($now, $timezone);
    }
    private function date_as_carbon(DateTimeInterface $date, DateTimeZone|string|int|null $timezone): Carbon_Interface
    {
        return $date instanceof DateTimeImmutable ? new Carbon_Immutable($date, $timezone) : new Carbon($date, $timezone);
    }
    public function sleep(float|int $seconds): void
    {
        if ($seconds === 0 || $seconds === 0.0) {
            return;
        }
        if ($seconds < 0) {
            throw new RuntimeException('Expected positive number of seconds, ' . $seconds . ' given');
        }
        if ($this->current_clock instanceof DateTimeInterface) {
            $this->current_clock = $this->add_seconds($this->current_clock, $seconds);
            return;
        }
        if ($this->current_clock instanceof Clock_Interface) {
            $this->current_clock->sleep($seconds);
            return;
        }
        $this->current_clock = $this->add_seconds($this->current_clock->now(), $seconds);
    }
    public function with_time_zone(DateTimeZone|string $timezone): static
    {
        if ($this->current_clock instanceof Clock_Interface) {
            return new self($this->current_clock->with_time_zone($timezone));
        }
        $now = $this->current_clock instanceof DateTimeInterface ? $this->current_clock : $this->current_clock->now();
        if (!$now instanceof DateTimeImmutable) {
            $now = clone $now;
        }
        if (\is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }
        return new self($now->set_timezone($timezone));
    }
    private function add_seconds(DateTimeInterface $date, float|int $seconds): DateTimeInterface
    {
        $seconds_per_hour = Carbon_Interface::SECONDS_PER_MINUTE * Carbon_Interface::MINUTES_PER_HOUR;
        $hours = number_format(floor($seconds / $seconds_per_hour), thousands_separator: '');
        $microseconds = number_format(($seconds - $hours * $seconds_per_hour) * Carbon_Interface::MICROSECONDS_PER_SECOND, thousands_separator: '');
        if (!$date instanceof DateTimeImmutable) {
            $date = clone $date;
        }
        if ($hours !== '0') {
            $date = $date->modify("{$hours} hours");
        }
        if ($microseconds !== '0') {
            return $date->modify("{$microseconds} microseconds");
        }
        return $date;
    }
}