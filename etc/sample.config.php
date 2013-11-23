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
    ),
    // Maybe you want to override those but if you are not
    // a developer please don't
    'services' => array(
        'auth' => '\Mailer\Security\Auth\ImapAuthProvider',
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
            'username' => 'someuser',     // This makes the webmail mono user
            'password' => 'somepassword', // This makes the webmail mono user
            'secure' => true,
            'secure_invalid' => false,
        ),
        'imap' => array(
            'host' => 'imap.example.com',
            'username' => 'someuser',     // This makes the webmail mono user
            'password' => 'somepassword', // This makes the webmail mono user
            'secure' => true,
            'secure_invalid' => false,
        ),
    ),
);