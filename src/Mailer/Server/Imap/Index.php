<?php

namespace Mailer\Server\Imap;

use Mailer\Core\AbstractContainerAware;
use Mailer\Core\Container;
use Mailer\Core\ContainerAwareInterface;
use Mailer\Error\NotImplementedError;
use Mailer\Mime\Part;
use Mailer\Model\Envelope;
use Mailer\Model\Folder;
use Mailer\Model\Mail;
use Mailer\Model\Person;
use Mailer\Server\Smtp\MailSenderInterface;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;

/**
 * IMAP protocol is stupid enough to force us to cache over it:
 * this where it starts
 */
class Index extends AbstractContainerAware
{
    /**
     * @var MailboxIndex[]
     */
    private $mailboxes = array();

    /**
     * @var MailReaderInterface
     */
    private $reader;

    /**
     * @var MailSenderInterface
     */
    private $sender;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $cachePrefix;

    /**
     * Default constructor
     *
     * @param MailReaderInterface $reader
     */
    public function __construct(
        MailReaderInterface $reader,
        MailSenderInterface $sender,
        Cache $cache = null)
    {
        $this->reader = $reader;
        $this->sender = $sender;

        if (null === $cache) {
            $this->cache = new ArrayCache();
        } else {
            $this->cache = $cache;
        }
    }

    /**
     * Get cache key for this cache entry
     *
     * @param ... parameters
     *
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cachePrefix . ':' . implode(':', func_get_args());
    }

    /**
     * Get internal mail reader instance
     *
     * If you are calling this method into a controller then you are doing
     * it wrong: it should never be accessed outside of the index with one
     * exception which is the IMAP auth connector.
     *
     * @return MailReaderInterface
     */
    public function getMailReader()
    {
        return $this->reader;
    }

    /**
     * Get cache
     *
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Fetch single mailbox index
     *
     * @param string $name
     * @param boolean $refresh
     *
     * @return MailboxIndex
     */
    public function getMailboxIndex($name, $refresh = true)
    {
        if (!isset($this->mailboxes[$name])) {
            $this->mailboxes[$name] = new MailboxIndex($this, $name);
        }

        return $this->mailboxes[$name];
    }

    /**
     * Fetch single mailbox
     *
     * @param string $name
     * @param boolean $refresh
     *
     * @return Folder
     */
    public function getMailbox($name, $refresh = true)
    {
        return $this->getMailboxIndex($name)->getInstance();
    }

    /**
     * Fetch complete mailbox map representation
     *
     * @param boolean $onlySubscribed
     * @param boolean $refresh
     *
     * @return Folder[]
     */
    public function getMailboxMap($onlySubscribed = true, $refresh = true)
    {
        $map    = array();
        $key    = $this->getCacheKey('fm', (int)$onlySubscribed);
        $reader = $this->getMailReader();
        $delim  = $reader->getFolderDelimiter();

        if ($refresh || !($list = $this->cache->fetch($key))) {
            $list = $reader->getFolderMap(null, $onlySubscribed);
            $this->cache->save($key, $list);
        }

        // Sorting ensures that direct parents will always be processed
        // before their child, and thus allow us having a fail-safe
        // tree creation algorithm
        sort($list);

        foreach ($list as $name) {
          $map[$name] = $this->getMailbox($name, $refresh);
          // If parent does not exists create a pseudo folder instance that
          // does not belong to IMAP server but will help the client
          // materialize the non existing yet folder
          if ($parent = $map[$name]->getParentKey()) {
            while (!isset($map[$parent])) {
              $map[$parent] = new Folder();
              $map[$parent]->fromArray(array(
                  'path'      => $parent,
                  'parent'    => null, // @todo
                  'delimiter' => $delim,
                  'unseen'    => 0,
                  'recent'    => 0,
                  'total'     => 0,
              ));
              $parent = $map[$parent]->getParentKey();
            }
          }
        }

        return $map;
    }

