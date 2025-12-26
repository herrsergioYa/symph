<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arTemplateParameters = array(
	"PARAMETERS" => array(
		"IS_AJAX" => array(
			"NAME" => GetMessage('GSV_IS_AJAX'),
			"TYPE" => 'CHECKBOX',
			"DEFAULT" => "N",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
	)
);