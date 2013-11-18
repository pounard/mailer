<?php
return array(
    'config' => array(
        'debug' => false,
        'html' => array(
            'title' => "My webmail",
        ),
    ),
    'services' => array(
        'auth' => '\Mailer\Security\Auth\ImapAuthProvider',
        'session' => '\Mailer\Core\Session',
    ),
    'redis' => array(
        'host' => 'localhost',
        'port' => null,
    ),
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