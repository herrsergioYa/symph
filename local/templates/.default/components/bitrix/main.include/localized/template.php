<? if (!defined ("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if($arResult["FILE"] == '') {
    return;
}

$FILE = $arResult['FILE'];
if(SITE_ID != 'ru') {
    if(substr($FILE, -4) == '.php') {
        $FILE = substr($FILE, 0, -4) . '_' . SITE_ID . '.php';
        if(file_exists($FILE)) {
            $arResult['FILE'] = $FILE;
        }
    }
}

(function() use($arResult) {
    include $arResult['FILE'];
})();
