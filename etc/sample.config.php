<?php
return array(
    // Main configuration
    'config' => array(
        'debug' => false,
        'html' => array(
            // Your site title
            'title' => "My webmail",
        ),
        'charset' => "UTF-8",
        // Default folders config
        // @todo Make this configurable per user
        'folders' => array(
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
        'session' => '\Mailer\Core\Session',
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