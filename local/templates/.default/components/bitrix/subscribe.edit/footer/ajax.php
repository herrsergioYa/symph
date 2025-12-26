<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
$APPLICATION->IncludeComponent(
    "bitrix:subscribe.edit",
    "footer",
    Array(
        "SET_TITLE" => "N",
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => 86400,

        'IS_AJAX' => 'Y',
    )
);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");