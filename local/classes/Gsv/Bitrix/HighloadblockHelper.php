<?php

namespace Gsv\Bitrix;

class HighloadblockHelper
{
    public static function getUserFields($hlblId)
    {
        static $arUserFields = [];
        if($arUserFields[$hlblId] === null) {
            global $USER_FIELD_MANAGER;
            if(is_numeric($hlblId)) {
                $ufId = 'HLBLOCK_' . $hlblId;
            } else {
                $ufId = $hlblId;
            }
            $arUserFields[$hlblId] = $USER_FIELD_MANAGER->GetUserFields($ufId);
        }
        return $arUserFields[$hlblId];
    }

    public static function getVariants($field, $hlblId = 0)
    {
        static $arVariants = [];
        if(is_numeric($field)) {
            $hlblId = 0;
        } else if(is_numeric($hlblId) && $hlblId <= 0) {
            return false;
        } //else a correct HLBL_ID or literal
        if(!isset($arVariants[$hlblId][$field])) {
            $arVariants[$hlblId][$field] = [];
            if(is_numeric($field)) {
                $arUserFields = [
                    $field => [
                        'ID' => $field,
                    ],
                ];
            } else if($hlblId) {
                $arUserFields = static::getUserFields($hlblId);
            } else {
                $arUserFields = [];
            }
            if($arUserFields[$field]) {
                $dbEnum = \CUserFieldEnum::GetList(
                    [],
                    [
                        'USER_FIELD_ID' => $arUserFields[$field]['ID'],
                    ]
                );
                while($arEnum = $dbEnum->Fetch()) {
                    $arVariants[$hlblId][$field][$arEnum['XML_ID']] = $arEnum;
                }
            }
        }

        return $arVariants[$hlblId][$field];
    }
}