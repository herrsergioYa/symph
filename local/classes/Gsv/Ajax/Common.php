<?php

namespace Gsv\Ajax;

use Gsv\Ajax\ShortCode;
use Gsv\Ajax\User;

class Common extends \Gsv\Bitrix\JsonAjaxController
{
    public function isoDateToSiteAction($arParams)
    {
        $date = $arParams['date'];

        return static::isoDateToSite($date);
    }

    public static function isoDateToSite($date)
    {
        $result = static::parseIsoDate($date);
        if(empty($result)) {
            return '';
        }
        $obDate = $result;
        $sFormat = $result instanceof \Bitrix\Main\Type\DateTime ? 'FULL' : 'SHORT';

        $result = $obDate->getTimestamp();
        $result = \ConvertTimeStamp($result, $sFormat);

        return $result;
    }

    public static function parseIsoDate($date) : ?\Bitrix\Main\Type\Date
    {
        $date = $date ?: '';
        $date = preg_replace('/\s+/', ' ', $date);
        $date = trim($date);

        if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return new \Bitrix\Main\Type\Date($date, "Y-m-d");
        } else if(preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}[-:]\d{2}$/', $date)) {
            return new \Bitrix\Main\Type\DateTime($date, "Y-m-d H:i");
        } else if(preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}[-:]\d{2}[-:]\d{2}$/', $date)) {
            return new \Bitrix\Main\Type\DateTime($date, "Y-m-d H:i:s");
        } else {
            return null;
        }
    }

    public function siteDateToIsoAction($arParams)
    {
        $date = $arParams['date'];
        $sFormat = $arParams['format'];
        return static::siteDateToIso($date, $sFormat);
    }

    public static function siteDateToIso($date, $sFormat = false)
    {
        $result = static::parseSiteDate($date, $sFormat);
        if($sFormat === 'SHORT') {
            $result = $result->format("Y-m-d");
        } else {
            $result = $result->format("Y-m-d H:i:s");
        }

        return $result;
    }

    public static function parseSiteDate($date, $sFormat = false)
    {
        $date = $date ?: '';
        $date = preg_replace('/\s+/', ' ', $date);
        $date = trim($date);

        $sFormat = $sFormat ?: '';
        $sFormat = trim($sFormat);
        $sFormat = /*mb_*/strtoupper($sFormat);

        if($sFormat === 'FULL') {
            $result = \MakeTimeStamp($date, FORMAT_DATETIME);
        } else if($sFormat === 'SHORT') {
            $result = \MakeTimeStamp($date, FORMAT_DATE);
        } else {
            $result = \MakeTimeStamp($date);
        }

        if($sFormat !== 'SHORT') {
            $result = \Bitrix\Main\Type\DateTime::createFromTimestamp($result);
        } else {
            $result = \Bitrix\Main\Type\Date::createFromTimestamp($result);
        }

        return $result;
    }
}