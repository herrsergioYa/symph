<?php

//The Bitrix-specific code follows
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    return;
}

if(!function_exists('showHashTmplTemplate')) {
    function showHashTmplTemplate(
        $templateFile,
        &$arResult,
        &$arParams,
        $arLangMessages,
        $templateFolder,
        $parentTemplateFolder,
        \CBitrixComponentTemplate $template
    )
    {
        static $hashTemplateEngine = null;

        if($hashTemplateEngine === null) {
            $hashTemplateEngine = new \Gsv\Util\Templates\HashTemplateEngine();
        }

        $hashTemplateEngine->displayFileInDocRoot($templateFile,
            $arResult + $arLangMessages + $arParams + array(
                'templateFile' => $templateFile,
                'templateFolder' => $templateFolder,
                'parentTemplateFolder' => $parentTemplateFolder,
            )
        );

        $component_epilog = $templateFolder . '/component_epilog.php';
        if(file_exists($_SERVER['DOCUMENT_ROOT'] . $component_epilog)) {
            $component = $template->__component;
            /** @var \CBitrixComponent $component */
            $component->SetTemplateEpilog(array(
                'epilogFile' => $component_epilog,
                'templateName' => $template->__name,
                'templateFile' => $template->__file,
                'templateFolder' => $template->__folder,
                'templateData' => false,
            ));
        }
    }
}


global $arCustomTemplateEngines;
if(!is_array($arCustomTemplateEngines)) {
    $arCustomTemplateEngines = [];
}

$arCustomTemplateEngines['hashTemplate'] = [
    'templateExt' => ['html', 'htm'],
    'function'    => 'showHashTmplTemplate',
];
