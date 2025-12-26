<?
namespace Gsv\ComponentAjax;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();

class AjaxController extends \Bitrix\Main\Engine\Controller
{
    #в параметры будут автоматически подставлены данные из REQUEST
    public function sayByeAction($param1 = '-')
    {
        //Если необходимо работать с подписанными параметрам внутри ajax.php
        //$arParams = $this->getUnsignedParameters();

        return [];
    }
}
