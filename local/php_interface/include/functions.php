<?php

/**
 * @param mixed $arr Выводимые данные
 * @param string|bool $filename Имя файла, куда осуществить запись; false для записи на экран и true для генерации имени файла
 * @param string|bool|null $meOnly - Выводить только для меня/админа; null - в файл выводить всем ('N'), на экран - админу-мне ('Y').
 * 'A' - выводить всем админам. Всякое иное значение - не выводить никому, но я могу добавить новые, так что пусть это будет 'X'.
 * Для совместимости с предыдущей версией true === 'A' и false === 'N'. А вот по null совместимость нарушена (было 'A', а стало 'Y')
 * @param ?bool $fullStack - Выводить полный стек вызовов; null - в файл выводить, на экран - только последний вызов.
 * @param bool $die - Завершить процесс после вывода (Полезно, если потом RestartBuffer())
 * @return void
 */
function gsv_dump($arr, $filename = false, $meOnly = null, $fullStack = null, $die = false)
{
    if($meOnly === null) {
        $meOnly = $filename ? 'N' : 'Y';
    } else if(is_bool($meOnly)) {
        //Compatibility
        $meOnly = $meOnly ? 'A' : 'N';
    }
    if($fullStack === null) {
        $fullStack = !!$filename;
    }
    if(!gsv_am_i_a_debug_user($meOnly)) {
        return;
    }
    $files = gsv_debug_trace($fullStack);
    if($filename) {
        $checkHtAccess = null;
        if(!is_string($filename)) {
            $filename = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/log'. date('Ymd') . '.log';
            $checkHtAccess = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/.htaccess';
        } else if(($pos = strpos($filename, '*')) !== false) {
            $filename = str_replace('*', date('Ymd'), $filename);
        }
        $message = date("Y-m-d H:i:s P");
        $message .= "\n";
        $message .= implode("\n", $files);
        $message .= "\n";
        global $USER;
        if($USER && is_object($USER) && $USER->IsAuthorized()) {
            $message .= "USER_ID = " . $USER->GetID() . " GROUPS = " . $USER->GetGroups() . " IS_ADMIN = " . ($USER->IsAdmin() ? 'true' : 'false');
        } else {
            $message .= "UNAUTHORIZED USER";
        }
        $message .= "\n\n";
        $message .= var_export($arr, true);
        $message .= "\n\n";

        $dir = dirname($filename);
        if(!is_dir($dir)) {
            mkdir($dir, 0777, true);
            if(empty($checkHtAccess)) {
                $checkHtAccess = $dir . '/.htaccess';
            }
        }

        if($checkHtAccess) {
            if(!file_exists($checkHtAccess)) {
                file_put_contents($checkHtAccess, "Deny from all\n");
            }
        }

        file_put_contents($filename, $message, LOCK_EX | FILE_APPEND);
    } else {
        echo '<div style="font-size: 14px; color: white; background-color: white; border-color: black; border-width: 1px; border-style: none">';
            echo '<div style="background-color: red; padding: 2px; margin: 0; color: white; border-color: black; border-width:2px; border-style: dotted">';
            foreach ($files as $file) {
                echo '<p style="text-align: left; text-indent: 0; padding: 3px 5px; color: white;">';
                    echo $file;
                echo '</p>';
            }
            echo '</div>';
            echo '<div style="background-color: white; padding: 3px; margin: 0; color: black; border-color: black; border-width: 3px; border-style: dashed;">';
                echo '<pre style="color: black; padding: 4px 7px; margin: 4px 7px;">';
                    var_dump($arr);
                echo '</pre>';
            echo '</div>';
        echo '</div>';
    }

    if($die) {
        die();
    }
}

/**
 * @param string $meOnly - Отладка только для меня/админа. 'Y' - только админа-меня. 'N' - для вообще всех (даже не админов!).
 * 'A' - выводить всем админам. Всякое иное значение - не воводить никому, но я могу добавить новые, так что пусть это будет 'X'.
 * @return bool
 */
function gsv_am_i_a_debug_user($meOnly)
{
    if ($meOnly != 'N') {
        if ($meOnly != 'Y' && $meOnly != 'A') {
            return false;
        }
        // Проверять два следующих условия именно в такой последовательности,
        // чтобы неадмин не мог достучаться до отладки через куки/параметра запроса.
        global $USER;
        if (!($USER && is_object($USER) && $USER->IsAdmin())) {
            return false;
        }
        if($meOnly == 'Y') {
            if(defined('DEBUG_USER_LOGIN') && DEBUG_USER_LOGIN) {
                if(in_array($USER->GetLogin(), DEBUG_USER_LOGIN)) {
                    return true;
                }
            }
            if(defined('DEBUG_USER_ID') && DEBUG_USER_ID) {
                if(in_array($USER->GetID(), DEBUG_USER_ID)) {
                    return true;
                }
            }
            if(defined('DEBUG_GROUP_USER_ID') && DEBUG_GROUP_USER_ID) {
                if(array_intersect($USER->GetUserGroupArray(), DEBUG_GROUP_USER_ID)) {
                    return true;
                }
            }
            if(!defined('XDEBUG_IDE_KEY_4_GSV_DUMP')) {
                //А кто я, неизвестно...
                return false;
            }
            $xDebugSessionKey = gsv_get_xdebug_key();
            if (empty($xDebugSessionKey) || $xDebugSessionKey != XDEBUG_IDE_KEY_4_GSV_DUMP) {
                return false;
            }
        }
    }
    return true;
}

