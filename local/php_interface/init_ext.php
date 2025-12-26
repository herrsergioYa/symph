<?php

if(file_exists(__DIR__ . '/include/bootstrap.php'))
{
    require_once __DIR__ . '/include/bootstrap.php';
}

if(file_exists(__DIR__ . '/polyfills/bootstrap.php'))
{
    require_once __DIR__ . '/polyfills/bootstrap.php';
}

if(file_exists(__DIR__ . '/ext/bootstrap.php'))
{
    require_once __DIR__ . '/ext/bootstrap.php';
}

if (file_exists(__DIR__ . '/mail/custom_mail.php'))
{
    require __DIR__ . '/mail/custom_mail.php';
}

if (file_exists(__DIR__ . '/../mustache/boostrap.php'))
{
    require __DIR__ . '/../mustache/boostrap.php';
}

if (file_exists(__DIR__ . '/../twig/boostrap.php'))
{
    require __DIR__ . '/../twig/boostrap.php';
}

if (file_exists(__DIR__ . '/../handlebars/boostrap.php'))
{
    require __DIR__ . '/../handlebars/boostrap.php';
}

if (file_exists(__DIR__ . '/../tmpl/boostrap.php'))
{
    require __DIR__ . '/../tmpl/boostrap.php';
}

