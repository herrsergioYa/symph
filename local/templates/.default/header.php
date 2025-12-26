<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

//TODO: To init.php?
$detect = new \Detection\MobileDetect();
$GLOBALS["IS_MOBILE"] = $detect->isMobile();

define('BITRIX_ACTIVE', $USER->IsAdmin() && $_REQUEST['include_bitrix'] == 'Y');

?>
<!DOCTYPE html>
<html lang="<?= LANGUAGE_ID?>">
	<head>
		<!-- Meta Data -->
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><? $APPLICATION->ShowTitle() ?></title>

		<!-- Favicon -->
		<link rel="shortcut icon" type="image/x-icon" href="<?= SITE_TEMPLATE_PATH ?>/favicon.png">

        <!-- Dependency Styles -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/jquery-ui.min.css" integrity="sha512-8PjjnSP8Bw/WNPxF6wkklW6qlQJdWJc/3w/ZQPvZ/1bjVDkrrSqLe9mfPYrMxtnzsXFPc434+u4FHLnLjXTSsg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/theme.min.css" integrity="sha512-XutDejX3PkIxnMh/xEu11qZ9+jn3lh+SrEnbtXny8dhr7Jk+lBkr2ujwco0Bx4LJ500XibluwyXc0kOJ+oY51Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!--link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/suggestions-jquery@21.12.0/dist/css/suggestions.min.css"-->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css" integrity="sha512-kq3FES+RuuGoBW3a9R2ELYKRywUEQv0wvPTItv3DSGqjpbNtGWVdvT8qwdKkqvPzT93jp8tSF4+oN4IeTEIlQA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <!-- Site Stylesheet -->
        <link rel="stylesheet" href="<?= SITE_TEMPLATE_PATH ?>/style.css" type="text/css">

		<!-- Google Web Fonts -->
		<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">

        <!-- Dependency Scripts used by Bitrix's components -->
        <!-- Can be moved to the footer with CAUTION!! -->
        <script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/jquery-ui.min.js" integrity="sha512-Ww1y9OuQ2kehgVWSD/3nhgfrb424O3802QYP/A5gPXoM4+rRjiKrjHdGxQKrMGQykmsJ/86oGdHszfcVgUr4hA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://unpkg.com/imask.min.js"></script>
        <!--script src="https://cdn.jsdelivr.net/npm/suggestions-jquery@21.12.0/dist/js/jquery.suggestions.min.js"></script-->
        <!--script src="https://cdn.jsdelivr.net/npm/bootstrap-autocomplete@2.3.7/dist/latest/bootstrap-autocomplete.min.js"></script-->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/en.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/ru.js"></script>

        <?if(gsv_is_debug()):?>
            <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
        <?else:?>
            <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
        <?endif;?>

        <!-- JS Polyfills -->
        <script src="https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/whatwg-fetch@3.6.20/dist/fetch.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/core-js-bundle@3.37.1/minified.min.js"></script>

        <?if(!defined('BITRIX_ACTIVE') || BITRIX_ACTIVE):?>
            <? $APPLICATION->ShowHead() ?>
        <?else:?>
            <?$APPLICATION->ShowMeta("description", false, false)?>
            <?$APPLICATION->ShowMeta("keywords", false, false)?>
            <?$APPLICATION->ShowViewContent("canonical");?>
            <script src="/local/js/bx.js"></script>
            <script>
                BX.message(<?= json_encode([
                    'SITE_ID' => SITE_ID,
                    'SITE_DIR' => SITE_DIR,
                    'bitrix_sessid' => '',
                ]) ?>)
            </script>
            <?$APPLICATION->ShowViewContent("head-styles");?>
        <?endif;?>

        <? $asset = \Bitrix\Main\Page\Asset::getInstance(); ?>

    </head>
	
	<body>
        <?if(!defined('BITRIX_ACTIVE') || BITRIX_ACTIVE):?>
            <? $APPLICATION->ShowPanel(); ?>
        <?endif;?>
        <h1><? $APPLICATION->ShowTitle(false) ?></h1>
        <div>
