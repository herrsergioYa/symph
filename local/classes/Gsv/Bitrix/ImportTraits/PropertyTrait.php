<?php

namespace Gsv\Bitrix\ImportTraits;

trait PropertyTrait
{
    use CommonTrait;

    /**
     * @var array $arEnum - Enum values (global)
     */
    protected $arEnum = [];

    /**
     * @var array $arEnumXmlId - Forced Enum Properties' XML_ID
     */
    protected $arEnumXmlId = [];

    /** @var \CIBlockProperty $obProperty */
    //protected $obProperty = null;

    /** @var \CIBlockPropertyEnum $obEnum */
    protected $obEnum = null;

    /**
     * @var array $arIblocks - Link IBlockElements
     */
    protected $arIblocks = [];

    protected function initProperties($IBLOCK_ID, $MAPPING)
    {
        $arProps = static::getProperties($IBLOCK_ID, static::getPropertyCodes($MAPPING));
        $this->fillEnumProperties($arProps);
        $this->fillIblockProperties($arProps);
        return $arProps;
    }

    protected static function getProperties($IBLOCK_ID, $propCodes)
    {
        $arResult = [];
        foreach ($propCodes as $propCode) {
            $arResult[$propCode] = static::getProperty($IBLOCK_ID, $propCode);
        }
        return $arResult;
    }

    protected static function getProperty($IBLOCK_ID, $propCode)
    {
        $arFilter = ['IBLOCK_ID' => $IBLOCK_ID];
        $propId = intval($propCode);
        if($propId <= 0) {
            $arFilter['CODE'] = $propCode;
        } else {
            $arFilter['ID'] = $propId;
        }
        $dbProp = \CIBlockProperty::GetList(
            [],
            $arFilter
        );
        if($arProp = $dbProp->Fetch()) {
            return $arProp;
        }
        return false;
    }

    protected function fillEnumProperties($properties)
    {
        foreach ($properties as $property) {
            if($property['PROPERTY_TYPE'] == 'L') {
                $this->arEnum[$property['ID']] = $this->fillEnumProperty($property);
            }
        }
    }

    protected function fillEnumProperty($property)
    {
        if(empty($property) || $property['PROPERTY_TYPE'] != 'L') {
            return false;
        }

        $arItems = [];
        $dbEnum = \CIBlockPropertyEnum::GetList(
            [],
            [
                'IBLOCK_ID' => $property['IBLOCK_ID'],
                'PROPERTY_ID' => $property['ID'],
            ]
        );
        while($arEnum = $dbEnum->Fetch()) {
            $name = static::clearEnumValue($arEnum['VALUE']);
            $arItems[$name] = $arEnum;
        }
        return $arItems;
    }

    protected function findEnumProperty($value, $arProperty, $arItem)
    {
        $arEnum = $this->arEnum[$arProperty['ID']];
        if(!is_array($arEnum)) {
            return false;
        }

        if(empty($value)) {
            return false;
        }

        $val = static::clearEnumValue($value);

        if(empty($val)) {
            return false;
        }

        if(array_key_exists($val, $arEnum)) {
            return $arEnum[$val]['ID'];
        } else if($this->canCreateEnum($arProperty)) {
            if($this->obEnum === null) {
                $this->obEnum = new \CIBlockPropertyEnum();
            }

            $arFields = [
                'PROPERTY_ID' => $arProperty['ID'],
                'VALUE' => trim($value),
            ];

            $this->adjustEnumPropertyFields($arFields, $value, $arProperty, $arItem);

            $ID = $this->obEnum->Add($arFields);

            $arEnum[$val] = [
                    'ID' => $ID,
                ] + $arFields;

            $this->arEnum[$arProperty['ID']] = $arEnum;
            return $arEnum[$val]['ID'];
        } else {
            return false;
        }
    }

    protected function canCreateEnum($arProperty)
    {
        return true;
    }

    protected function adjustEnumPropertyFields(&$arFields, $value, $arProperty, &$arItem)
    {
        if(isset($this->arEnumXmlId[$arProperty['ID']][$value])) {
            $arFields['XML_ID'] = $this->arEnumXmlId[$arProperty['ID']][$value];
        }
    }

