<?
namespace Gsv\EventHandlers\IBlock;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;

/**
 * Создает свойство iblock.
 */
class OnIBlockPropertyBuildList
{
    protected static $arCaches = array();

    //Will force the class to autoload so must be used wth caution!
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

    public static function addAllEventHandlers(?\Bitrix\Main\EventManager $eventManager = null)
    {
        if($eventManager === null) {
            $eventManager = \Bitrix\Main\EventManager::getInstance();
        }

        $matches = [];
        foreach (scandir(__DIR__) as $file) {
            if(preg_match('/^(OnIBlockPropertyBuildList_[\S]+)\.php$/', $file, $matches)) {
                $eventManager->addEventHandler(
                    'iblock',
                    'OnIBlockPropertyBuildList',
                    array(
                        __NAMESPACE__ . '\\' . $matches[1],
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
