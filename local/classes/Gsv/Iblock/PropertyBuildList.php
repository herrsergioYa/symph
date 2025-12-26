<?
namespace Gsv\Iblock;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;

/**
 * Создает свойство iblock.
 */
class PropertyBuildList
{
    protected static $arCaches = array();

    //Will force the inheriting class to autoload so must be used with caution!
    public static function addEventHandler(?\Bitrix\Main\EventManager $eventManager = null)
    {
        if($eventManager === null) {
            $eventManager = \Bitrix\Main\EventManager::getInstance();
        }

        $eventManager->addEventHandler(
            'iblock',
            'OnIBlockPropertyBuildList',
            array(
                static::class,
                'onIBlockPropertyBuildListHandler',
            )
        );
    }

    //Will read the filesystem
    public static final function addAllEventHandlers(?\Bitrix\Main\EventManager $eventManager = null)
    {
        if($eventManager === null) {
            $eventManager = \Bitrix\Main\EventManager::getInstance();
        }

        //$matches = [];
        foreach (scandir(__DIR__ .'/PropertyBuildList') as $file) {
            if(substr($file, -4) === '.php') {
            //if(preg_match('/^([\S]+)\.php$/', $file, $matches)) {
                $eventManager->addEventHandler(
                    'iblock',
                    'OnIBlockPropertyBuildList',
                    array(
                        __NAMESPACE__ . '\\PropertyBuildList\\' . substr($file, 0, -4),//$matches[1],
                        'onIBlockPropertyBuildListHandler',
                    )
                );
            }
        }
    }

    public static function OnIBlockPropertyBuildListHandler()
    {
        return array(/*
            'PROPERTY_TYPE' => 'N',
            'USER_TYPE' => 'SGsvSomething',
            //'USER_TYPE_ID' => 'SGsvSomething',
            'DESCRIPTION' => 'Gsv: Что-то',
            'GetPropertyFieldHtml' => array(static::class, 'GetPropertyFieldHtml'),
            'GetSettingsHTML' => array(static::class, 'GetSettingsHTML'),
        */);
    }

    protected static function GetCache()
    {
        $key = static::GetCacheKey();
        if(!isset(static::$arCaches[$key])) {
            static::$arCaches[$key] = array();
        }
        return static::$arCaches[$key];
    }

    protected static function SetCache($arCache)
    {
        $key = static::GetCacheKey();
        static::$arCaches[$key] = $arCache;
    }

    protected static function GetCacheKey()
    {
        return static::class;
    }
}
