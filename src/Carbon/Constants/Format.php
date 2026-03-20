<?php

declare(strict_types=1);

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Carbon\Constants;

/**
 * Date/time format string constants used throughout the Carbon library.
 *
 * This interface can be implemented by Carbon subclasses to inherit the
 * constants, or accessed statically as `Format::RFC7231_FORMAT`.
 *
 * All constants use PHP's `date()` format characters. To use a constant with
 * `Carbon::format()` or `date()`:
 *
 * ```php
 * echo Carbon::now()->format(Format::RFC7231_FORMAT);
 * // → e.g. "Thu, 20 Mar 2025 14:30:00 GMT"
 * ```
 *
 * @since 2.0
 * @see   https://www.php.net/manual/en/datetime.format.php PHP date format characters
 */
interface Format
{
    /**
     * RFC 7231 HTTP-date format, as required by HTTP/1.1 headers.
     *
     * Output example: `Thu, 20 Mar 2025 14:30:00 GMT`
     *
     * Use for: `Date`, `Expires`, `Last-Modified`, `If-Modified-Since` HTTP headers.
     *
     * @since 2.0
     * @see   https://datatracker.ietf.org/doc/html/rfc7231#section-7.1.1.1
     */
    public const RFC7231_FORMAT = 'D, d M Y H:i:s \G\M\T';

    /**
     * Default format used when a Carbon instance is cast to string.
     *
     * Applied by `__toString()` when Carbon participates in string concatenation
     * or is passed to functions expecting a string.
     *
     * Output example: `2025-03-20 14:30:00`
     *
     * @since 2.0
     */
    public const DEFAULT_TO_STRING_FORMAT = 'Y-m-d H:i:s';

    /**
     * Format used internally when serialising a mocked "test now" value.
     *
     * Includes microseconds (the `u` character) so that sub-second precision
     * is preserved when freezing time for tests via `Carbon::setTestNow()`.
     *
     * Output example: `2025-03-20 14:30:00.123456`
     *
     * @since 2.0
     * @see   \Carbon\Carbon::setTestNow()
     */
    public const MOCK_DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * Regular expression pattern used by `->isoFormat()` and `::createFromIsoFormat()`.
     *
     * Matches Moment.js-compatible format tokens (e.g. `YYYY`, `MM`, `ddd`).
     * This pattern is used to tokenise the format string before substituting
     * actual date/time values.
     *
     * @since 2.0
     * @see   https://momentjs.com/docs/#/displaying/format/ Moment.js format tokens
     * @complexity O(n) in the length of the format string when used in preg_replace_callback
     */
    public const ISO_FORMAT_REGEXP = '(O[YMDHhms]|[Hh]mm(ss)?|Mo|MM?M?M?|Do|DDDo|DD?D?D?|ddd?d?|do?|w[o|w]?|W[o|W]?|Qo?|YYYYYY|YYYYY|YYYY|YY?|g{1,5}|G{1,5}|e|E|a|A|hh?|HH?|kk?|mm?|ss?|S{1,9}|x|X|zz?|ZZ?)';
}
