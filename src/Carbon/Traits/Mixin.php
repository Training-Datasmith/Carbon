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
use Carbon\Carbon_Interval;
use Carbon\Carbon_Period;
use Closure;
use Generator;
use ReflectionClass;
use Reflection_Exception;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
/**
 * Trait Mixin.
 *
 * Allows mixing in entire classes with multiple macros.
 */
trait Mixin
{
    /**
     * Stack of macro instance contexts.
     */
    protected static array $macro_context_stack = [];
    /**
     * Mix another object into the class.
     *
     * @example
     * ```
     * Carbon::mixin(new class {
     *   public function addMoon() {
     *     return function () {
     *       return $this->addDays(30);
     *     };
     *   }
     *   public function subMoon() {
     *     return function () {
     *       return $this->subDays(30);
     *     };
     *   }
     * });
     * $fullMoon = Carbon::create('2018-12-22');
     * $nextFullMoon = $fullMoon->addMoon();
     * $blackMoon = Carbon::create('2019-01-06');
     * $previousBlackMoon = $blackMoon->subMoon();
     * echo "$nextFullMoon\n";
     * echo "$previousBlackMoon\n";
     * ```
     *
     * @throws ReflectionException
     */
    public static function mixin(object|string $mixin): void
    {
        \is_string($mixin) && trait_exists($mixin) ? self::load_mixin_trait($mixin) : self::load_mixin_class($mixin);
    }
    /**
     * @throws ReflectionException
     */
    private static function load_mixin_class(object|string $mixin): void
    {
        $methods = (new ReflectionClass($mixin))->get_methods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);
        foreach ($methods as $method) {
            if (self::cannot_be_a_mixin_method($method)) {
                continue;
            }
            $macro = $method->invoke($mixin);
            if (\is_callable($macro)) {
                static::macro($method->name, $macro);
            }
        }
    }
    private static function cannot_be_a_mixin_method(ReflectionMethod $method): bool
    {
        if ($method->is_constructor() || $method->is_destructor()) {
            return true;
        }
        $return_type = $method->get_return_type();
        if ($return_type instanceof ReflectionNamedType) {
            $returned_type_name = $return_type->get_name();
            if ($return_type->is_builtin()) {
                return !\in_array($returned_type_name, [
                    'callable',
                    'object',
                    // could have __invoke
                    'array',
                    // could be [MyClass::class, 'myMethod']
                    'mixed',
                ], true);
            }
            // If it returns a non-invokable object, it cannot be a mixin method
            if (class_exists($returned_type_name)) {
                return !is_a($returned_type_name, Closure::class, true) && !\is_callable([$returned_type_name, '__invoke']);
            }
        }
        return false;
    }
    private static function load_mixin_trait(string $trait): void
    {
        if (!(new ReflectionClass($trait))->is_trait()) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid trait.', $trait));
        }
        $context = eval(self::get_anonymous_class_code_for_trait($trait));
        $class_name = $context::class;
        $base_class = static::class;
        foreach (self::get_mixable_methods($context) as $name) {
            $closure_base = Closure::from_callable([$context, $name]);
            static::macro($name, function (...$parameters) use ($closure_base, $class_name, $base_class) {
                $down_context = $this ?? new $base_class();
                $context = isset($this) ? $this->cast($class_name) : new $class_name();
                try {
                    // @ is required to handle error if not converted into exceptions
                    $closure = @$closure_base->bind_to($context);
                } catch (Throwable) {
                    // @codeCoverageIgnore
                    $closure = $closure_base;
                    // @codeCoverageIgnore
                }
                // in case of errors not converted into exceptions
                $closure = $closure ?: $closure_base;
                $result = $closure(...$parameters);
                if (!$result instanceof $class_name) {
                    return $result;
                }
                if ($down_context instanceof Carbon_Interface && $result instanceof Carbon_Interface) {
                    if ($context !== $result) {
                        $down_context = $down_context->copy();
                    }
                    return $down_context->set_timezone($result->get_timezone())->modify($result->format('Y-m-d H:i:s.u'))->settings($result->get_settings());
                }
                if ($down_context instanceof Carbon_Interval && $result instanceof Carbon_Interval) {
                    if ($context !== $result) {
                        $down_context = $down_context->copy();
                    }
                    $down_context->copy_properties($result);
                    self::copy_step($down_context, $result);
                    self::copy_negative_units($down_context, $result);
                    return $down_context->settings($result->get_settings());
                }
                if ($down_context instanceof Carbon_Period && $result instanceof Carbon_Period) {
                    if ($context !== $result) {
                        $down_context = $down_context->copy();
                    }
                    return $down_context->set_dates($result->get_start_date(), $result->get_end_date())->set_recurrences($result->get_recurrences())->set_options($result->get_options())->settings($result->get_settings());
                }
                return $result;
            });
        }
    }
    private static function get_anonymous_class_code_for_trait(string $trait): string
    {
        return 'return new class() extends ' . static::class . ' {use ' . $trait . ';};';
    }
    private static function get_mixable_methods(self $context): Generator
    {
        foreach (get_class_methods($context) as $name) {
            if (method_exists(static::class, $name)) {
                continue;
            }
            yield $name;
        }
    }
    /**
     * Stack a Carbon context from inside calls of self::this() and execute a given action.
     */
    protected static function bind_macro_context(?self $context, callable $callable): mixed
    {
        static::$macro_context_stack[] = $context;
        try {
            return $callable();
        } finally {
            array_pop(static::$macro_context_stack);
        }
    }
    /**
     * Return the current context from inside a macro callee or a null if static.
     */
    protected static function context(): ?static
    {
        return end(static::$macro_context_stack) ?: null;
    }
    /**
     * Return the current context from inside a macro callee or a new one if static.
     */
    protected static function this(): static
    {
        return end(static::$macro_context_stack) ?: new static();
    }
}