function gsv_is_debug()
{
    //Определить эту константу в dbconn.php
    if(defined('GSV_DEBUG'))
    {
        return GSV_DEBUG === true || GSV_DEBUG === 'Y';
    }
    //There can be a specific condition for a specific site
//    if(defined('IS_DEV'))
//    {
//        return IS_DEV === true || IS_DEV === 'Y';
//    }

    //Fallback'и
    $dev = preg_match('/^dev(crm)?\./i', $_SERVER['SERVER_NAME'])
        || preg_match('/^test\./i', $_SERVER['SERVER_NAME'])
        || preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i', $_SERVER['SERVER_NAME'])
        //TODO: Add an IPv6-regex...
        || preg_match('/\.devarium\.(com|ru)$/i', $_SERVER['SERVER_NAME'])
        || preg_match('/\.moozzg\.(com|ru)$/i', $_SERVER['SERVER_NAME']);

    return $dev;
}

function gsv_js_dump($arr, $meOnly = 'A', $fullStack = false)
{
    if (!gsv_am_i_a_debug_user($meOnly)) {
        return;
    }

    $files = gsv_debug_trace($fullStack);
    ?>
    <script>
        console.log(<?= \CUtil::PhpToJSObject($files) ?>, <?= \CUtil::PhpToJSObject($arr) ?>);
    </script>
    <?php
}

function gsv_debug_trace($fullStack = true)
{
    $files = [];
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') . '/';
    $docRootOffset = strlen($docRoot);
    $skippedTheCaller = false;
    foreach(debug_backtrace() as $bt) {
        if(!$skippedTheCaller) {
            $skippedTheCaller = true;
            continue;
        }
        $file = $bt['file'];
        $file = str_replace('\\', '/', $file);
        if(strpos($file, $docRoot) === 0) {
            $file = substr($file, $docRootOffset);
        }
        if($files) {
            $files[] .= "\t" . $bt['class'] . $bt['type'] . $bt['function'] . '()';
        }
        $files[] = $file . ":" . $bt['line'];
        if(!$fullStack) {
            break;
        }
    }

    return $files;
}

function gsv_get_xdebug_key()
{
    if($_REQUEST['XDEBUG_SESSION_START']){
        return $_REQUEST['XDEBUG_SESSION_START'];
    } else if($_COOKIE['XDEBUG_SESSION']) {
        return $_COOKIE['XDEBUG_SESSION'];
    } else {
        return false;
    }
}

function php_version_id()
{
    if (!defined('PHP_VERSION_ID')) {
        $version = explode('.', PHP_VERSION);
        $version = intval($version[0]) * 10000 + intval($version[1]) * 100 + intval($version[2]);
    } else {
        $version = PHP_VERSION_ID;
    }
    return $version;
}

if (!function_exists('is_bitrix_included')) {
    function is_bitrix_included(): bool
    {
        return defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true;
    }
}

function bitrix_version_array(): array
{
    return is_bitrix_included() && defined('SM_VERSION') ? explode('.', SM_VERSION) : false;
}

if(is_bitrix_included()) {
    function sendJsonAnswer($result)
    {
        global $APPLICATION;

        $APPLICATION->RestartBuffer();
        header('Content-Type: application/json');

        echo \Bitrix\Main\Web\Json::encode($result);

        \CMain::FinalActions();
        die();
    }
} else {
    function sendJsonAnswer($result)
    {
        header('Content-Type: application/json');

        echo json_encode($result);

        die();
    }
}

function executeAjaxAction($object_or_class, $action, $params, $decorator = null)
{
    $callable = [
        $object_or_class,
        $action . 'Action'
    ];

    if(is_callable($callable))
    {
        if(is_callable($decorator))
        {
            $result = $decorator($callable, $params);
        }
        else
        {
            $result = $callable($params);
        }
    }
    else
    {
        if(is_callable($decorator))
        {
            //A fallback?
            $result = $decorator(false, $params);
        }
        else
        {
            $result = [];
        }
    }

    sendJsonAnswer($result);
    die();//Should never be called!!
}

/**
 * Получение множественного число
 * @param string|integer $number - например число товаров
 * @param string[] $item - предмет, например, для русского языка ['товар', 'товара', 'товаров']
 * @param string $lang - язык ('ru' или 'en')
 */
function gsv_plural_form($number, $item, $lang = 'ru')
{
    static $cases = array (2, 0, 1, 1, 1, 2);
    $lang = strtolower($lang);
    if($lang == 'ru') {
        return $item[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    } else if($lang == 'en') {
        //0 itemS <!--
        return $number == 1 ? $item[0] : $item[1];
    } else {
        return null;
    }
}
