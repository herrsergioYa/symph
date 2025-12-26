<?php

namespace Gsv\Bitrix\SprintMigration;

trait SaleOrderPropsTrait
{
    //const ORDER_PROP_CODES = [];

    public function ensureFields($__FILE__, $arPersonTypeIds = null, $arSites = null)
    {
        \Bitrix\Main\Loader::requireModule('sale');

        $filename = static::getIncFilename($__FILE__);

        $arProps = [];

        (function(&$arOrderProps) use($filename) {
            include $filename;
        })($arProps);

        $arOrderProps = [];
        foreach ($arProps as $arOrderProp) {
            $arOrderProps[$arOrderProp['CODE']] = $arOrderProp;
        }
        //gsv_dump($arSites);
        if($arPersonTypeIds === null) {
            if($arSites === null) {
                $arSites = static::getSites();
            }
            $arPersonTypeIds = static::getPersonTypes($arSites);
            $arPersonTypeIds = array_merge(...array_values($arPersonTypeIds));
        }

        //foreach ($arPersonTypeIds as $SITE_ID => $ids) {
        if ($arPersonTypeIds) {
//            if($SITE_ID == 'ru') {
//                continue;
//            }
            //gsv_dump(($ids));
            $propGrpId = null;
            $arBadProps = [];
            $arPropsToCreate = array_fill_keys($arPersonTypeIds, $arOrderProps);
            $dbProps = \Bitrix\Sale\Internals\OrderPropsTable::getList([
                'filter' => [
                    'PERSON_TYPE_ID' => $arPersonTypeIds,
                    '=CODE' => array_keys($arOrderProps),
                    '=ACTIVE' => 'Y',
                ],
                'select' => ['*'],
            ]);
            while ($arProp = $dbProps->fetch()) {
                if($arProp['ACTIVE'] != $arOrderProps[$arProp['CODE']]['ACTIVE']
                    || $arProp['TYPE'] != $arOrderProps[$arProp['CODE']]['TYPE']) {
                    $arBadProps[] = $arProp['CODE'];
                }
                unset($arPropsToCreate[$arProp['PERSON_TYPE_ID']][$arProp['CODE']]);
            }
            //gsv_dump($arPropCodes);
            $propGrpId = [];
            foreach ($arPropsToCreate as $personTypeId => $arProps) {
                foreach ($arProps as $code => $arProp) {
                    $arProp['PERSON_TYPE_ID'] = $personTypeId;
                    if ($propGrpId[$personTypeId] === null) {
                        //TODO:: Make at once?
                        $arPropGrp = \Bitrix\Sale\Internals\OrderPropsGroupTable::getRow([
                            'filter' => [
                                'PERSON_TYPE_ID' => $personTypeId,
                            ],
                        ]);
                        $propGrpId[$personTypeId] = $arPropGrp['ID'];
                    }
                    $arProp['PROPS_GROUP_ID'] = $propGrpId;
                    $r = \Bitrix\Sale\Internals\OrderPropsTable::add($arProp);
                    if (!$r->isSuccess()) {
                        //gsv_dump($r, true);
                    }
                }
            }
            if($arBadProps) {
                //gsv_dump($arBadProps, true);
            }
        }
    }

    public static function getSites()
    {
        $dbSites = \CSite::GetList();
        $arSites = [];
        while($arSite = $dbSites->Fetch()) {
            $arSites[] = $arSite['LID'];
        }
        return $arSites;
    }

    public static function readProps($__FILE__, $arPersonTypeIds = 1, $arPropCodes = null)
    {
        \Bitrix\Main\Loader::requireModule('sale');

        //$SITE_ID = 'en';//Admin panel!! We have no site!

        //$arPersonTypeIds = static::getPersonTypes($SITE_ID)[$SITE_ID];

        $arPropCodes = $arPropCodes ?? static::ORDER_PROP_CODES;

        $dbProps = \Bitrix\Sale\Internals\OrderPropsTable::getList([
            'filter' => [
                '=PERSON_TYPE_ID' => $arPersonTypeIds,
                '=CODE' => $arPropCodes,
                '=ACTIVE' => 'Y',
            ],
            'select' => [
                '*',
                //'PROPERTY_GROUP_NAME' => 'PROPERTY_GROUP.NAME'
            ],
        ]);
        $arProps = [];
        while($arProp = $dbProps->fetch()) {
            unset($arProp['ID']);
            unset($arProp['XML_ID']);
            unset($arProp["PROPS_GROUP_ID"]);
            unset($arProp["PERSON_TYPE_ID"]);
            //gsv_dump($arProp);
            $arProps[] = $arProp;
        }

        $name = static::getIncFilename($__FILE__);

        $content = '<?$arOrderProps = ' . var_export($arProps, true) . ';';

        file_put_contents($name,  $content);

        //return var_export($arProps, true);
    }

    public static function getPersonTypes($SITE_ID)
    {
        $dbPersonTypes = \Bitrix\Sale\Internals\PersonTypeTable::getList([
            'filter' => [
                '=LID' => $SITE_ID,
            ],
            'select' => [
                'ID', 'LID',
            ],
        ]);
        $arPersonTypeIds = [];
        while($arPersonType = $dbPersonTypes->fetch()) {
            $arPersonTypeIds[$arPersonType['LID']][] = $arPersonType['ID'];
        }
        //gsv_dump($arPersonTypeIds);
        if(!is_array($SITE_ID)) {
            return $arPersonTypeIds[$SITE_ID];
        }
        return $arPersonTypeIds;
    }

    public static function getIncFilename($__FILE__)
    {
        $name = preg_replace('/\.php$/', ".inc", $__FILE__);
        if($name == $__FILE__) {
            $name .= '.inc';
        }
        return $name;
    }
}