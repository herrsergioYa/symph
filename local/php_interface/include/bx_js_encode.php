<?php

//There are some dirty HACKs in catalog.element involving \CUtil::PhpToJSObject()
//so proceed with caution!

if(!function_exists('bx_js_encode')) {
    //https://mrcappuccino.ru/blog/post/zastavlyaem-cutilphptojsobject-vozvrashchat-validnyj-json
    function bx_js_encode($arData, $bWS, $bSkipTilda, $bExtType)
    {
        // Два варианта на выбор:
        return \Bitrix\Main\Web\Json::encode($arData);
        // ИЛИ
        return json_encode($arData);
    }
}