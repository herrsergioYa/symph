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
$this->setFrameMode(true);?>
<?
$INPUT_ID = trim($arParams["~INPUT_ID"]);
if($INPUT_ID == '')
	$INPUT_ID = "title-search-input";
$INPUT_ID = CUtil::JSEscape($INPUT_ID);

$CONTAINER_ID = trim($arParams["~CONTAINER_ID"]);
if($CONTAINER_ID == '')
	$CONTAINER_ID = "title-search";
$CONTAINER_ID = CUtil::JSEscape($CONTAINER_ID);

if($arParams["SHOW_INPUT"] !== "N"):?>
    <? //$this->SetViewTarget('header-search');?>
    <form class="col search-form" action="<?= SITE_DIR ?>search/" method="post">
        <div class="w-100 pr">
            <input class="w-100 search-input" data-ajax-url="<?= POST_FORM_ACTION_URI?>" type="search"
                   name="q" value="" maxlength="128" placeholder="Поиск" autofocus required
                   id="<?= $arParams['INPUT_ID'] ?>" data-min-query-len="<?= $arParams['MIN_QUERY_LEN'] ?>">
            <button class="pa btn-clear search-clear" type="reset" title="Очистить"></button>
        </div>
        <button class="pr btn-close" type="button" title="Закрыть"></button>
    </form>
    <? ///$this->EndViewTarget();?>
    <? /*$this->SetViewTarget('header-search-mobile');?>
    <? $this->EndViewTarget();*/?>
<?endif?>
<div class="pb-4 search-results" id="<?= $CONTAINER_ID?>">
</div>
<script>
    (function() {
        let fn = function () {
            initSearch('<?= $INPUT_ID?>', '<?= $CONTAINER_ID?>');
        };
        if (document.readyState === "complete" || document.readyState === "interactive") {
            // call on next available tick
            setTimeout(fn, 1);
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    })();
</script>
<? $this->SetViewTarget('footer-scripts');?>
<script src="<?= $templateFolder ?>/script.js"></script>
<? $this->EndViewTarget();?>
