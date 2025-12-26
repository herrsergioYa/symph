<?php

namespace Gsv\Agents;

use Gsv\Helpers\SyncRestApi;

class AdjustColorAgent extends \Gsv\Bitrix\Agent
{
    public const DEBUG = false;

    //We DO initialize them at runtime!
    public const PRODUCT_IBLOCK_ID = PRODUCT_IBLOCK_ID;
    //public const OFFER_IBLOCK_ID = OFFER_IBLOCK_ID;
    public const COLOR_IBLOCK_ID = COLOR_IBLOCK_ID;

    /** @var array $arData */
    protected $arData = null;

    /** @var array $arColors */
    protected $arColors = null;

//    /** @var \CIBlock $obIblock */
//    protected $obIblock = null;
//
//    /** @var \CIBlockProperty $obProperty */
//    protected $obProperty = null;
//
//    /** @var \CIBlockPropertyEnum $obEnum */
//    protected $obEnum = null;
//
//    /** @var \CIBlockElement $obElement */
//    protected $obElement = null;

    /** @var bool $infinite */
    protected $infinite = true;

    /** @var int $lastId */
    protected $lastId = 0;

    /** @var int $limit */
    protected $limit = 100;

    public function getModulesToLoad(&$arModules)
    {
        parent::getModulesToLoad($arModules);
        $arModules[] = 'iblock';
        //$arModules[] = 'catalog';
    }

    public function execute(...$params)
    {
        if($params) {
            $this->lastId = intval($params[0]);
        } else {
            $this->lastId = 0;
        }

        if(isset($params[1])) {
            $this->infinite = !!($params[1]);
        } else {
            $this->infinite = true;
        }

        if(isset($params[2])) {
            $this->limit = intval($params[2]);
        } else {
            $this->limit = 100;
        }

        $this->arData = $this->readCatalog($this->lastId, $this->limit);

        $this->arColors = $this->readColors($this->arData);

        $this->lastId = null;

        foreach ($this->arData as $arItem) {
            if($arItem['PROPERTIES']['COLOR']['VALUE']) {
                $currentColor = (int)$arItem['PROPERTIES']['COLOR']['VALUE'];
            } else {
                $currentColor = false;
            }

            $color = $arItem['PROPERTIES']['TSVET']['VALUE_ENUM_ID'];
            if($color) {
                $newColor = $this->arColors[$arItem['PROPERTIES']['TSVET']['VALUE_ENUM_ID']] ?: false;
            } else {
                $newColor = false;
            }
            if($newColor != $currentColor) {
                \CIBlockElement::SetPropertyValuesEx(
                    $arItem['ID'],
                    $arItem['IBLOCK_ID'],
                    ['COLOR' => $newColor]
                );
            }

            $this->lastId = (int)$arItem['ID'];
        }

        if($this->lastId !== null) {
            return [$this->lastId, $this->infinite, $this->limit];
        } else if($this->infinite) {
            return [0, $this->infinite, $this->limit];
        } else {
            return false;
        }
    }

    public function readCatalog($lastId, $limit = 100)
    {
        $arResult = [];

        $dbProducts = \CIBlockElement::GetList(
            [
                'ID' => 'ASC',
            ],
            [
                'IBLOCK_ID' => static::PRODUCT_IBLOCK_ID,
                '>ID' => $lastId,
            ],
            false,
            ['nTopCount' => $limit],
            ['ID', 'IBLOCK_ID']
        );

        while($arItem = $dbProducts->Fetch()) {
            $arItem['PROPERTIES'] = [];
            $arResult[$arItem['ID']] = $arItem;
        }

        if($arResult) {
            \CIBlockElement::GetPropertyValuesArray(
                $arResult,
                static::PRODUCT_IBLOCK_ID,
                ['ID' => array_keys($arResult)],
                ['CODE' => ['TSVET', 'COLOR']]
            );
        }

        return $arResult;
    }

    protected function readColors($arData)
    {
        $arColors = [];

        foreach ($arData as $arItem) {
            if($arItem['PROPERTIES']['TSVET']['VALUE_ENUM_ID']) {
                foreach ((array)$arItem['PROPERTIES']['TSVET']['VALUE_ENUM_ID'] as $value) {
                    $arColors[] = (int)$value;
                }
            }
        }

        if($arColors) {
            $arColors = array_values(array_unique($arColors));
            $dbColors = \CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => static::COLOR_IBLOCK_ID,
                    'PROPERTY_TSVET' => $arColors,
                ],
                false,
                false,
                ['ID', 'PROPERTY_TSVET']
            );
            $arColors = [];
            while($arColor = $dbColors->Fetch()) {
                foreach ((array)$arColor['PROPERTY_TSVET_VALUE'] as $color) {
                    $arColors[$color] = $arColor['ID'];
                }
            }
        }

        return $arColors;
    }
}