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
namespace Carbon\Message_Formatter;

use ReflectionMethod;
use Symfony\Component\Translation\Formatter\Message_Formatter;
use Symfony\Component\Translation\Formatter\Message_Formatter_Interface;
// @codeCoverageIgnoreStart
$trans_method = new ReflectionMethod(Message_Formatter_Interface::class, 'format');
require $trans_method->get_parameters()[0]->has_type() ? __DIR__ . '/../../../lazy/Carbon/MessageFormatter/MessageFormatterMapperStrongType.php' : __DIR__ . '/../../../lazy/Carbon/MessageFormatter/MessageFormatterMapperWeakType.php';
// @codeCoverageIgnoreEnd
final class Message_Formatter_Mapper extends Lazy_Message_Formatter
{
    public function __construct(protected ?Message_Formatter_Interface $formatter = new Message_Formatter())
    {
    }
    protected function transform_locale(?string $locale): ?string
    {
        return $locale ? preg_replace('/[_@][A-Za-z][a-z]{2,}/', '', $locale) : $locale;
    }
}