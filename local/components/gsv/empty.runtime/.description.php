<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("T_IBLOCK_DESC_RUNTIME"),
	"DESCRIPTION" => GetMessage("T_IBLOCK_DESC_RUNTIME_DESC"),
	"SORT" => 20,

	//"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "gsv_empty",
		/*"CHILD" => array(
			"ID" => "gsv",
			"NAME" => GetMessage("T_IBLOCK_DESC_EMPTY"),
			"SORT" => 10,
			"CHILD" => array(
				"ID" => "gsv_empty",
			),
		),*/
	),
);