    /**
     * Generate clean ready to use markup from the given string
     *
     * @param string $string
     *   Input string
     * @param string $type
     *   Filter type ("plain" or "html")
     * @param string $charset
     *   Input charset
     * @param boolean $summary
     *   Set this to true if you need a summary
     * @param int $size
     *   Truncate size if summary
     *
     * @return string
     */
    public function bodyFilter($string, $type = 'plain', $charset = null, $summary = false, $size = 200)
    {
        if ($summary) {
            $type .= '2sum';
        }

        $string = $this
            ->getContainer()
            ->getFilterFactory()
            ->getFilter($type)
            ->filter($string, $charset);

        if ($summary && !empty($string)) {
            if ($size < strlen($string)) {
                if (preg_match('/^.{1,' . $size . '}\b/su', $string, $match)) {
                    return $match[0] . '…';
                }
            }
        }

        return $string;
    }

    /**
     * Flush caches
     */
    public function flush()
    {
        // @todo Doctrine cache cannot flush?
        // The only one on packagist is 1.x and 2.x knows how to flush...
    }

    /**
     * Send mail
     *
     * If no identity is set into the mail (from and organization fields)
     * they will be added from the current logged in account. Note that
     * organization will not be set if from is already set.
     *
     * The mail structure will be modified by adding all missing headers
     * including user identity.
     *
     * @param Mail $mail
     *   Mail to send
     * @param string $name
     *   Where to copy the mail, defaults to configured sent mailbox
     */
    public function sendMail(Mail $mail, $name = null)
    {
        $config  = $this->getContainer()->getConfig();
        $updates = array();

        if (null === $name) {
            $name = $config['mailboxes/sent'];
        }

        if (!$mail->getFrom()) {
            if (!$fromMail = $config['identity/mail']) {
                $fromMail = $this
                    ->getContainer()
                    ->getInternalContainer()
                    ->offsetGet('defaultAddress');
            }
            $account = new Person();
            $account->fromArray(array(
                'mail' => $fromMail,
                'name' => $config['identity/displayName'],
            ));
            $updates['from'] = $account;

            if (!$mail->getOrganization()) {
                if ($organization = $config['identity/organization']) {
                    $updates['organization'] = $organization;
                }
            }
        }

        if (!empty($updates)) {
            $mail->fromArray($updates);
        }

        $mailbox = $this->getMailboxIndex($name);
        $headers = $this->buildMailHeaders($mail);

        if (false === ($resource = tmpfile())) {
            throw new \Exception("Cannot create temporary file");
        }
        $lineEnding = Part::DEFAULT_LINE_ENDING;
        foreach ($headers as $name => $value) {
            fwrite($resource, $name . ": " . $value . $lineEnding);
        }
        $mimeEncoded = $mail->getStructure()->writeEncodedMime($resource);
        rewind($resource);

        // Save before sending to ensure that the user can edit back his
        // mail if something wrong happened
        $this->reader->saveMail($mail, $headers);
        $this->sender->sendMail($mail, $headers);
    }

