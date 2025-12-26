<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class GsvAjaxAjax extends \CBitrixComponent
{
    protected $arOriginalParams = null;

    public function onPrepareComponentParams($arParams)
    {
        global $APPLICATION;

        //Store them to restore for AjaX
        $this->arOriginalParams = $arParams;

        $arParams = parent::onPrepareComponentParams($arParams);

        return $arParams;
    }
}