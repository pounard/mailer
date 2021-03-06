<?php
return array(
    // Main configuration
    'config' => array(
        'debug' => false,
        'html' => array(
            // Your site title
            'title' => "My webmail",
        ),
        // Internal charset this software works from: you should never
        // change this; But hey, do what the heck you want! I'm not your
        // mother...
        'charset' => "UTF-8",
        // Default timezone if there is no user override
        'timezone' => 'Europe/Paris',
        // If this is set to false the Useragent header won't be sent
        // into new mails
        'displayUserAgent' => true,
        // This is your default mail domain from which will give default
        // mail address for your users which did not configure their own
        // address
        'domain' => 'mydomain.tld',
        // Default folders config
        // @todo Make this configurable per user
        'mailboxes' => array(
            'draft' => 'Drafts',
            'sent' => 'Sent',
            'trash' => 'Trash',
            'spam' => 'spam',
        ),
        // Output filtering configuration, you should not modify
        // this in most cases, defaults are fine for basic usage
        'filters' => array(
            'html' => array('strip', 'lntohr', 'autop', 'urltoa'),
            'html2sum' => array('strip', 'lntovd', 'urltoa'),
            'plain' => array('htmlesc', 'lntohr', 'autop', 'urltoa'),
            'plain2sum' => array('htmlesc', 'lntovd', 'urltoa'),
            'secure' => array('strip'),
        ),
    ),
    // Maybe you want to override those but if you are not
    // a developer please don't
    'services' => array(
        'auth' => '\Mailer\Security\Auth\ImapAuthProvider',
        'filterfactory' => '\Mailer\View\Helper\FilterFactory',
        /*'mailreader' => '\Mailer\Server\Imap\Impl\RcubeImapMailReader',
        'mailsender' => '\Mailer\Server\Smtp\Impl\PhpMailerMailSender',*/
        'messager' => '\Mailer\Core\Messager',
        'session' => '\Mailer\Core\Session',
        'templatefactory' => '\Mailer\View\Helper\TemplateFactory',
    ),
    // Just remove the 'redis' part to disable caching
    // Note: this is a very bad idea
    'redis' => array(
        'host' => 'localhost',
        'port' => null,
    ),
    // In this section configure the servers you want to use
    'servers' => array(
        'smtp' => array(
            'host' => 'smtp.example.com',
            'secure' => true,
            'secure_invalid' => false,
        ),
        'imap' => array(
            'host' => 'imap.example.com',
            'secure' => true,
            'secure_invalid' => false,
        ),
    ),
);