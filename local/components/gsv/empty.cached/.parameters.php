<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();


$arComponentParameters = array(
	"PARAMETERS" => array(
		"CACHE_TIME"  =>  array("DEFAULT"=>3600),
		"CACHE_GROUPS" => array(
			"PARENT" => "CACHE_SETTINGS",
			"NAME" => GetMessage("CP_EMPTY_CACHE_GROUPS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
	),
);
