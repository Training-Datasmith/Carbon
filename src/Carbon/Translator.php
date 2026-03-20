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
namespace Carbon;

use ReflectionMethod;
use Symfony\Component\Translation;
use Symfony\Contracts\Translation\Translator_Interface;
$trans_method = new ReflectionMethod(class_exists(Translator_Interface::class) ? Translator_Interface::class : Translation\Translator::class, 'trans');
require $trans_method->has_return_type() ? __DIR__ . '/../../lazy/Carbon/TranslatorStrongType.php' : __DIR__ . '/../../lazy/Carbon/TranslatorWeakType.php';
class Translator extends Lazy_Translator
{
    // Proxy dynamically loaded LazyTranslator in a static way
}