<?php

const ALT_DB_CONNECTIONS = [
    'example' => [
        'className' => \Bitrix\Main\DB\MysqlConnection::class,
        'login' => 'LOGIN',
        'password' => 'PASS',
        'database'   => 'DB',
        'host'     => '127.0.0.1',
    ],
    'sqlite' => [
        'className' => '*SQLITE*',
        'login' => '',
        'password' => '',
        'database'   => __DIR__ . '/../../db/sqlite.db',
        'host'     => '',
    ],
];

const GSV_MAILBOX = [
    'someone@ya.ru' => [
        'SMTP_HOST' => 'smtp.yandex.ru',
        'SMTP_PORT' => 587,
        'LOGIN' => '',
        'PASSWORD' => '1234',
        'SECURE' => 'tls', //'ssl'/'',
        'STARTTLS' => 'Y', //'N'/'A',
    ],
    'mailtrap@demomailtrap.com' => [
        'SMTP_HOST' => 'live.smtp.mailtrap.io',
        'SMTP_PORT' => 587,
        'LOGIN' => 'api',
        'PASSWORD' => '',
        'SECURE' => 'tls', //'ssl'/'',
        'STARTTLS' => 'Y', //'N'/'A',
    ],
];