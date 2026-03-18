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

namespace Carbon\Exceptions;

use InvalidArgumentException as BaseInvalidArgumentException;
use Throwable;

class ParseErrorException extends BaseInvalidArgumentException implements InvalidArgumentException
{
    /**
     * Constructor.
     *
     * @param string         $expected
     * @param string         $actual
     * @param int            $code
     * @param string $help
     */
    public function __construct(/**
     * The expected.
     */
    protected $expected, /**
     * The actual.
     */
    protected $actual, /**
     * The help message.
     */
    protected $help = '', $code = 0, ?Throwable $previous = null)
    {
        $this->actual = $this->actual === '' ? 'data is missing' : "get '{$this->actual}'";

        parent::__construct(trim("Format expected {$this->expected} but {$this->actual}\n{$this->help}"), $code, $previous);
    }

    /**
     * Get the expected.
     */
    public function getExpected(): string
    {
        return $this->expected;
    }

    /**
     * Get the actual.
     */
    public function getActual(): string
    {
        return $this->actual;
    }

    /**
     * Get the help message.
     */
    public function getHelp(): string
    {
        return $this->help;
    }
}
