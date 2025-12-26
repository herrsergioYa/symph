<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

if(isset($arParams["IBLOCK_ID"]) && $USER->IsAuthorized() && $APPLICATION->GetShowIncludeAreas())
{
    if(\Bitrix\Main\Loader::includeModule("iblock"))
    {
        $arButtons = \CIBlock::GetPanelButtons(
            $arParams["IBLOCK_ID"],
            0,
            $arParams["PARENT_SECTION"] ?: 0,
            array("SECTION_BUTTONS"=>false)
        );

        if($APPLICATION->GetShowIncludeAreas())
            $this->addIncludeAreaIcons(\CIBlock::GetComponentMenu($APPLICATION->GetPublicShowMode(), $arButtons));
    }
}

if($this->startResultCache(false, array(($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()), $_GET, $_POST, $_FILES)))
{
    $this->setResultCacheKeys(array());
	$this->includeComponentTemplate();
}


