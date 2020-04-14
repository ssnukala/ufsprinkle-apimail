<?php

/*
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2019 Alexander Weissman
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\ApiMail\ServicesProvider;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Container\ContainerInterface;
use UserFrosting\Sprinkle\ApiMail\Controller\Gmail\Gmailer;
use UserFrosting\Sprinkle\Core\Facades\Debug;

/**
 * UserFrosting APIMail services provider.
 *
 * Registers core services for Google OAuth Mail for UserFrosting.
 *
 * @author Srinivas Nukala (https://srinivasnukala.com)
 */
class ServicesProvider
{
    /**
     * Register UserFrosting's core services.
     *
     * @param ContainerInterface $container A DI container implementing ArrayAccess and container-interop.
     */
    public function register(ContainerInterface $container)
    {

        /*
         * Mail service.
         *
         * @return \UserFrosting\Sprinkle\Core\Mail\Mailer
         */
        $container['gmailer'] = function ($c) {
            $mailer = new Gmailer($c->mailLogger, $c->config['gmail']);
            //Debug::debug("Line 44 ServiceProvider config gmail is ", $c->config['gmail']);
            // Use UF debug settings to override any service-specific log settings.
            if (!$c->config['debug.smtp']) {
                $mailer->getPhpMailer()->SMTPDebug = 0;
            }

            return $mailer;
        };
    }
}
