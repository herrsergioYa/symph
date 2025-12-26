<?php

namespace Gsv\Agents;

use \Bitrix\Currency;

class UpdateCurrencyRatesAgent extends \Gsv\Bitrix\Agent
{
    public function getModulesToLoad(&$arModules)
    {
        parent::getModulesToLoad($arModules);

        $arModules[] = 'currency';
    }

    public function execute(...$params)
    {
        $timestamp = intval($params[0]) > 0 ? intval($params[0]) : time();

        $arRates = static::getCBCurrencyRates($timestamp);
        $arRates = static::filterCurrencyRates($arRates);

        static::applyCurrencyRates($arRates, $timestamp);
    }

    public static function getCBCurrencyRates($timestamp = null)
    {
        if($timestamp === null)
            $timestamp = time();

        global $APPLICATION;
        $arRates = [];

        $obHttp = new \CHTTP();
        $obHttp->Query(
            'GET',
            'www.cbr.ru',
            80,
            "/scripts/XML_daily.asp?date_req=".date("d.m.Y", $timestamp),
            false,
            '',
            'N'
        );

        $strQueryText = $obHttp->result;
        if ($strQueryText)
        {
            if (SITE_CHARSET != "windows-1251")
                $strQueryText = $APPLICATION->ConvertCharset($strQueryText, "windows-1251", SITE_CHARSET);

            $strQueryText = preg_replace("#<!DOCTYPE[^>]+?>#i", "", $strQueryText);
            $strQueryText = preg_replace("#<"."\\?XML[^>]+?\\?".">#i", "", $strQueryText);

            $objXML = new \CDataXML();
            $objXML->LoadString($strQueryText);
            $arData = $objXML->GetArray();

            $arRates = array();

            if (!empty($arData) && is_array($arData))
            {
                if (!empty($arData["ValCurs"]) && is_array($arData["ValCurs"]))
                {
                    if (!empty($arData["ValCurs"]["#"]) && is_array($arData["ValCurs"]["#"]))
                    {
                        if (!empty($arData["ValCurs"]["#"]["Valute"]) && is_array($arData["ValCurs"]["#"]["Valute"]))
                        {
                            $arCBVal = $arData["ValCurs"]["#"]["Valute"];
                            foreach($arCBVal as &$arOneCBVal)
                            {
                                //if (in_array($arOneCBVal["#"]["CharCode"][0]["#"], $arParams["arrCURRENCY_FROM"]))
                                {
                                    $arCurrency = array(
                                        "CURRENCY" => $arOneCBVal["#"]["CharCode"][0]["#"],
                                        "RATE_CNT" => intval($arOneCBVal["#"]["Nominal"][0]["#"]),
                                        "RATE" => doubleval(str_replace(",", ".", $arOneCBVal["#"]["Value"][0]["#"])),

                                        'CURRENCY_BASE' => 'RUB',
                                    );

                                    $arRates[] = $arCurrency + array(
                                        "FROM" => \CCurrencyLang::CurrencyFormat($arCurrency["RATE_CNT"], $arCurrency["CURRENCY"], true),
                                        "BASE" => \CCurrencyLang::CurrencyFormat($arCurrency["RATE"], $arCurrency['CURRENCY_BASE'], true),
                                    );
                                }
                            }
                            if (isset($arOneCBVal))
                                unset($arOneCBVal);
                        }
                    }
                }
            }
        }

        return $arRates;
    }

    public static function applyCurrencyRates($arRates, $timestamp = null)
    {
        if($timestamp === null)
            $dateRate = new \Bitrix\Main\Type\Date();
        else
            $dateRate = \Bitrix\Main\Type\Date::createFromTimestamp($timestamp);

        foreach ($arRates as $arRate)
        {
            static::applyRate($arRate, $dateRate);
        }
    }

