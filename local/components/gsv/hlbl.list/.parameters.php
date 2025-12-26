<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arCurrentValues */

//Нам нужны константы из модуля iblock... :-(
if(!CModule::IncludeModule("highloadblock") || !CModule::IncludeModule("iblock"))
	return;

//$arTypesEx = CIBlockParameters::GetIBlockTypes(array("-"=>" "));
//
//$arIBlocks=array();
//$db_iblock = CIBlock::GetList(array("SORT"=>"ASC"), array("SITE_ID"=>$_REQUEST["site"], "TYPE" => ($arCurrentValues["IBLOCK_TYPE"]!="-"?$arCurrentValues["IBLOCK_TYPE"]:"")));
//while($arRes = $db_iblock->Fetch())
//	$arIBlocks[$arRes["ID"]] = "[".$arRes["ID"]."] ".$arRes["NAME"];

$arHlbls = [];
$arHlbls2 = [];

foreach(\Bitrix\Highloadblock\HighloadblockTable::getList([])->fetchAll() as $arHlbl)
{
    $arHlbls[$arHlbl['ID']] = $arHlbl['NAME'];
    $arHlbls2[$arHlbl['ID']] = $arHlbl;
}

//$arSorts = array("ASC"=>GetMessage("T_IBLOCK_DESC_ASC"), "DESC"=>GetMessage("T_IBLOCK_DESC_DESC"));
//$arSortFields = array(
//		"ID"=>GetMessage("T_IBLOCK_DESC_FID"),
//		"NAME"=>GetMessage("T_IBLOCK_DESC_FNAME"),
//		"ACTIVE_FROM"=>GetMessage("T_IBLOCK_DESC_FACT"),
//		"SORT"=>0GetMessage("T_IBLOCK_DESC_FSORT"),
//		"TIMESTAMP_X"=>GetMessage("T_IBLOCK_DESC_FTSAMP")
//	);
//
//$arProperty_LNS = array();
//$rsProp = CIBlockProperty::GetList(array("sort"=>"asc", "name"=>"asc"), array("ACTIVE"=>"Y", "IBLOCK_ID"=>(isset($arCurrentValues["IBLOCK_ID"])?$arCurrentValues["IBLOCK_ID"]:$arCurrentValues["ID"])));
//while ($arr=$rsProp->Fetch())
//{
//	$arProperty[$arr["CODE"]] = "[".$arr["CODE"]."] ".$arr["NAME"];
//	if (in_array($arr["PROPERTY_TYPE"], array("L", "N", "S")))
//	{
//		$arProperty_LNS[$arr["CODE"]] = "[".$arr["CODE"]."] ".$arr["NAME"];
//	}
//}
$arHlbl = $arHlbls2[$arCurrentValues['HLBL_ID']];

$arProperty_LNS = array();
$fields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('HLBLOCK_'.$arHlbl['ID'], 0, LANGUAGE_ID);
//$rsProp = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$arCurrentValues["IBLOCK_ID"]));
foreach ($fields as $key => $field)
{
//	$arProperty[$arr["CODE"]] = "[".$arr["CODE"]."] ".$arr["NAME"];
//	if (in_array($arr["PROPERTY_TYPE"], array("L", "N", "S", "E")))
//	{
    $arProperty_LNS[$key] = $field['EDIT_FORM_LABEL'] ?: $field['FIELD_NAME'];
//	}
}

$arSortFields = ['ID' => 'ID'];
$arSortFields += $arProperty_LNS;
$arSorts = ['ASC' => 'По возрастанию', 'DESC' => 'По убыванию'];

$arUGroupsEx = Array();
$dbUGroups = CGroup::GetList($by = "c_sort", $order = "asc");
while($arUGroups = $dbUGroups -> Fetch())
{
    $arUGroupsEx[$arUGroups["ID"]] = $arUGroups["NAME"];
}

