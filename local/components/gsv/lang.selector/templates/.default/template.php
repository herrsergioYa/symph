<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>

<div>
    <?foreach ($arResult['LANG'] as $arLang):?>
        <a href="<?= $arLang['URL'] ?>">
            <img
                src="<?= $arLang['ICO'] ?>"
                width="<?= $arLang['WIDTH'] ?>"
                alt="<?= $arLang['CODE'] ?>">
        </a>
    <?endforeach;?>
</div>
