<?php

namespace WideBundle\Authentication;

use Monolog\Logger;
use VBee\SettingBundle\Manager\SettingDoctrineManager;

/**
 * Class WebmailAuthenticator
 *
 * Authenticates users based on their CEID webmail credentials.
 * @package WideBundle\Authentication
 */
class WebmailAuthenticator
{
    /** Either the hostname or the IP address of the IMAP mail server */
    private $imapServerHost;

    /** The port that should be used in the IMAP open request */
    private $imapServerPort;

    /** @var Logger $logger */
    private $logger;

    /**
     * WebmailAuthenticator constructor.
     * Using the mailserver's IP address instead of the hostname will speed up the IMAP functions.
     * If no port is specified, the default SSL/TLS encrypted IMAP port (993) will be used.
     * 
     * @param SettingDoctrineManager $settingsManager
     * @param mixed $logger
     */
    public function __construct(SettingDoctrineManager $settingsManager, Logger $logger)
    {
        $this->imapServerHost = $settingsManager->get('imap_server_host');
        $this->imapServerPort = $settingsManager->get('imap_server_port', 993);
        $this->logger = $logger;
    }

    /**
     * Validates the provided email/password combination against the corresponding
     * mailbox on the provided email server.
     *
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function validateCredentials($email, $password)
    {
        $mailboxServer = '{' . $this->imapServerHost . ':' . $this->imapServerPort . '/imap/ssl}INBOX';

        $mailbox = $this->safeImapOpen($mailboxServer, $email, $password);

        if ($mailbox !== false) {
            // If the mailbox is valid, the user's credentials were correct, so just close the mailbox and return.
            $this->safeImapClose($mailbox, $email);
            return true;
        }

        return false;
    }

    /**
     * Opens a mailbox with imap_open taking care of any errors/exceptions.
     * @param $server
     * @param $email
     * @param $password
     * @return bool|resource
     */
    private function safeImapOpen($server, $email, $password)
    {
        try {
            $returnValue = imap_open($server, $email, $password, OP_READONLY);
            $lastError = imap_last_error();
            if ($lastError !== false) {
                $this->logger->addNotice("User $email login failure - " . $lastError);
            }
        } catch (\Exception $exception) {
            $this->logger->addError("User $email login failure - " . $exception->getMessage());
            $returnValue = false;
        }
        return $returnValue;
    }

    /**
     * Closes the provided mailbox. Logs any errors that may occur.
     * @param $mailbox
     * @param $email
     * @return bool
     */
    private function safeImapClose($mailbox, $email)
    {
        try {
            imap_close($mailbox);
            $lastError = imap_last_error();
            if ($lastError !== false) {
                $this->logger->addError("Failed to close mailbox for $email - " . $lastError);
            }
        } catch (\Exception $exception) {
            $this->logger->addError("Failed to close mailbox for $email - " . $exception->getMessage());
        }

        // The $mailbox resource will be released anyway after the script is done, so just return true
        return true;
    }
}