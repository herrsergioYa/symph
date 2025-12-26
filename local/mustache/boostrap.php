<?php

//TODO: It can be included by Composer already so... But I cannot check it or it may go to the missing classes.
if(!class_exists(\Mustache_Engine::class, false)
    && is_dir(__DIR__ . '/php') && is_dir(__DIR__ . '/php/Mustache')
    ) {

    /** @var \Composer\Autoload\ClassLoader[] $composerClassLoader */
    global $composerClassLoaders;
    /** @var ?\Psr0AutoloaderClass $psr0ClassLoader */
    global $psr0ClassLoader;

    if ($composerClassLoaders) {
        $composerClassLoaders[0]->add("Mustache", __DIR__ . '/php');
        $composerClassLoaders[0]->setClassMapAuthoritative(false);
    } elseif ($psr0ClassLoader) {
        $psr0ClassLoader->add("Mustache", __DIR__ . '/php');
    } elseif (file_exists(__DIR__ . '/php/Mustache/Autoloader.php')) {
        require_once __DIR__ . '/php/Mustache/Autoloader.php';
        \Mustache_Autoloader::register();
    } else {
        return;
    }
}

//TODO: Should we avoid loading this class or check existence!?
//if(!class_exists(\Mustache_Engine::class)) {
//    return;
//}

//The Bitrix-specific code follows
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    return;
}

if(!function_exists('showMustacheTemplate')) {
    function showMustacheTemplate(
        $templateFile,
        &$arResult,
        &$arParams,
        $arLangMessages,
        $templateFolder,
        $parentTemplateFolder,
        \CBitrixComponentTemplate $template
    )
    {
        static $mustacheTemplateEngine = null;

        if($mustacheTemplateEngine === null) {
            $mustacheTemplateEngine = new \Gsv\Util\Templates\MustacheTemplateEngine();
        }

        $mustacheTemplateEngine->displayFileInDocRoot($templateFile, array(
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

$arCustomTemplateEngines['mustache'] = [
    'templateExt' => ['mustache'],
    'function'    => 'showMustacheTemplate',
];
