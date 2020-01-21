<?php

namespace UserFrosting\Sprinkle\ApiMail\Controller\Gmail;

use UserFrosting\Sprinkle\Core\Controller\SimpleController;
use UserFrosting\Sprinkle\Core\Facades\Debug;
use Google_Service_Gmail_Message;
use Google_Service_Gmail_Draft;
use Google_Service_Gmail;
use Google_Client;
use UserFrosting\Sprinkle\Core\Mail\MailMessage;
use UserFrosting\Sprinkle\Core\Mail\Mailer;
use Swift_Message;
//use PHPMailer\PHPMailer\PHPMailer;

/**
 * GmailController
 *
 * @package UserFrosting-RegSevak
 * @author Srinivas Nukala
 * @link http://srinivasnukala.com
 */

class GmailController extends SimpleController
{

    function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes(Google_Service_Gmail::MAIL_GOOGLE_COM); //GMAIL_READONLY);
        $client->setAuthConfig(__DIR__ . '/cred/credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = __DIR__ . '/cred/token-compose.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new \Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    public function getMailName($name, $email)
    {
        return $name . "<$email>";
    }

    public function createMessage($name, $email, $subject, $body)
    {

        $strRawMessage = "From: fromname <fromemail>\r\n";
        $strRawMessage .= "To: $name <$email>\r\n";
        $strRawMessage .= 'Subject: =?utf-8?B?' . base64_encode($subject) . "?=\r\n";
        $strRawMessage .= "MIME-Version: 1.0\r\n";
        $strRawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
        $strRawMessage .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n";
        $strRawMessage .= "$body\r\n";


        $message = new Google_Service_Gmail_Message();
        $encmesg = strtr(base64_encode($strRawMessage), array('+' => '-', '/' => '_'));
        $message->setRaw($encmesg);
        return $message;
    }


    public function sendGmailMessage($service, $userId, $message)
    {
        try {
            $message = $service->users_messages->send($userId, $message);
            print 'Message with ID: ' . $message->getId() . ' sent.';
            return $message;
        } catch (\Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }
    }

    public function createGmailDraft($service, $user, $message)
    {
        /*creates and inserts a draft email.
        Arguments:
        service: an authorized Gmail API service instance.
        user: User's email address
        message: The body of the email message with headers.
        */
        $draft = new Google_Service_Gmail_Draft();
        $draft->setMessage($message);
        $draftid = -1;
        try {
            $draftmsg = $service->users_drafts->create($user, $draft);
            $draftid = $draftmsg->getId();
            print 'Draft ID: ' . $draftmsg->getId();
        } catch (\Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }
        return $draftid;
    }

    public function sendDraft($service, $userId, $draftId)
    {
        $draft = new Google_Service_Gmail_Draft();
        $draft->setId($draftId);
        // To update the Draft before sending, set a new Message on the Draft before sending.

        try {
            $draftmsg = $service->users_drafts->send($userId, $draft);
            print 'Draft ID: ' . $draftmsg->getId();
        } catch (\Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }
    }


    public function sendMessageTest($request, $response, $args)
    {
        $client = $this->getClient();
        $service = new Google_Service_Gmail($client);
        // Print the labels in the user's account.
        $user = 'me';
        //$results = $service->users_labels->listUsersLabels($user);
        $mesg = "Srinivas testing Chinmaya Mission Message " . date('YMD H:i:s');
        $message = $this->createMessage('Srinivas', 'ssgnukala@gmail.com', 'RegSevak Test', $mesg);
        //$draftid = $this->createGmailDraft($service, $user, $message);
        //$this->sendDraft($service, $user, $draftid);

        $this->sendGmailMessage($service, $user, $message);
    }

    public function createMailSwift($subject, $to, $body, $cc = '')
    {
        $swiftMessage = new Swift_Message($subject);
        $swiftMessage->setTo([$to]);
        if ($cc != '') {
            $swiftMessage->setCc([$cc]);
        }
        $swiftMessage->setBody($body);
        $validMessageBody = $swiftMessage->toString();
    }

    public function sendUfMail(MailMessage $message)
    {
        $client = $this->getClient();
        $service = new Google_Service_Gmail($client);
        // Print the labels in the user's account.
        $user = 'me';
        // Add all email recipients, as well as their CCs and BCCs
        $mailer = $this->ci->mailer;
        $mailcontent  = $mailer->getMailHeader($message);
        Debug::debug("Line 182 the mime is " . $mailcontent);
        $thismesg = new Google_Service_Gmail_Message();
        $encmesg = strtr(base64_encode($mailcontent), array('+' => '-', '/' => '_'));
        $thismesg->setRaw($encmesg);
        $this->sendGmailMessage($service, $user, $thismesg);
    }
}
