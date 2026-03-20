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

use Carbon\Carbon_Interface;
use InvalidArgumentException as BaseInvalidArgumentException;
use Throwable;
class Not_A_Carbon_Class_Exception extends Base_Invalid_Argument_Exception implements InvalidArgumentException
{
    /**
     * Constructor.
     *
     * @param string         $className
     * @param int            $code
     */
    public function __construct(
        /**
         * The className.
         */
        protected $class_name,
        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(\sprintf('Given class does not implement %s: %s', Carbon_Interface::class, $this->class_name), $code, $previous);
    }
    /**
     * Get the className.
     */
    public function get_class_name(): string
    {
        return $this->class_name;
    }
}