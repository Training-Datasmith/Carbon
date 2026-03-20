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

use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Parameters_Acceptor;
use Php_Stan\Trinary_Logic;
use Php_Stan\Type\Type;
use function preg_match;
class Macro_Method_Reflection implements Method_Reflection
{
    public function __construct(private readonly Class_Reflection $declaring_class, private readonly string $method_name, private readonly Parameters_Acceptor $macro_closure_type, private readonly bool $static, private readonly bool $final, private readonly bool $deprecated, private readonly ?string $doc_comment)
    {
    }
    public function get_declaring_class(): Class_Reflection
    {
        return $this->declaring_class;
    }
    public function is_static(): bool
    {
        return $this->static;
    }
    public function is_private(): bool
    {
        return false;
    }
    public function is_public(): bool
    {
        return true;
    }
    public function get_doc_comment(): ?string
    {
        return $this->doc_comment;
    }
    public function get_name(): string
    {
        return $this->method_name;
    }
    public function get_prototype(): \Php_Stan\Reflection\Class_Member_Reflection
    {
        return $this;
    }
    public function get_variants(): array
    {
        return [$this->macro_closure_type];
    }
    public function is_deprecated(): Trinary_Logic
    {
        return Trinary_Logic::create_from_boolean($this->deprecated || preg_match('/@deprecated/i', $this->get_doc_comment() ?: ''));
    }
    public function get_deprecated_description(): ?string
    {
        return null;
    }
    public function is_final(): Trinary_Logic
    {
        return Trinary_Logic::create_from_boolean($this->final);
    }
    public function is_internal(): Trinary_Logic
    {
        return Trinary_Logic::create_no();
    }
    public function get_throw_type(): ?Type
    {
        return null;
    }
    public function has_side_effects(): Trinary_Logic
    {
        return Trinary_Logic::create_maybe();
    }
}