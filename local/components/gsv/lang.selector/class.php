<?

use \Bitrix\Main;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Error;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Iblock;
use \Bitrix\Iblock\Component\ElementList;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CIntranetToolbar $INTRANET_TOOLBAR
 */

Loc::loadMessages(__FILE__);


class GsvLangSelectorComponent extends CBitrixComponent
{
	public function __construct($component = null)
	{
		parent::__construct($component);
	}

	public function onPrepareComponentParams($params)
	{
		$params = parent::onPrepareComponentParams($params);

        if(is_string($params['LANG_LIST']) && $params['LANG_LIST']) {
            $params['LANG_LIST'] = explode(',', $params['LANG_LIST']);
        } else if(!is_array($params['LANG_LIST'])) {
            $params['LANG_LIST'] = [];
        }

        if(empty($params['LANG_BASE'])) {
            $params['LANG_BASE'] = 'en';
        }

		return $params;
	}
}
