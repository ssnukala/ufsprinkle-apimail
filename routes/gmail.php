<?php

/**
 * Helper - Cart and Checkout 
 *
 * @link      https://github.com/ssnukala/ufsprinkle-orders
 * @copyright Copyright (c) 2013-2016 Srinivas Nukala
 */

$app->group('/api/gmail/{action}', function () {
    $rController = 'UserFrosting\Sprinkle\ApiMail\Controller\Gmail\GmailController';
    $this->get('', $rController . ':sendMessage');

    $this->post('', $rController . ':create');
    $this->post('/c/{so_id}', $rController . ':update');
})->add('authGuard');

$app->group('/apimail/gmail/token', function () {
    $rController = 'UserFrosting\Sprinkle\ApiMail\Controller\Gmail\cred\GmailToken';
    $this->get('', $rController . ':getOauthToken');
})->add('authGuard');
