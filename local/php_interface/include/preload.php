<?php

if (!function_exists('is_bitrix_included')) {
    function is_bitrix_included(): bool
    {
        return defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true;
    }
}

if (!function_exists('get_iblock_obj')) {
    function get_iblock_obj($apiCode)
    {
        if(!is_bitrix_included()) {
            return false;
        }

        static $iblocks = [];

        if (array_key_exists($apiCode, $iblocks)) {
            return $iblocks[$apiCode];
        }

        $iBlock = false;

        if(\Bitrix\Main\Loader::includeModule('iblock')) {
            $iBlock = \Bitrix\Iblock\IblockTable::getList([
                'filter' => ['=API_CODE' => $apiCode],
                'select' => ['*'],
                'limit' => 1,
            ])->fetchObject();

            if(empty($iBlock)) { //Fallback
                $iBlock = \Bitrix\Iblock\IblockTable::getList([
                    'filter' => ['=CODE' => $apiCode],
                    'select' => ['*'],
                    'limit' => 1,
                ])->fetchObject();
            }

            $iBlock = $iBlock ?: false;
        }

        $iblocks[$apiCode] = $iBlock;

        return $iBlock;
    }
}

if (!function_exists('get_iblock_id')) {
    function get_iblock_id($apiCode) : int
    {
        if(!is_bitrix_included()) {
            return false;
        }

        $iBlock = get_iblock_obj($apiCode);

        return $iBlock ? (int)$iBlock->getId() : 0;
    }
}

//An ID of an HighloadBlock isn't needed most of the time BUT some config may use it.
if(!function_exists('get_hlbl_id')) {
    function get_hlbl_id($hlblName) : int
    {
        if(!is_bitrix_included()) {
            return false;
        }

        $hlblock = get_hlbl_arr($hlblName);

        return $hlblock ? intval($hlblock['ID']) : 0;
    }
}

//An ID of an HighloadBlock isn't needed most of the time BUT some config may use it.
if(!function_exists('get_hlbl_arr')) {
    function get_hlbl_arr($hlblName)
    {
        if(!is_bitrix_included()) {
            return false;
        }

        static $hlblocks = [];

        if (array_key_exists($hlblName, $hlblocks)) {
            return $hlblocks[$hlblName];
        }

        $hlblock = false;

        if (\Bitrix\Main\Loader::includeModule('highloadblock')) {
            $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getRow([
                'filter' => [
                    'NAME' => $hlblName,
                ],
                'select' => [
                    '*',
                ],
            ]);
            if(empty($hlblock)) { //Fallback
                $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getRow([
                    'filter' => [
                        'TABLE_NAME' => $hlblName,
                    ],
                    'select' => [
                        '*',
                    ],
                ]);
                if ($hlblock) {
                    $hlblocks[$hlblock['NAME']] = $hlblock; //Redundant if we didn't use the fallback
                }
            }
            if (!$hlblock) {
                $hlblock = false;
            }
        }

        $hlblocks[$hlblName] = $hlblock;

        return $hlblock;
    }
}
