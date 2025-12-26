<?php

//TODO: It can be included by Composer already so... But I cannot check it or it may go to the missing classes.
if(!class_exists(\Handlebars\Handlebars::class, false)
    && is_dir(__DIR__ . '/php') && is_dir(__DIR__ . '/php/Handlebars')
    ) {

    /** @var \Composer\Autoload\ClassLoader[] $composerClassLoader */
    global $composerClassLoaders;
    /** @var ?\Psr4AutoloaderClass $psr4ClassLoader */
    global $psr4ClassLoader;

    if ($composerClassLoaders) {
        $composerClassLoaders[0]->addPsr4("Handlebars\\", __DIR__ . '/php/Handlebars');
        $composerClassLoaders[0]->setClassMapAuthoritative(false);
    } elseif ($psr4ClassLoader) {
        $psr4ClassLoader->addNamespace("Handlebars", __DIR__ . '/php/Handlebars');
    } elseif (file_exists(__DIR__ . '/php/Handlebars/Autoloader.php')) {
        require_once __DIR__ . '/php/Handlebars/Autoloader.php';
        \Handlebars\Autoloader::register();
    } else {
        return;
    }
}

//TODO: Should we avoid loading this class or check existence!?
//if(!class_exists(\Handlebars\Handlebars::class)) {
//    return;
//}

//The Bitrix-specific code follows
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    return;
}

if(!function_exists('showHandlebarsTemplate')) {
    function showHandlebarsTemplate(
        $templateFile,
        &$arResult,
        &$arParams,
        $arLangMessages,
        $templateFolder,
        $parentTemplateFolder,
        \CBitrixComponentTemplate $template
    )
    {
        static $handlebarsTemplateEngine = null;

        if($handlebarsTemplateEngine === null) {
            $handlebarsTemplateEngine = new \Gsv\Util\Templates\HandlebarsTemplateEngine();
        }

        $handlebarsTemplateEngine->displayFileInDocRoot($templateFile, array(
            'arResult' => $arResult,
            'arParams' => $arParams,
            'arLangMessages' => $arLangMessages,
            'templateFile' => $templateFile,
            'templateFolder' => $templateFolder,
            'parentTemplateFolder' => $parentTemplateFolder,
        ));;

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

$arCustomTemplateEngines['handlebars'] = [
    'templateExt' => ['handlebars', 'hbs'],
    'function'    => 'showHandlebarsTemplate',
];
