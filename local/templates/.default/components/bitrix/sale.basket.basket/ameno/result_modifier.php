<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$offers = [];
foreach ($arResult["ITEMS"]["AnDelCanBuy"] as $arItem) {
    $offers[$arItem["PRODUCT_ID"]] = [];
}
$arResult["INFO"] = [];
if (!empty($offers)) {
    //$iblockObj = new CIBlockElement();
    $dbObj = \CIBlockElement::GetList(
        array(),
        array(
            "IBLOCK_ID" => CATALOG_OFFERS_IBLOCK_ID,
            "ACTIVE" => "Y",
            "ID" => array_keys($offers)
        ),
        false,
        false,
        array(
            "IBLOCK_ID",
            "ID",
            "CODE",
            "PROPERTY_CML2_LINK"
        )
    );

    $elements = [];
    $elementToOffer = [];
    while ($arOffer = $dbObj->GetNext()) {
        $arOffer['PROPERTIES'] = [];
        $offers[$arOffer['ID']] = $arOffer;
        $elements[$arOffer["PROPERTY_CML2_LINK_VALUE"]] = [];
        $elementToOffer[$arOffer["PROPERTY_CML2_LINK_VALUE"]][] = $arOffer["ID"];
    }

    \CIBlockElement::GetPropertyValuesArray(
        $offers,
        CATALOG_OFFERS_IBLOCK_ID,
        ['ID' => array_keys($offers)],
    );

    if (!empty($elements)) {
        $dbObj = \CIBlockElement::GetList(
            array(),
            array(
                "IBLOCK_ID" => CATALOG_IBLOCK_ID,
                "ACTIVE" => "Y",
                "ID" => array_keys($elements)
            ),
            false,
            false,
            array(
                "IBLOCK_ID",
                "ID",
                "NAME",
                "CODE",
                "DETAIL_PAGE_URL",
            )
        );

        while ($arElement = $dbObj->GetNext()) {
            $arElement['PROPERTIES'] = [];
            $elements[$arElement['ID']] = $arElement;
            /*$arElement["DETAIL_PAGE_URL"] = substr($arElement["DETAIL_PAGE_URL"], strlen(SITE_DIR));
            $arElement["DETAIL_PAGE_URL"] = explode("/", $arElement["DETAIL_PAGE_URL"]);
            $arElement["DETAIL_PAGE_URL"] = SITE_DIR.$arElement["DETAIL_PAGE_URL"][0]."/".$arElement["CODE"]."/";*/
        }

        \CIBlockElement::GetPropertyValuesArray(
            $elements,
            CATALOG_IBLOCK_ID,
            ['ID' => array_keys($elements)],
        );

        foreach ($elements as $elementId => $arElement) {
            $arElement['OFFERS'] = [];
            foreach ($elementToOffer[$arElement["ID"]] as $offerId) {
                $arElement['OFFERS'][] = $offers[$offerId];
            }
            $arResult["INFO"][$elementId] = $arElement;
        }

        \Gsv\Helpers\Ameno::modifyProducts($elements, true);

        foreach ($elements as $elementId => $arElement) {
            foreach ($elementToOffer[$arElement["ID"]] as $offerId) {
                $arOffer = $offers[$offerId];
                $arOffer['SKU'] = $arElement;
                $arResult["INFO"][$offerId] = $arOffer;
            }
        }
    }
}

$this->__component->SetResultCacheKeys(array("INFO"));