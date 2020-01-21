<?php

/*
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2019 Alexander Weissman
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\ApiMail\Controller\Gmail;

/**
 * This example shows how to send via Google's Gmail servers using XOAUTH2 authentication.
 */

//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\OAuth;
use UserFrosting\Sprinkle\Core\Mail\MailMessage;
use UserFrosting\Sprinkle\Core\Facades\Debug;

// Alias the League Google OAuth2 provider class
use League\OAuth2\Client\Provider\Google;

/**
 * Mailer Class.
 *
 * A basic wrapper for sending template-based emails.
 *
 * @author Alex Weissman (https://alexanderweissman.com)
 */
class Gmailer
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \PHPMailer
     */
    protected $phpMailer;

    protected $config;
    /**
     * Create a new Mailer instance.
     *
     * @param Logger  $logger A Monolog logger, used to dump debugging info for SMTP server transactions.
     * @param mixed[] $config An array of configuration parameters for phpMailer.
     *
     * @throws phpmailerException Wrong mailer config value given.
     */
    public function __construct($logger, $config = [])
    {
        $this->logger = $logger;

        $this->config = $config;

        Debug::debug("Line 57 the oauth Config params are ", $config);

        // 'true' tells PHPMailer to use exceptions instead of error codes
        $this->phpMailer = new PHPMailer(true);

        // Configuration options
        $this->phpMailer->isSMTP(true);
        $this->phpMailer->Host = 'smtp.gmail.com';
        $this->phpMailer->Port = 587; //$config['port'];
        $this->phpMailer->SMTPAuth = true; //$config['auth'];
        $this->phpMailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; //$config['secure']

        $this->phpMailer->Username = $config['username'];
        //$this->phpMailer->Password = $config['password'];
        $this->phpMailer->SMTPDebug = $config['smtp_debug'];
        $this->phpMailer->AuthType = 'XOAUTH2';

        //Fill in authentication details here
        //Either the gmail account owner, or the user that gave consent
        $email = $config['username'];
        $clientId = $config['client_id'];
        $clientSecret = $config['client_secret'];

        //Obtained by configuring and running get_oauth_token.php
        //after setting up an app in Google Developer Console.
        $refreshToken = $config['refresh_token'];

        //Create a new OAuth2 provider instance
        $provider = new Google(
            [
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ]
        );
        $gmail_param = [
            'provider' => $provider,
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'refreshToken' => $refreshToken,
            'userName' => $email,
        ];
        Debug::debug("Line 97 the oauth params are ", $gmail_param);
        //Pass the OAuth provider instance to PHPMailer
        $this->phpMailer->setOAuth(new OAuth($gmail_param));

        if (isset($config['smtp_options'])) {
            $this->phpMailer->SMTPOptions = $config['smtp_options'];
        }
        // Set any additional message-specific options
        // TODO: enforce which options can be set through this subarray
        if (isset($config['message_options'])) {
            $this->setOptions($config['message_options']);
        }

        // Pass logger into phpMailer object
        $this->phpMailer->Debugoutput = function ($message, $level) {
            $this->logger->debug($message);
        };
    }

    /**
     * Set option(s) on the underlying phpMailer object.
     *
     * @param mixed[] $options
     *
     * @return Mailer
     */
    public function setOptions($options)
    {
        if (isset($options['isHtml'])) {
            $this->phpMailer->isHTML($options['isHtml']);
        }

        foreach ($options as $name => $value) {
            $this->phpMailer->set($name, $value);
        }

        return $this;
    }

    /**
     * Get the underlying PHPMailer object.
     *
     * @return PHPMailer
     */
    public function getPhpMailer()
    {
        return $this->phpMailer;
    }

    /**
     * Create a MailMessage message.
     *
     * Creates the phpMailer object ready to be sent.
     * this can be used by subsequent functions to send or capture the MIME contents etc.
     *
     * @param MailMessage $message
     *
     */
    public function createMessage(MailMessage $message)
    {
        $this->phpMailer->From = $message->getFromEmail();
        $this->phpMailer->FromName = $message->getFromName();
        $this->phpMailer->addReplyTo($message->getReplyEmail(), $message->getReplyName());

        // Add all email recipients, as well as their CCs and BCCs
        foreach ($message->getRecipients() as $recipient) {
            $this->phpMailer->addAddress($recipient->getEmail(), $recipient->getName());

            // Add any CCs and BCCs
            if ($recipient->getCCs()) {
                foreach ($recipient->getCCs() as $cc) {
                    $this->phpMailer->addCC($cc['email'], $cc['name']);
                }
            }

            if ($recipient->getBCCs()) {
                foreach ($recipient->getBCCs() as $bcc) {
                    $this->phpMailer->addBCC($bcc['email'], $bcc['name']);
                }
            }
        }

        $this->phpMailer->Subject = $message->renderSubject();
        $this->phpMailer->Body = $message->renderBody();

        //return $this->phpMailer;
    }

    /**
     * Send a MailMessage message.
     *
     * Sends a single email to all recipients, as well as their CCs and BCCs.
     * Since it is a single-header message, recipient-specific template data will not be included.
     *
     * @param MailMessage $message
     * @param bool        $clearRecipients Set to true to clear the list of recipients in the message after calling send().  This helps avoid accidentally sending a message multiple times.
     *
     * @throws phpmailerException The message could not be sent.
     */
    public function send(MailMessage $message, $clearRecipients = true)
    {
        $this->createMessage($message);
        // Try to send the mail.  Will throw an exception on failure.
        $this->phpMailer->send();

        // Clear recipients from the PHPMailer object for this iteration,
        // so that we can use the same object for other emails.
        $this->phpMailer->clearAllRecipients();

        // Clear out the MailMessage's internal recipient list
        if ($clearRecipients) {
            $message->clearRecipients();
        }
    }

    public function getOauthToken()
    {
        $clientId = $this->config['client_id'];
        $clientSecret = $this->config['client_secret'];

        //If this automatic URL doesn't work, set it yourself manually to the URL of this script
        $redirectUri = 'http://localhost:8695/aipmail/gmail/token';

        $params = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
            'accessType' => 'offline'
        ];

        $options = [];
        $provider = null;

        $provider = new Google($params);
        $options = [
            'scope' => [
                'https://mail.google.com/'
            ]
        ];

        if (null === $provider) {
            exit('Provider missing');
        }

        if (!isset($_GET['code'])) {
            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl($options);
            $_SESSION['oauth2state'] = $provider->getState();
            header('Location: ' . $authUrl);
            exit;
            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            unset($_SESSION['provider']);
            exit('Invalid state');
        } else {
            unset($_SESSION['provider']);
            // Try to get an access token (using the authorization code grant)
            $token = $provider->getAccessToken(
                'authorization_code',
                [
                    'code' => $_GET['code']
                ]
            );
            // Use this to interact with an API on the users behalf
            // Use this to get a new access token if the old one expires
            echo 'Refresh Token: ', $token->getRefreshToken();
        }
    }
}