    protected function fillIblockProperties($properties)
    {
        foreach ($properties as $property) {
            if($property['PROPERTY_TYPE'] == 'E') {
                if(empty($property['LINK_IBLOCK_ID'])) {
                    $property['LINK_IBLOCK_ID'] = $this->fixLinkIblock($property);
                    if(empty($property['LINK_IBLOCK_ID'])) {
                        continue;
                    }
                }
                $IBLOCK_ID = intval($property['LINK_IBLOCK_ID']);
                if(array_key_exists($IBLOCK_ID, $this->arIblocks)) {
                    continue;
                }
                if($property['CODE'] == 'CML2_LINK') {
                    continue;
                }
                $this->arIblocks[$IBLOCK_ID] = $this->fillIblockProperty($property);
            }
        }
    }

    protected function fillIblockProperty($property)
    {
        if(empty($property)  || $property['PROPERTY_TYPE'] != 'E') {
            return false;
        }
        if(empty($property['LINK_IBLOCK_ID'])) {
            $property['LINK_IBLOCK_ID'] = $this->fixLinkIblock($property);
            if(empty($property['LINK_IBLOCK_ID'])) {
                return [];
            }
        }

        $arItems = [];
        $dbElem = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $property['LINK_IBLOCK_ID'],
            ],
            false,
            false,
            ['ID', 'NAME']
        );
        while($arElem = $dbElem->Fetch()) {
            $name = static::clearEnumValue($arElem['NAME']);
            //$id = intval($arElem['ID']);
            $arItems[$name] = $arElem;
        }
        return $arItems;
    }

    protected function fixLinkIblock($property)
    {
        return false;
    }

    protected function findIblockProperty($value, $arProperty, &$arItem)
    {
        $IBLOCK_ID = intval($arProperty['LINK_IBLOCK_ID']);
        if($IBLOCK_ID <= 0) {
            $IBLOCK_ID = $this->fixLinkIblock($arProperty);
            if(empty($IBLOCK_ID)) {
                return false;
            }
        }

        $arIblock = $this->arIblocks[$IBLOCK_ID];
        if(!is_array($arIblock)) {
            return false;
        }

        if(empty($value)) {
            return false;
        }

        $val = static::clearEnumValue($value);

        if(empty($val)) {
            return false;
        }

        if(array_key_exists($val, $arIblock)) {
            return $arIblock[$val]['ID'];
        } else if($this->canCreateIblockElement($arProperty)) {

            $arFields = [
                'IBLOCK_ID' => $IBLOCK_ID,
                'NAME' => trim($value),
            ];

            $this->adjustIblockPropertyFields($arFields, $value, $arProperty, $arItem);

            $this->adjustNewElement($arFields);

            $ID = $this->getIblockElement()->Add($arFields);

            $arIblock[$val] = [
                    'ID' => $ID,
                ] + $arFields;

            $this->arIblocks[$IBLOCK_ID] = $arIblock;

            return $arIblock[$val]['ID'];
        } else {
            return false;
        }
    }

    protected function canCreateIblockElement($arProperty)
    {
        return true;
    }

    protected function adjustIblockPropertyFields(&$arFields, $value, $arProperty, &$arItem)
    {
        if($arProperty['CODE'] == 'COLOR' && $arItem['EXTRA']['COLOR_CODE']) {
            $arFields['PROPERTY_VALUES']['COLOR_CODE'] = $arItem['EXTRA']['COLOR_CODE'];
        }
    }

    //TODO: Add HLBL-properties...

    protected function applyProperties(&$arItems, $arProperties)
    {
        foreach ($arItems as &$arItem) {
            if(static::FILE_HASH_COMPARE) {
                foreach ($arItem as $fieldCode => &$value) {
                    if (substr($fieldCode, -8) == '_PICTURE') {
                        if ($value) {
                            $value = static::makeFileArray($value);
                        } else {
                            $value = false;
                        }
                    }
                }
                unset($value);
            } //else it isn't needed for now
            foreach ($arItem['PROPERTIES'] as $code => &$value) {
                if($code == 'CML2_LINK') {
                    continue;
                }
                $arProperty = $arProperties[$code];
                $value = $this->applyProperty($value, $arProperty, $arItem);
            }
            unset($value);
        }
        unset($arItem);
    }

    protected function applyProperty($value, $arProperty, &$arItem) {

        $value = $this->unifyProp($value, $arProperty);

        foreach ($value as $i => &$val) {
            if($arProperty['PROPERTY_TYPE'] == 'L') {
                $val = $this->findEnumProperty($val, $arProperty, $arItem);
            } else if($arProperty['PROPERTY_TYPE'] == 'E') {
                $val = $this->findIblockProperty($val, $arProperty, $arItem);
            } else if($arProperty['PROPERTY_TYPE'] == 'N') {

            } else if($arProperty['PROPERTY_TYPE'] == 'S' && $arProperty['USER_TYPE'] == 'HTML') {
                if(!is_array($val)) {
                    $val = [
                        'TYPE' => 'TEXT',
                        'TEXT' => $val,
                    ];
                }
            } else if($arProperty['PROPERTY_TYPE'] == 'S') {

            } else if($arProperty['PROPERTY_TYPE'] == 'F') {
                if(static::FILE_HASH_COMPARE) {
                    if ($val) {
                        $val = static::makeFileArray($val);
                    } else {
                        $val = false;
                    }
                }
            }

            if(empty($val)) {
                unset($value[$i]);
            }
        }
        unset($val);

        $value = $this->deunifyProp($value, $arProperty);

        return $value;
    }

    protected function fixProperties($arProperties, $arProps)
    {
        if(is_array($arProps)) {
            foreach ($arProperties as $code => &$value) {
                $arProperty = $arProps[$code];
                if ($arProperty['PROPERTY_TYPE'] == 'F') {
                    if(!static::FILE_HASH_COMPARE) {//Wasn't done already
                        $value = $this->unifyProp($value, $arProperty);
                        foreach ($value as $k => &$val) {
                            if($val) {
                                $val = static::makeFileArray($val);
                            }
                            if(!$val) {
                                unset($value[$k]);
                            }
                            /*if(is_string(\CFile::CheckImageFile($val))) {
                                unset($value[$k]);
                            }*/
                        }
                        unset($val);
                        $value = $this->deunifyProp($value, $arProperty);
                    }
                    if (empty($value)) {
                        //Not necessary for SetPropertyValuesEx() but still...
                        $value = ['del' => 'Y'];
                    }
                } else {
                    $value = $this->unifyProp($value, $arProperty);
                    foreach ($value as $k => &$val) {
                        if(!$val) {
                            unset($value[$k]);
                            continue;
                        }
                        if(is_array($val)) {
                            if(!array_key_exists('VALUE', $val)) {
                                $val = array("VALUE"=>$val,"DESCRIPTION"=>"");
                            }
                        }
                    }
                    unset($val);
                    $value = $this->deunifyProp($value, $arProperty);
                }
            }
            unset($value);
        } else {
            foreach ($arProperties as $code => &$value) {;
                if (substr($code, -8) == '_PICTURE') {
                    if(!static::FILE_HASH_COMPARE) {//Wasn't done already
                        if($value) {
                            $value = static::makeFileArray($value);
                        }
                    }
                    if (empty($value)) {
                        $value = ['del' => 'Y'];
                    }
                }
            }
            unset($value);
        }
        return $arProperties;
    }

    public static function asProp($target)
    {
        $pos = strpos($target, 'PROPERTY_');
        if($pos === 0) {
            return substr($target, strlen('PROPERTY_'));
        } else {
            return false;
        }
    }

    protected static function getPropertyCodes($arMapping)
    {
        $arResult = [];
        foreach ($arMapping as $key => $arTargets) {
            foreach ($arTargets as $target) {
                if($prop = static::asProp($target)) {
                    $arResult[] = $prop;
                }
            }
        }
        return array_unique($arResult);
    }
}