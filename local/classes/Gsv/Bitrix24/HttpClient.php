<?php


namespace Gsv\Bitrix24;


interface HttpClient
{
    /**
     * Осуществляет запрос на низком уровне
     * @param string $uri Адрес вызываемого метода
     * @param array|null $params Параметры вызываемого метода
     * @return array Возвращаемое CRM значение
     * @throws Bitrix24Exception
     */
    function doCall(string $uri, ?array $params = []) : array;
}