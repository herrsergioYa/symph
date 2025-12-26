<?php

/**
 * Список директорий, просматриваемых local_classes_autoload()
 */
const LOCAL_CLASSES_DIRECTORIES = [
    __DIR__.'/../../classes/',
    __DIR__.'/classes/',
    __DIR__.'/../classes/',
];

/**
 * Список директорий, просматриваемых local_lib_autoload()
 */
const LOCAL_LIB_DIRECTORIES = [
    'gsv.local' => __DIR__.'/../../lib/',
];


/**
 * Расположение Micro ORMs
 */
const REDBEANPHP_LIB_FILE = __DIR__.'/../../micro_orm/rb.php';
const MEDOO_LIB_FILE = __DIR__.'/../../micro_orm/Medoo.php';

define('XDEBUG_IDE_KEY_4_GSV_DUMP', 'PHPSTORMSERGEY');
const DEBUG_USER_LOGIN = [
    'sgaevoy',
    's.gaevoy', 's.gaevoy@seocomplex.ru',
    'serge.gaevoy' , 'serge.gaevoy@gmail.com',
    'sergey.gaevoy',
];
const DEBUG_USER_ID = [];
const DEBUG_GROUP_USER_ID = [];

/**
 * Возможные местоположения Composer'а
 */
const COMPOSER_DIRECTORIES = [
    __DIR__ . '/../../composer', // In /local/composer

    __DIR__ . '/', //In /local/php_interface/include
    __DIR__ . '/../', //In /local/php_interface
    __DIR__ . '/../../', //In /local
    __DIR__ . '/../../../', // In /
    __DIR__ . '/../../../../', // In /../ (outside the webroot)

    __DIR__ . '/../lib', //In /local/php_interface/lib
    __DIR__ . '/../../lib/', //In /local/lib (overlaps with our 'lib' folder for the 'psr4', unfortunately)
    __DIR__ . '/../../../lib/', // In /lib
    __DIR__ . '/../../../../lib/', // In /../lib (outside the webroot)

    __DIR__ . '/../libs', //In /local/php_interface/libs
    __DIR__ . '/../../libs/', //In /local/libs
    __DIR__ . '/../../../libs/', // In /libs
    __DIR__ . '/../../../../libs/', // In /../libs (outside the webroot)

    __DIR__ . '/../../../bitrix', // In /bitrix (does Bitrix use it by default?). Should it be checked earlier?
    __DIR__ . '/../../../bitrix/php_interface/', // In /bitrix/php_interface
    __DIR__ . '/../../../bitrix/php_interface/lib', // In /bitrix/php_interface/lib
    __DIR__ . '/../../../bitrix/php_interface/libs', // In /bitrix/php_interface/libs
    __DIR__ . '/../../../bitrix/php_interface/include', // In /bitrix/php_interface
    __DIR__ . '/../../../bitrix/php_interface/include/lib', // In /bitrix/php_interface/lib
    __DIR__ . '/../../../bitrix/php_interface/include/libs', // In /bitrix/php_interface/libs
];

/**
 * Подключать PSR-0 и PSR-4 автозагрузчики
 * Y - Всегда заранее
 * A - Заранее при отсутствии Composer'а
 * N - Только тогда, когда в них есть потребность
 */
const USE_PSR04_AUTOLOADER = 'A';

const GSV_COMPONENTS_NAMESPACE = [
    '\Gsv\Components\\' => true,
    '\Gsv\ComponentAjax\\' => 'ajax.php',
    '\Gsv\ComponentAjaxClass\\' => 'ajax_class.php',
    //'\Gsv\ComponentTemplateAjaxClass\\' => false, //FIXME: There should be a naming convention!
];

const CUSTOM_SALE_DELIVERY_CLASSES = [];
const CUSTOM_SALE_DELIVERY_RESTRICTIONS_CLASSES = [];
const CUSTOM_SALE_PAYSYSTEM_RESTRICTIONS_CLASSES = [];

//const GSV_DEBUG = false;