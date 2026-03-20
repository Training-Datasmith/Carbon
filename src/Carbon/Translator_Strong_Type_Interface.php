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

use Symfony\Component\Translation\Message_Catalogue_Interface;
/**
 * Mark translator using strong type from symfony/translation >= 6.
 */
interface Translator_Strong_Type_Interface
{
    public function get_from_catalogue(Message_Catalogue_Interface $catalogue, string $id, string $domain = 'messages');
}