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

use Carbon\Factory_Immutable;
use Closure;
/**
 * Trait ToStringFormat.
 *
 * Handle global format customization for string cast of the object.
 */
trait To_String_Format
{
    /**
     * Reset the format used to the default when type juggling a Carbon instance to a string
     */
    public static function reset_to_string_format(): void
    {
        Factory_Immutable::get_default_instance()->reset_to_string_format();
    }
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather let Carbon object being cast to string with DEFAULT_TO_STRING_FORMAT, and
     *             use other method or custom format passed to format() method if you need to dump another string
     *             format.
     *
     * Set the default format used when type juggling a Carbon instance to a string.
     *
     *
     */
    public static function set_to_string_format(string|Closure|null $format): void
    {
        Factory_Immutable::get_default_instance()->set_to_string_format($format);
    }
}