<?php

namespace Gsv\Bitrix\ImportTraits;

trait IblockTrait
{
    use CommonTrait;

    //TODO: Should it be here?
    /** @var \CIBlockElement $obElement */
    protected $obElement = null;

    /** @var \CIBlock $obIblock */
    protected $obIblock = null;

    /** @var array $arIblockCache */
    protected $arIblockCache = [];

    protected function readIblockCatalog($IBLOCK_ID, $FIELDS, $PROPERTIES, $xmlIds, $xmlIdField = 'XML_ID')
    {
        $arSelect = ['ID', 'XML_ID', 'ACTIVE', 'CODE', 'NAME'];
        $arPropCodes = [];
        if(!$xmlIds) {
            return [];
        }

        $arSelect = array_merge($arSelect, $FIELDS);
        if(!in_array($xmlIdField, $arSelect)) {
            $arSelect[] = $xmlIdField;
        }
        $arPropCodes = array_merge($arPropCodes, $PROPERTIES);
        $needsAllSections = in_array('IBLOCK_SECTION', $arSelect);

        $arItems = [];
        $dbItems = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
                "=$xmlIdField" => $xmlIds,
            ],
            false,
            false,
            $arSelect
        );
        while($arItem = $dbItems->Fetch()) {
            $arItem['PROPERTIES'] = [];
            if(static::FILE_HASH_COMPARE) {
                foreach ($arItem as $field => $value) {
                    if(substr($field, -8) == '_PICTURE') {
                        if ($value) {
                            $arItem[$field] = \CFile::GetFileArray($value);
                        } else {
                            $arItem[$field] = false;
                        }
                    }
                }
            }
            if($needsAllSections) {
                $arItem['IBLOCK_SECTION'] = [];
            }
            $arItems[$arItem['ID']] = $arItem;
        }

        if($arItems) {

            if($needsAllSections) {
                $dbSections = \CIBlockElement::GetElementGroups(
                    array_keys($arItems),
                    true,
                    ['ID', 'IBLOCK_ELEMENT_ID']
                );
                while($arSection = $dbSections->Fetch()) {
                    $arItems[$arSection['IBLOCK_ELEMENT_ID']]['IBLOCK_SECTION'][] = $arSection['ID'];
                }
            }

            \CIBlockElement::GetPropertyValuesArray($arItems, $IBLOCK_ID,
                ['ID' => array_keys($arItems)],
                ['CODE' => $arPropCodes],
                ['GET_RAW_DATA' => 'Y']
            );

            if(static::FILE_HASH_COMPARE) {
                foreach ($arItems as &$arItem) {
                    foreach ($arItem['PROPERTIES'] as &$arProp) {
                        if ($arProp['PROPERTY_TYPE'] == 'F') {
                            $arProp['VALUE'] = $this->unifyProp($arProp['VALUE'], $arProp);
                            foreach ($arProp['VALUE'] as $k => &$val) {
                                if (empty($val)) {
                                    unset($arProp['VALUE'][$k]);
                                    continue;
                                }
                                $val = \CFile::GetFileArray($val);
                                if (empty($val)) {
                                    unset($arProp['VALUE'][$k]);
                                }
                            }
                            unset($val);
                            $arProp['VALUE'] = $this->deunifyProp($arProp['VALUE'], $arProp);
                        }
                    }
                    unset($arProp);
                }
                unset($arItem);
            }
        }

        $arResult = [];

        if(strpos($xmlIdField, 'PROPERTY_') !== false) {
            $xmlIdField .= '_VALUE';
        }

        foreach ($arItems as $arItem) {
            $arResult[$arItem[$xmlIdField]] = $arItem;
        }

        return $arResult;
    }

    protected function adjustNewElement(&$arElement)
    {
        //$arElement['ACTIVE'] = 'N';
    }

    protected function fixFields($arProperties)
    {
        foreach ($arProperties as $code => &$value) {
            if (substr($code, -5) == '_TEXT') {
                if(is_array($arProperties[$code])) {
                    $arProperties["${code}_TYPE"] = strtolower($value['TYPE']);
                    $value= $value['TEXT'];
                } else if(!isset($arProperties["${code}_TYPE"])) {
                    $arProperties["${code}_TYPE"] = 'text';
                }
            }
        }
        unset($value);
        return $arProperties;
    }

    protected function getIblockElement()
    {
        if($this->obElement === null)
            $this->obElement = new \CIBlockElement();
        return $this->obElement;
    }

    protected function getIblock($IBLOCK_ID = null)
    {
        if($IBLOCK_ID === null) {
            if($this->obIblock === null) {
                $this->obIblock = new \CIBlock();
            }
            return $this->obIblock;
        }
        if(!isset($this->arIblockCache[$IBLOCK_ID])) {
            $dbIblock = \CIBlock::GetByID($IBLOCK_ID);
            $this->arIblockCache[$IBLOCK_ID] = $dbIblock->Fetch();
        }
        return $this->arIblockCache[$IBLOCK_ID];
    }
}