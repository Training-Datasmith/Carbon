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
namespace Carbon\Exceptions;

use Throwable;
class Unknown_Unit_Exception extends Unit_Exception
{
    /**
     * Constructor.
     *
     * @param string         $unit
     * @param int            $code
     */
    public function __construct(
        /**
         * The unit.
         */
        protected $unit,
        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct("Unknown unit '{$this->unit}'.", $code, $previous);
    }
    /**
     * Get the unit.
     */
    public function get_unit(): string
    {
        return $this->unit;
    }
}