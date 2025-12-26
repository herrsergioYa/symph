<?php

namespace Sprint\Migration;


class VersionAddedFastbuyStoreId extends Version
{
    protected $description = "Каждому используемому типу плательщика необходимо создать свойство заказа FASTBUY_STORE_ID активное, служебное, строковое, немножественное и не входящее в профиль.";

    protected $moduleVersion = "4.6.1";

    public function up()
    {
        $arPersonTypeIds = [1, 2];
        $arOrderProperties = [
            [
                "PERSON_TYPE_ID" => "",
                "NAME" => "Склад быстрого заказа",
                "CODE" => "FASTBUY_STORE_ID",
                "TYPE" => "STRING",
                "REQUIRED" => "N",
                "DEFAULT_VALUE" => "",
                "SORT" => 0,
                "USER_PROPS" => "N",
                "IS_LOCATION" => "N",
                "PROPS_GROUP_ID" => "2",
                "DESCRIPTION" => "",
                "IS_EMAIL" => "N",
                "IS_PROFILE_NAME" => "N",
                "IS_PAYER" => "N",
                "UTIL" => "Y",
                "ENTITY_REGISTRY_TYPE" => "ORDER",
            ],
        ];

        \Bitrix\Main\Loader::requireModule("sale");
        foreach ($arOrderProperties as $arOrderProperty) {
            foreach ($arPersonTypeIds as $arPersonTypeId) {
                $arOrderProperty["PERSON_TYPE_ID"] = $arPersonTypeId;
                $arOrderProperty["PROPS_GROUP_ID"] += $arPersonTypeId;
                \Bitrix\Sale\Internals\OrderPropsTable::add($arOrderProperty);
            }
        }
    }

    public function down()
    {
        //your code ...
    }
}