    public static function applyRate(array $arRate, \Bitrix\Main\Type\Date $dateRate)
    {
        $iterator = Currency\CurrencyRateTable::getList(array(
            'select' => array('ID', 'RATE', 'RATE_CNT','*'),
            'filter' => array(
                'CURRENCY' => $arRate['CURRENCY'],
                'BASE_CURRENCY' => $arRate['CURRENCY_BASE'],
                '=DATE_RATE' => $dateRate,
            )
        ));
        $multiplier = 1;
        while (ceil($arRate['RATE_CNT'] * $multiplier) != floor($arRate['RATE_CNT'] * $multiplier)) {
            $multiplier *= 10;
            if($multiplier > 99999) {
                break;
            }
        }
        if($multiplier > 1) {
            $arRate['RATE_CNT'] *= $multiplier;
            $arRate['RATE'] *= $multiplier;
            $arRate['RATE_CNT'] = round($arRate['RATE_CNT']);
        }
        if (ceil($arRate['RATE_CNT']) != floor($arRate['RATE_CNT'])) {
            $arRate['RATE'] /= $arRate['RATE_CNT'];
            $arRate['RATE_CNT'] = 1000;
            $arRate['RATE'] *= 1000;
        }
        if ($row = $iterator->fetch())
        {
            $arFields = array();
            if($row['RATE'] != $arRate['RATE'])
                $arFields['RATE'] = $arRate['RATE'];
            if($row['RATE_CNT'] != $arRate['RATE_CNT'])
                $arFields['RATE_CNT'] = $arRate['RATE_CNT'];
            if($arFields) {
                $arKey = [
                    'ID' => $row['ID'],
                    'CURRENCY' => $arRate['CURRENCY'],
                    'BASE_CURRENCY' => $arRate['CURRENCY_BASE'],
                    'DATE_RATE' => $dateRate,
                ];
                Currency\CurrencyRateTable::update($arKey, $arFields);
            }

            while ($row = $iterator->fetch())
            {
                $arKey = [
                    'ID' => $row['ID'],
                    'CURRENCY' => $arRate['CURRENCY'],
                    'BASE_CURRENCY' => $arRate['CURRENCY_BASE'],
                    'DATE_RATE' => $dateRate,
                ];
                Currency\CurrencyRateTable::delete($row['ID'], $arFields);
            }
        }
        else
        {
            $arFields = array(
                'CURRENCY' => $arRate['CURRENCY'],
                'BASE_CURRENCY' => $arRate['CURRENCY_BASE'],
                'DATE_RATE' => $dateRate,
                'RATE' => $arRate['RATE'],
                'RATE_CNT' => $arRate['RATE_CNT'],
            );

            Currency\CurrencyRateTable::add($arFields);
        }
    }

    public static function filterCurrencyRates($arRates)
    {
        $arCurrencies = self::getCurrencies();
        $baseCurrency = Currency\CurrencyManager::getBaseCurrency();

        foreach ($arRates as $i => &$arRate)
        {
            if(!isset($arCurrencies[$arRate['CURRENCY']]) || !isset($arCurrencies[$arRate['CURRENCY_BASE']]))
            {
                unset($arRates[$i]);
            }
            else if($arRate['CURRENCY_BASE'] == $baseCurrency)
            {

            }
            else if($arRate['CURRENCY'] == $baseCurrency)
            {
                $arRate = [
                    'RATE' => $arRate['RATE_CNT'],
                    'RATE_CNT' => $arRate['RATE'],
                    'CURRENCY_BASE' => $arRate['CURRENCY'],
                    'CURRENCY' => $arRate['CURRENCY_BASE'],
                ] + $arRate;
            }
            else
            {
                unset($arRates[$i]);
            }
        }
        unset($arRate);

        return $arRates;
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getCurrencies(): array
    {
        $dbCurrencies = Currency\CurrencyTable::getList();
        $arCurrencies = array();
        while ($arCurrency = $dbCurrencies->fetch()) {
            $arCurrencies[$arCurrency['CURRENCY']] = $arCurrency;
        }
        return $arCurrencies;
    }
}