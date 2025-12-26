<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$this->createFrame()->begin("Загрузка навигации");
?>

<? if ($arResult["NavPageCount"] > 1): ?>
    <?/* Добавляем data-show-more-container="<?= $arResult['NAV_RESULT']->{'NavNum'} ?>" на контейнер элементов */?>
	<? if ($arResult["NavPageNomer"] + 1 <= $arResult["NavPageCount"]): ?>
		<?
		$plus = $arResult["NavPageNomer"] + 1;
		$url = $arResult["sUrlPathParams"] . "PAGEN_" . $arResult["NavNum"] . "=" . $plus;
		//if($_REQUEST['clear_cache'] == 'Y')
		//    $url .= '&clear_cache=Y';
		
		?>
        <div class="news-all-btn-block" data-show-more-button="<?= $arResult['NavNum'] ?>" data-show-more-url="<?= $url ?>">
            <a href="javascript:void(0);" class="news-all-btn">Смотреть ещё</a>
        </div>
	<? else: ?>
		<div data-show-more-button="<?= $arResult['NavNum'] ?>" class="d-none"></div>
	<? endif ?>

<? endif ?>
