<?php

//TODO: Should we check it now!?
//if(!class_exists(\Twig\Environment::class)) {
//    //No fallback. At least for now.
//    return;
//}

//The Bitrix-specific code follows
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    return;
}

if(!function_exists('showTwigTemplate')) {
    function showTwigTemplate(
        $templateFile,
        &$arResult,
        &$arParams,
        $arLangMessages,
        $templateFolder,
        $parentTemplateFolder,
        \CBitrixComponentTemplate $template
    )
    {
        /*if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
            throw new \Exception('Пролог не подключен');
        }*/

        //Preprocess with PHP?
        /*ob_start();
        //$template->__IncludePHPTemplate($arResult, $arParams, $parentTemplateFolder);
        $file = $_SERVER['DOCUMENT_ROOT'] . $templateFile;
        (function(&$arResult, &$arParams) use($file) {
            require $file;
        })($arResult, $arParams);
        $TEMPLATE = ob_get_clean();*/

        static $twigTemplateEngine = null;

        if($twigTemplateEngine === null) {
            $twigTemplateEngine = new \Gsv\Util\Templates\TwigTemplateEngine();
        }

        $twigTemplateEngine->displayFileInDocRoot($templateFile, array(
            'arResult' => $arResult,
            'arParams' => $arParams,
            'arLangMessages' => $arLangMessages,
            'templateFile' => $templateFile,
            'templateFolder' => $templateFolder,
            'parentTemplateFolder' => $parentTemplateFolder,
        ));

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

$arCustomTemplateEngines['twig'] = [
    'templateExt' => ['twig'],
    'function'    => 'showTwigTemplate',
];
