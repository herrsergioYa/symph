<?php

namespace Gsv\Agents;

class AdjustCsvColorAgent extends \Gsv\Bitrix\Agent
{
    public const DEBUG = false;

    //We DO initialize them at runtime!
    public const PRODUCT_IBLOCK_ID = CATALOG_IBLOCK_ID;
    //public const OFFER_IBLOCK_ID = CATALOG_OFFERS_IBLOCK_ID;
    //public const COLOR_IBLOCK_ID = CATALOG_COLOR_IBLOCK_ID;

    /** @var array $arData */
    protected $arModels = null;

    /** @var array $arData */
    protected $arData = null;

    /** @var array $arColors */
    protected $arColors = null;

    /** @var bool $infinite */
    protected $infinite = true;

    /** @var int $offset */
    protected $offset = 0;

    /** @var int $limit */
    protected $limit = 100;

    public function getModulesToLoad(&$arModules)
    {
        parent::getModulesToLoad($arModules);
        $arModules[] = 'iblock';
    }

    public function execute(...$params)
    {
        if($params) {
            $this->offset = intval($params[0]);
        } else {
            $this->offset = 0;
        }

        if(isset($params[1])) {
            $this->infinite = !!($params[1]);
        } else {
            $this->infinite = false;
        }

        if(isset($params[2])) {
            $this->limit = intval($params[2]);
        } else {
            $this->limit = 10;
        }

        $this->arModels = $this->readModels($this->offset, $this->limit);

        $this->arData = $this->readCatalog($this->arModels);

        //if($this->arModels) {
        if(count($this->arModels) >= $this->limit) {
            $this->offset += count($this->arModels);
        } else {
            $this->offset = null;
        }

        $arGroups = $this->groupCatalog($this->arData);

        foreach ($arGroups as $group => $arGroup) {
            $arItemIds = [];
            if($group != '') {
                foreach ($arGroup as $arItem) {
                    $arItemIds[] = $arItem['ID'];
                }
            }
            foreach ($arGroup as &$arItem) {
                $arOldItemIds = $arItem['PROPERTIES']['COLORS']['VALUE'] ?: [];
                if(array_diff($arOldItemIds, $arItemIds) || array_diff($arItemIds, $arOldItemIds)) {
                    \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [
                        'COLORS' => $arItemIds,
                    ]);
                }
            }
            unset($arItem);
        }

        if($this->offset !== null) {
            return [$this->offset, $this->infinite, $this->limit];
        } else if($this->infinite) {
            return [0, $this->infinite, $this->limit];
        } else {
            return false;
        }
    }

    public function readModels($offset, $limit = 100)
    {
        $arResult = [false]; // We should check the empty model too

        $dbModels = \CIBlockElement::GetList(
            [
                'PROPERTY_NAME_MODEL' => 'ASC',
            ],
            [
                'IBLOCK_ID' => static::PRODUCT_IBLOCK_ID,
                '>ID' => $offset,
            ],
            ['PROPERTY_NAME_MODEL'],
            false,
            ['PROPERTY_NAME_MODEL']
        );

        while($arItem = $dbModels->Fetch()) {
            $arResult[] = $arItem['PROPERTY_NAME_MODEL_VALUE'];
        }

        $arResult = array_slice($arResult, $offset, $limit);

        return $arResult;
    }

    public function readCatalog($arModels)
    {
        $arResult = [];

        if($arModels) {
            $dbProducts = \CIBlockElement::GetList(
                [
                    'PROPERTY_NAME_MODEL' => 'ASC',
                ],
                [
                    'IBLOCK_ID' => static::PRODUCT_IBLOCK_ID,
                    'PROPERTY_NAME_MODEL' => $arModels,
                ],
                false,
                false,
                ['ID', 'IBLOCK_ID']
            );

            while ($arItem = $dbProducts->Fetch()) {
                $arItem['PROPERTIES'] = [];
                $arResult[$arItem['ID']] = $arItem;
            }

            if ($arResult) {
                \CIBlockElement::GetPropertyValuesArray(
                    $arResult,
                    static::PRODUCT_IBLOCK_ID,
                    ['ID' => array_keys($arResult)],
                    ['CODE' => ['NAME_MODEL', 'COLORS']]
                );
            }
        }

        return $arResult;
    }

    public function groupCatalog($arCatalog)
    {
        $arResult = [];

        foreach ($arCatalog as $arItem) {
            if($arItem['PROPERTIES']['NAME_MODEL']['VALUE']) {
                $arResult[$arItem['PROPERTIES']['NAME_MODEL']['VALUE']][] = $arItem;
            } else {
                $arResult[''][] = $arItem;
            }
        }

        return $arResult;
    }
}