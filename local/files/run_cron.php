<?php
    ob_start();
    if($_REQUEST['KEY'] == 'rgeg45ythrhrth')
    {
        session_write_close();
        //define('CLI', true);
        if(file_exists(__DIR__ . '/local/php_interface/cron_events.php'))
        {
            require __DIR__ . '/local/php_interface/cron_events.php';
        }
        else if(file_exists(__DIR__ . '/bitrix/php_interface/cron_events.php'))
        {
            require __DIR__ . '/bitrix/php_interface/cron_events.php';
        }
        else if(file_exists(__DIR__ . '/bitrix/modules/main/tools/cron_events.php'))
        {
            require __DIR__ . '/bitrix/modules/main/tools/cron_events.php';
        }
    }
    ob_end_clean();
