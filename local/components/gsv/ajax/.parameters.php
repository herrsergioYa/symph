<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentParameters = array(
	"PARAMETERS" => array(
		"ACTION_VARIABLE" => array(
			"NAME" => GetMessage('GSV_ACTION_VARIABLE'),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "action",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
	)
);