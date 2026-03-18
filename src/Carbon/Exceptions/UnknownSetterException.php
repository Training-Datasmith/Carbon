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

class UnknownSetterException extends BaseInvalidArgumentException implements BadMethodCallException
{
    /**
     * Constructor.
     *
     * @param string         $setter   setter name
     * @param int            $code
     */
    public function __construct(/**
     * The setter.
     */
        protected $setter,
        $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct("Unknown setter '{$this->setter}'", $code, $previous);
    }

    /**
     * Get the setter.
     */
    public function getSetter(): string
    {
        return $this->setter;
    }
}
