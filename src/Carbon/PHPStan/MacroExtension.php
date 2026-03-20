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
namespace Carbon\Php_Stan;

use Carbon\Carbon_Interface;
use Carbon\Factory_Immutable;
use Closure;
use InvalidArgumentException;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Methods_Class_Reflection_Extension;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Closure_Type_Factory;
use ReflectionFunction;
use ReflectionMethod;
use stdClass;
use Throwable;
/**
 * Class MacroExtension.
 *
 * @codeCoverageIgnore Pure PHPStan wrapper.
 */
final class Macro_Extension implements Methods_Class_Reflection_Extension
{
    /**
     * Extension constructor.
     */
    public function __construct(protected Reflection_Provider $reflection_provider, protected Closure_Type_Factory $closure_type_factory)
    {
    }
    /**
     * {@inheritdoc}
     */
    public function has_method(Class_Reflection $class_reflection, string $method_name): bool
    {
        if ($class_reflection->get_name() !== Carbon_Interface::class && !$class_reflection->is_subclass_of(Carbon_Interface::class)) {
            return false;
        }
        $class_name = $class_reflection->get_name();
        return \is_callable([$class_name, 'hasMacro']) && $class_name::has_macro($method_name);
    }
    /**
     * {@inheritdoc}
     */
    public function get_method(Class_Reflection $class_reflection, string $method_name): Method_Reflection
    {
        $macros = Factory_Immutable::get_default_instance()->get_settings()['macros'] ?? [];
        $macro = $macros[$method_name] ?? throw new InvalidArgumentException("Macro '{$method_name}' not found");
        $static = false;
        $final = false;
        $deprecated = false;
        $doc_comment = null;
        if (\is_array($macro) && \count($macro) === 2 && \is_string($macro[1])) {
            \assert($macro[1] !== '');
            $reflection = new ReflectionMethod($macro[0], $macro[1]);
            $closure = \is_object($macro[0]) ? $reflection->get_closure($macro[0]) : $reflection->get_closure();
            $static = $reflection->is_static();
            $final = $reflection->is_final();
            $deprecated = $reflection->is_deprecated();
            $doc_comment = $reflection->get_doc_comment() ?: null;
        } elseif (\is_string($macro)) {
            $reflection = new ReflectionFunction($macro);
            $closure = $reflection->get_closure();
            $deprecated = $reflection->is_deprecated();
            $doc_comment = $reflection->get_doc_comment() ?: null;
        } elseif ($macro instanceof Closure) {
            $closure = $macro;
            try {
                $bound_closure = Closure::bind($closure, new stdClass());
                $static = !$bound_closure || (new ReflectionFunction($bound_closure))->get_closure_this() === null;
            } catch (Throwable) {
                $static = true;
            }
            $reflection = new ReflectionFunction($macro);
            $deprecated = $reflection->is_deprecated();
            $doc_comment = $reflection->get_doc_comment() ?: null;
        }
        if (!isset($closure)) {
            throw new InvalidArgumentException('Could not create reflection from the spec given');
            // @codeCoverageIgnore
        }
        $closure_type = $this->closure_type_factory->from_closure_object($closure);
        return new Macro_Method_Reflection($class_reflection, $method_name, $closure_type, $static, $final, $deprecated, $doc_comment);
    }
}