$arComponentParameters = array(
	"GROUPS" => array(
	),
	"PARAMETERS" => array(

        "HLBL_ID" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage("T_HLBL_LIST_NAME"),
            "TYPE" => "LIST",
            "VALUES" => $arHlbls,
            "REFRESH" => 'Y',
        ],

		/*"AJAX_MODE" => array(),
		"IBLOCK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("T_IBLOCK_DESC_LIST_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arTypesEx,
			"DEFAULT" => "news",
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("T_IBLOCK_DESC_LIST_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlocks,
			"DEFAULT" => '={$_REQUEST["ID"]}',
			"ADDITIONAL_VALUES" => "Y",
			"REFRESH" => "Y",
		),*/
		"NEWS_COUNT" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("T_IBLOCK_DESC_LIST_CONT"),
			"TYPE" => "STRING",
			"DEFAULT" => "20",
		),
		"SORT_BY1" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("T_IBLOCK_DESC_IBORD1"),
			"TYPE" => "LIST",
			"DEFAULT" => "ACTIVE_FROM",
			"VALUES" => $arSortFields,
			"ADDITIONAL_VALUES" => "Y",
		),
		"SORT_ORDER1" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("T_IBLOCK_DESC_IBBY1"),
			"TYPE" => "LIST",
			"DEFAULT" => "DESC",
			"VALUES" => $arSorts,
			"ADDITIONAL_VALUES" => "Y",
		),
		"SORT_BY2" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("T_IBLOCK_DESC_IBORD2"),
			"TYPE" => "LIST",
			"DEFAULT" => "SORT",
			"VALUES" => $arSortFields,
			"ADDITIONAL_VALUES" => "Y",
		),
		"SORT_ORDER2" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("T_IBLOCK_DESC_IBBY2"),
			"TYPE" => "LIST",
			"DEFAULT" => "ASC",
			"VALUES" => $arSorts,
			"ADDITIONAL_VALUES" => "Y",
		),
		"FILTER_NAME" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("T_IBLOCK_FILTER"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		),
		"FIELD_CODE" => //CIBlockParameters::GetFieldCode(GetMessage("IBLOCK_FIELD"), "DATA_SOURCE"),
		/*"PROPERTY_CODE" =>*/ array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("IBLOCK_FIELD"),//GetMessage("T_IBLOCK_PROPERTY"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arProperty_LNS,
			"ADDITIONAL_VALUES" => "Y",
		),

        "DETAIL_URL" => array(
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("T_IBLOCK_DESC_DETAIL_PAGE_URL"),
            "TYPE" => "STRING",
            "DEFAULT" => "/hlbl/#HLBL_ID#/#ELEMENT_ID#/",
        ),
        "LIST_URL" => array(
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("T_IBLOCK_DESC_LIST_PAGE_URL"),
            "TYPE" => "STRING",
            "DEFAULT" => "/hlbl/#HLBL_ID#/",
        ),
        "CODE_FIELD" => array(
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => 'CODE_FIELD',
            "TYPE" => "STRING",
            "DEFAULT" => "UF_XML_ID",
            "VALUES" => $arProperty_LNS,
            "ADDITIONAL_VALUES" => "Y",
        ),

        /*
		"CHECK_DATES" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("T_IBLOCK_DESC_CHECK_DATES"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"DETAIL_URL" => CIBlockParameters::GetPathTemplateParam(
			"DETAIL",
			"DETAIL_URL",
			GetMessage("T_IBLOCK_DESC_DETAIL_PAGE_URL"),
			"",
			"URL_TEMPLATES"
		),
		"PREVIEW_TRUNCATE_LEN" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_PREVIEW_TRUNCATE_LEN"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		),
		"ACTIVE_DATE_FORMAT" => CIBlockParameters::GetDateFormat(GetMessage("T_IBLOCK_DESC_ACTIVE_DATE_FORMAT"), "ADDITIONAL_SETTINGS"),
		"SET_TITLE" => array(),
		"SET_BROWSER_TITLE" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BNL_SET_BROWSER_TITLE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"SET_META_KEYWORDS" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BNL_SET_META_KEYWORDS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"SET_META_DESCRIPTION" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BNL_SET_META_DESCRIPTION"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"SET_LAST_MODIFIED" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BNL_SET_LAST_MODIFIED"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
		"INCLUDE_IBLOCK_INTO_CHAIN" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_INCLUDE_IBLOCK_INTO_CHAIN"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"ADD_SECTIONS_CHAIN" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_ADD_SECTIONS_CHAIN"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"HIDE_LINK_WHEN_NO_DETAIL" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_HIDE_LINK_WHEN_NO_DETAIL"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
		"PARENT_SECTION" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("IBLOCK_SECTION_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => '',
		),
		"PARENT_SECTION_CODE" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("IBLOCK_SECTION_CODE"),
			"TYPE" => "STRING",
			"DEFAULT" => '',
		),
		"INCLUDE_SUBSECTIONS" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BNL_INCLUDE_SUBSECTIONS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"STRICT_SECTION_CHECK" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BNL_STRICT_SECTION_CHECK"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),*/
		"CACHE_TIME"  =>  array("DEFAULT"=>36000000),
		"CACHE_FILTER" => array(
			"PARENT" => "CACHE_SETTINGS",
			"NAME" => GetMessage("IBLOCK_CACHE_FILTER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
		"CACHE_GROUPS" => array(
			"PARENT" => "CACHE_SETTINGS",
			"NAME" => GetMessage("CP_BNL_CACHE_GROUPS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
        "USE_PERMISSIONS" => Array(
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("T_IBLOCK_DESC_USE_PERMISSIONS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
            "REFRESH" => "Y",
        ),
        "GROUP_PERMISSIONS" => Array(
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("T_IBLOCK_DESC_GROUP_PERMISSIONS"),
            "TYPE" => "LIST",
            "VALUES" => $arUGroupsEx,
            "DEFAULT" => Array(1),
            "MULTIPLE" => "Y",
        ),
	),
);

if($arCurrentValues["USE_PERMISSIONS"]!="Y")
    unset($arComponentParameters["PARAMETERS"]["GROUP_PERMISSIONS"]);

CIBlockParameters::AddPagerSettings(
	$arComponentParameters,
    GetMessage('T_HLBL_LIST_NAME'),//"T_IBLOCK_DESC_PAGER_NEWS"), //$pager_title
	false,//true, //$bDescNumbering
	true, //$bShowAllParam
	true, //$bBaseLink
	$arCurrentValues["PAGER_BASE_LINK_ENABLE"]==="Y" //$bBaseLinkEnabled
);

//CIBlockParameters::Add404Settings($arComponentParameters, $arCurrentValues);
