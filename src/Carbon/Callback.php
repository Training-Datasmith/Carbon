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

use Closure;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use ReflectionFunction;
use ReflectionNamedType;
use Reflection_Type;
final class Callback
{
    private ?ReflectionFunction $function = null;
    private function __construct(private readonly Closure $closure)
    {
    }
    public static function from_closure(Closure $closure): self
    {
        return new self($closure);
    }
    public static function parameter(mixed $closure, mixed $value, string|int $index = 0): mixed
    {
        if ($closure instanceof Closure) {
            return self::from_closure($closure)->prepare_parameter($value, $index);
        }
        return $value;
    }
    public function get_reflection_function(): ReflectionFunction
    {
        return $this->function ??= new ReflectionFunction($this->closure);
    }
    public function prepare_parameter(mixed $value, string|int $index = 0): mixed
    {
        $type = $this->get_parameter_type($index);
        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }
        $name = $type->get_name();
        if ($name === Carbon_Interface::class) {
            $name = $value instanceof DateTime ? Carbon::class : Carbon_Immutable::class;
        }
        if (!class_exists($name) || is_a($value, $name)) {
            return $value;
        }
        $class = $this->get_promoted_class($value);
        if ($class && is_a($name, $class, true)) {
            return $name::instance($value);
        }
        return $value;
    }
    public function call(mixed ...$arguments): mixed
    {
        foreach ($arguments as $index => &$value) {
            if ($this->get_promoted_class($value)) {
                $value = $this->prepare_parameter($value, $index);
            }
        }
        return ($this->closure)(...$arguments);
    }
    private function get_parameter_type(string|int $index): ?Reflection_Type
    {
        $parameters = $this->get_reflection_function()->get_parameters();
        if (\is_int($index)) {
            return ($parameters[$index] ?? null)?->get_type();
        }
        foreach ($parameters as $parameter) {
            if ($parameter->get_name() === $index) {
                return $parameter->get_type();
            }
        }
        return null;
    }
    /** @return class-string|null */
    private function get_promoted_class(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon_Interface::class;
        }
        if ($value instanceof DateInterval) {
            return Carbon_Interval::class;
        }
        if ($value instanceof DatePeriod) {
            return Carbon_Period::class;
        }
        if ($value instanceof DateTimeZone) {
            return Carbon_Time_Zone::class;
        }
        return null;
    }
}