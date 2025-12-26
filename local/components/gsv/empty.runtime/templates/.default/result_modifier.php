<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */

/* // Для добавления меню "Эрмитаж"
foreach ($arResult["ITEMS"] as &$arItem) {
    $arButtons = \CIBlock::GetPanelButtons(
        $arItem["IBLOCK_ID"],
        $arItem["ID"],
        0,
        array("SECTION_BUTTONS" => false, "SESSID" => false)
    );
    $arItem["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"];
    $arItem["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"];
}
unset($arItem);
*/