    /**
     * Generate a new message identifier
     *
     * @return string
     */
    public function generateMessageId()
    {
        $config = $this->getContainer()->getConfig();
        $domain = $config['domain'];
        $local  = md5(uniqid('rcube'.mt_rand(), true));

        // This comment comes from Roundcube. Basically the whole algorithm
        // in this function does: Try to find FQDN some spamfilters doesn't
        // like 'localhost' (#1486924)
        if (!preg_match('/\.[a-z]+$/i', $domain)) {
            // Note from Mailer: this should hopefully never happen, domain
            // should be configured in the config.php file
            foreach (array($_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME']) as $host) {
                $host = preg_replace('/:[0-9]+$/', '', $host);
                if ($host && preg_match('/\.[a-z]+$/i', $host)) {
                    $domain = $host;
                }
            }
        }

        return sprintf('<%s@%s>', $local, $domain);
    }

    /**
     * Build mail headers from what's inside and add missing considering
     * we are supposdly sending or editing this mail ourselves
     *
     * @todo Encore headers with special chars
     *
     * @param Envelope $mail
     *
     * @return string[]
     */
    public function buildMailHeaders(Envelope $mail)
    {
        $container = $this->getContainer();
        $config    = $container->getConfig();
        $headers   = array();
        $updates   = array();

        if (!$messageId = $mail->getMessageId()) { // Mail could already exist (Draft)
            $messageId = $this->generateMessageId();
            $updates['messageId'] = $messageId;
        }
        if (!$charset = $mail->getCharset()) {
            $charset = $container->getDefaultCharset();
            $updates['charset'] = $charset;
        }

        // if configured, the Received headers goes to top, for good measure
        // @todo Received header

        try {
            $date = new \DateTime(null, new \DateTimeZone($config['timezone']));
        } catch (\Exception $e) {
            $date = new \DateTime();
        }

        $headers['Date'] = $date->format('r');
        $headers['From'] = $mail->getFrom()->getCompleteMail();

        if ($to = $mail->getTo()) { // Mail addresses
            $headers['To'] = implode(", ", $mail->getTo());
        } else {
            $headers['To'] = "undisclosed-recipients:;";
        }
        if ($cc = $mail->getCc()) { // Mail addresses
            $headers['Cc'] = implode(", ", $cc);
        }
        if ($bcc = $mail->getBcc()) { // Mail addresses
            $headers['Bcc'] = implode(", ", $bcc);
        }

        $headers['Subject'] = trim($mail->getSubject());

        if ($organization = $mail->getOrganization()) {
          $headers['Organization'] = $organization;
        }
        if ($replyTo = $mail->getReplyTo()) { // Mail addresses
          $headers['Reply-To'] = $replyTo;
        }
        if (!empty($headers['Reply-To'])) { // Mail addresses
          $headers['Mail-Reply-To'] = $headers['Reply-To'];
        }
        /*
        if (!empty($_POST['_followupto'])) { // Mail addresses
          $headers['Mail-Followup-To'] = rcmail_email_input_format(get_input_value('_followupto', RCUBE_INPUT_POST, TRUE, $message_charset));
        }
         */

        /*
        // A dawn good idea from Rouncube:
        // remember reply/forward UIDs in special headers
        if (!empty($COMPOSE['reply_uid']) && $savedraft) {
            $headers['X-Draft-Info'] = array('type' => 'reply', 'uid' => $COMPOSE['reply_uid']);
        } else if (!empty($COMPOSE['forward_uid']) && $savedraft) {
          $headers['X-Draft-Info'] = array('type' => 'forward', 'uid' => $COMPOSE['forward_uid']);
        }
        if (is_array($headers['X-Draft-Info'])) {
          $headers['X-Draft-Info'] = rcmail_draftinfo_encode($headers['X-Draft-Info'] + array('folder' => $COMPOSE['mailbox']));
        }
         */

        if ($inReplyTo = $mail->getInReplyto()) {
            $headers['In-Reply-To'] = $inReplyTo;
        }
        if ($references = $mail->isReferenceTo()) {
            $headers['References'] = $references;
        }
        if ($priority = $mail->getPriorityHeaderString()) {
            $headers['X-Priority'] = $priority;
        }

        /*
        if (!empty($_POST['_receipt'])) {
          $headers['Return-Receipt-To'] = $from_string;
          $headers['Disposition-Notification-To'] = $from_string;
        }
         */

        $headers['Message-ID'] = $messageId;
        $headers['X-Sender']   = $mail->getFrom()->getCompleteMail();

        if ($config['displayUserAgent']) {
            $headers['User-Agent'] = $config['useragent'];
        }

        if (!empty($updates)) {
            $mail->fromArray($updates);
        }

        return $headers;
    }

    public function setContainer(Container $container)
    {
        parent::setContainer($container);

        $account = $container->getSession()->getAccount();

        $this->cachePrefix = sprintf("i:%s:", $account->getId());

        if ($this->reader instanceof ContainerAwareInterface) {
            $this->reader->setContainer($container);
        }
        if ($this->sender instanceof ContainerAwareInterface) {
            $this->sender->setContainer($container);
        }

        $this->reader->setCredentials(
            $account->getUsername(),
            $account->getPassword()
        );
        $this->sender->setCredentials(
            $account->getUsername(),
            $account->getPassword()
        );
    }
}
