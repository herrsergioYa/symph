<?php


namespace Gsv\Bitrix;

/**
 * Класс ajax-контроллера для PHP
 */
class JsonAjaxController extends \Gsv\Util\JsonAjaxController
{
    /**
     * Отображение вывода, адаптированное под Bitrix
     * @param $response
     */
    protected static function render($response)
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();

        $response = json_encode($response);
        if(strtoupper(LANG_CHARSET) != 'UTF-8') {
            $response = mb_convert_encoding($response, 'UTF-8');
        }

        http_response_code(200);//TODO: Следует ли это изменить?
        header("Content-Type: application/json; charset=utf-8");
        header("Content-Length: ".strlen($response));
        echo $response;

        die();
    }

//    protected static function exec($callable, $arParams)
//    {
//        if($callable[1] == 'abc' || static::isGetRequest()) {
//            //Possible check for a specific callable
//            if ($_REQUEST['sessid'] != bitrix_sessid()) {
//                $APPLICATION->RestartBuffer();
//                http_response_code(403);
//                die();
//            }
//        }
//        return parent::exec($callable, $arParams);
//    }

    public static function buildQuery($useSessId = 'Y', $ACTION_NAME = false, $SESSID_NAME = false,
                                      $siteId = false, $adminSection = false, $siteTemplateId = false)
    {
        $query = '';

        if($siteId === false)
            $siteId = SITE_ID;
        if($siteId !== '')
            $query = '&SITE_ID=' . urlencode($siteId);

        if($adminSection === false)
            $adminSection = defined('ADMIN_SECTION') && ADMIN_SECTION === true;
        else
            $adminSection = $adminSection === 'Y';

        if($adminSection)
            $query .= '&admin_section=Y';

        if($siteTemplateId === false)
            $siteTemplateId = defined('SITE_TEMPLATE_ID') && is_string(SITE_TEMPLATE_ID) ? SITE_TEMPLATE_ID : '';

        if($siteTemplateId !== '')
            $query .= '&SITE_TEMPLATE_ID=' . urlencode($siteTemplateId);

        return parent::buildQuery($useSessId, $ACTION_NAME, $SESSID_NAME) . $query;
    }

    public static function getSessId()
    {
        return bitrix_sessid();
    }
}