<?php

if(!defined('GSV_ESEARCH_IBLOCK_ID'))
{
    $arIblockIds = [
        \Bitrix\Main\Config\Option::get('gsv.esearch', "product_iblock_id", ''),
        \Bitrix\Main\Config\Option::get('gsv.esearch', "offers_iblock_id", ''),
        \Bitrix\Main\Config\Option::get('gsv.esearch', "brand_iblock_id", ''),
    ];
    define('GSV_ESEARCH_IBLOCK_ID', $arIblockIds);
}

if(!defined('GSV_ESEARCH_URL'))
{
    $eUrl = \Bitrix\Main\Config\Option::get('gsv.esearch', "esearch_url", '');
    define('GSV_ESEARCH_URL', $eUrl);
}

if(!defined('GSV_ESEARCH_HTTP_AUTH'))
{
    $httpAuth = \Bitrix\Main\Config\Option::get('gsv.esearch', "http_auth", '');
    $httpAuth = trim($httpAuth);
    if($httpAuth) {
        $httpAuth = ['Authorization' => $httpAuth];
    } else {
        $httpAuth = [];
    }
    define('GSV_ESEARCH_HTTP_AUTH', $httpAuth);
}