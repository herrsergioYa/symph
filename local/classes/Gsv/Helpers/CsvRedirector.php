<?php

namespace Gsv\Helpers;

class CsvRedirector
{
    const FILE = '/local/files/redirects.csv';

    protected static $arRedirectRules;

    public static function onPrologHandler()
    {
        $requestURL = \Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPage();
        $arRequestUrl = parse_url($requestURL);

        foreach (static::getRedirectRules() as $arRule) {

            if(static::areTheSame($arRule['URL'])) {
                if(static::arePathesTheSame($arRequestUrl['path'], $arRule['URL']['path'])) {
                    $arUrl = $arRule['TARGET'];
                    if($arRequestUrl['query']) {
                        if($arUrl['query']) {
                            $arUrl['query'] .= '&' . $arRequestUrl['query'];
                        } else {
                            $arUrl['query'] = $arRequestUrl['query'];
                        }
                    }
                    if(static::areTheSame($arUrl)) {
                        unset($arUrl['scheme'], $arUrl['host'], $arUrl['port']);
                        if(static::arePathesTheSame($arRequestUrl['path'], $arUrl['path']) ) {
                            continue;
                        }
                    }
                    $redirectUrl = build_url($arUrl);
                    if($arRule['CODE'] == 301) {
                        $status = "301 Moved Permanently";
                    } else {
                        $status = "302 Found";
                    }
                    \LocalRedirect($redirectUrl, false, $status);
                }
            }
        }
    }

    public static function getRedirectRules()
    {
        if(static::$arRedirectRules === null) {
            static::$arRedirectRules = [];

            $csv = new \CCSVData();
            $csv->LoadFile($_SERVER['DOCUMENT_ROOT'] . self::FILE);

            while($arRule = $csv->Fetch()) {
                list($url, $target, $flags) = $arRule;

                $arUrl = parse_url($url);
                $arTarget = parse_url($target);

                $matches = [];
                if(preg_match('/R\s*=\S*(3\d+)/i', $flags, $matches)) {
                    $code = intval($matches[1]);
                } else if(intval($flags) . "" == $flags) {
                    $code = intval($flags);
                } else {
                    $code = 302;
                }

                $arRedirectRule = [
                    'URL' => $arUrl,
                    'TARGET' => $arTarget,
                    'CODE' => $code,
                ];

                static::$arRedirectRules[] = $arRedirectRule;
            }
        }

        return static::$arRedirectRules;
    }

    public static function areTheSame($arUrl)
    {
        if(is_string($arUrl)) {
            $arUrl = parse_url($arUrl);
        }

        if(isset($arUrl['host']) && $arUrl['host'] !== $_SERVER['SERVER_NAME']) {
            return false;
        }

        $scheme = \CMain::IsHTTPS() ? 'https' : 'http';
        if(isset($arUrl['scheme']) && $arUrl['scheme'] !== $scheme) {
            return false;
        }

        $port = \CMain::IsHTTPS() ? 443 : 80;
        if(isset($arUrl['port']) && $arUrl['port'] !== $port) {
            return false;
        }

        return true;
    }

    public static function arePathesTheSame($path1, $path2)
    {
        $path1 = '/' . ltrim($path1, '/');
        $path2 = '/' . ltrim($path2, '/');

        if($path1 === $path2) {
            return true;
        }

        $path1 = preg_replace('#/index\.(php|htm|html|asp|aspx)$#', '/', $path1);
        $path2 = preg_replace('#/index\.(php|htm|html|asp|aspx)$#', '/', $path2);

        if($path1 === $path2) {
            return true;
        }

        $path1 = rtrim($path1, '/');
        $path2 = rtrim($path2, '/');

        if($path1 === $path2) {
            return true;
        }

        return false;
    }
}