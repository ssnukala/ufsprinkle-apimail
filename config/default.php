<?php

/*
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2019 Alexander Weissman
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */

/*
 * Core configuration file for UserFrosting.  You must override/extend this in your site's configuration file.
 *
 * Sensitive credentials should be stored in an environment variable or your .env file.
 * Database password: DB_PASSWORD
 * SMTP server password: SMTP_PASSWORD
 */
return [
    /*
    * ----------------------------------------------------------------------
    * Mail Service Config
    * ----------------------------------------------------------------------
    * See https://learn.userfrosting.com/mail/the-mailer-service
    */
    'mail'    => [
        'mailer'          => 'smtp', // Set to one of 'smtp', 'mail', 'qmail', 'sendmail'
        'host'            => getenv('SMTP_HOST') ?: null,
        'port'            => 587,
        'auth'            => true,
        'secure'          => 'tls', // Enable TLS encryption. Set to `tls`, `ssl` or `false` (to disabled)
        'username'        => getenv('SMTP_USER') ?: null,
        //'password'        => getenv('SMTP_PASSWORD') ?: null,
        'client_id' => getenv('SMTP_G_CLIENT_ID'),
        'client_secret' => getenv('SMTP_G_CLIENT_SECRET'),
        'refresh_token' => getenv('SMTP_G_REFRESH_TOKEN'),
        'smtp_debug'      => 4,
        'message_options' => [
            'CharSet'   => 'UTF-8',
            'isHtml'    => true,
            'Timeout'   => 15,
        ],
    ],

];
