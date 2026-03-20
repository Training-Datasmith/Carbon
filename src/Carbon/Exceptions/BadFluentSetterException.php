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

use BadMethodCallException as BaseBadMethodCallException;
use Throwable;
class Bad_Fluent_Setter_Exception extends Base_Bad_Method_Call_Exception implements BadMethodCallException
{
    /**
     * Constructor.
     *
     * @param string         $setter
     * @param int            $code
     */
    public function __construct(
        /**
         * The setter.
         */
        protected $setter,
        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(\sprintf("Unknown fluent setter '%s'", $this->setter), $code, $previous);
    }
    /**
     * Get the setter.
     */
    public function get_setter(): string
    {
        return $this->setter;
    }
}