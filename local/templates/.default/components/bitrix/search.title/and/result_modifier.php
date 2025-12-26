<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

//$arIBlocks = array();

$arResult["SEARCH"] = array();
foreach($arResult["CATEGORIES"] as $category_id => $arCategory)
{
	foreach($arCategory["ITEMS"] as $i => $arItem)
	{
		if(isset($arItem["ITEM_ID"]))
			$arResult["SEARCH"][] = &$arResult["CATEGORIES"][$category_id]["ITEMS"][$i];
	}
}

$arResult['ITEMS'] = [];
$arResult['SECTIONS'] = [];
foreach($arResult["SEARCH"] as $i=>$arItem)
{
	$file = false;
	switch($arItem["MODULE_ID"])
	{

		case "iblock":/*
			if(CModule::IncludeModule('iblock'))
			{
				if(!array_key_exists($arItem["PARAM2"], $arIBlocks))
					$arIBlocks[$arItem["PARAM2"]] = CIBlock::GetArrayByID($arItem["PARAM2"]);
			}
*/
            if(is_string($arItem['ITEM_ID']) && $arItem['ITEM_ID'][0] == 'S') {
                $arItem['NAME'] = strip_tags($arItem['NAME']);
                $arResult['SECTIONS'][substr($arItem['ITEM_ID'][0],1)] = $arItem;
            } else {
                $arResult['ITEMS'][] = $arItem['ITEM_ID'];
            }
			break;

	}
}