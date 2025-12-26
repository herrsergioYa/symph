<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
return;
if ($arResult["404"] == "Y") {
    \Bitrix\Iblock\Component\Tools::process404("", true, true, true);
    die();
} else {
    $GLOBALS["CHECKED"] = $arResult["CHECKED"];
}
?>