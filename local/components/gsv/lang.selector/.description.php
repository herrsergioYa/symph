<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("T_GSV_LANG_SELECTOR"),
	"DESCRIPTION" => GetMessage("T_GSV_LANG_SELECTOR_DESC"),
	"ICON" => "/images/news_list.gif",
	"SORT" => 20,
//	"SCREENSHOT" => array(
//		"/images/post-77-1108567822.jpg",
//		"/images/post-1169930140.jpg",
//	),
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "gsv",
			"NAME" => GetMessage("T_GSV_COMPONENT_SECTION"),
			"SORT" => 10,
			"CHILD" => array(
				"ID" => "gsv_components",
			),
		),
	),
);

?>