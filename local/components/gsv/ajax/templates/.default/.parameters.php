<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arTemplateParameters = array(
    "ACTION_VARIABLE" => array(
        "NAME" => GetMessage('GSV_ACTION_VARIABLE'),
        "TYPE" => "STRING",
        "MULTIPLE" => "N",
        "DEFAULT" => "action",
        "PARENT" => "ADDITIONAL_SETTINGS",
    ),
    /*"IS_AJAX" => array(
        "NAME" => GetMessage('GSV_IS_AJAX'),
        "TYPE" => "CHECKBOX",
        "MULTIPLE" => "N",
        "DEFAULT" => "N",
        "PARENT" => "ADDITIONAL_SETTINGS",
    ),*/
);