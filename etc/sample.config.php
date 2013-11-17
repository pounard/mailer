<?php
return array(
    'config' => array(
        'debug' => false,
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