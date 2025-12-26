<?php

namespace Gsv\Agents;

use Gsv\Helpers\SyncRestApi;

class ImportOrdersAgent extends \Gsv\Bitrix\Agent
{
    protected $arExistingOrders = [];
    protected $arExistingFields = [];
    protected $arOrders;

    protected $arExistingItems = [];
    protected $arExistingItemFields = [];
    protected $arItems;

    /** @var ?SyncRestApi $restApi  */
    protected ?SyncRestApi $restApi = null;

    /** @var int $lastId */
    protected $lastId = 0;

    public function getModulesToLoad(&$arModules)
    {
        parent::getModulesToLoad($arModules);
        $arModules[] = 'iblock';
        $arModules[] = 'catalog';
        $arModules[] = 'sale';
    }

    protected function map($arOrder, &$arItems)
    {
        $arResult = [];

        $arItems = [];

        foreach ($arOrder as $k => $v) {
            if($k == 'PROPERTIES') {
                foreach ($v as $name => $val) {
                    $name = strtoupper($name);
                    $arResult["UF_PROP_$name"] = $val;
                }
            } else if($k == 'DELIVERY') {
//                foreach ($v as $name => $val) {
//                    $arResult["UF_DELIV_$name"] = $val;
//                }
                $arResult['UF_DELIVERY'] = json_encode($v);
            } else if($k == 'PAY_SYSTEM') {
//                foreach ($v as $name => $val) {
//                    $arResult["UF_PS_$name"] = $val;
//                }
                $arResult['UF_PAY_SYSTEM'] = json_encode($v);
            } else if($k == 'SHIPMENTS') {
//                foreach ($v as $name => $val) {
//                    $arResult["UF_PS_$name"] = $val;
//                }
                $arResult['UF_SHIPMENTS'] = json_encode($v);
            } else if($k == 'PAYMENTS') {
//                foreach ($v as $name => $val) {
//                    $arResult["UF_PS_$name"] = $val;
//                }
                $arResult['UF_PAYMENTS'] = json_encode($v);
            } else if($k == 'ITEMS') {
                //gsv_dump($v);
                foreach ($v as $arItem) {
                    $arRes = [
                        'UF_ORDER_ID' => $arOrder['ID'],
                    ];
                    foreach ($arItem as $name => $value) {
                        $name = strtoupper($name);
                        $arRes["UF_$name"] = $value;
                    }
                    $arItems[$arRes['UF_ITEM_ID']] = $arRes;
                }
                //gsv_dump($arItems);
            } else {
                $k = strtoupper($k);
                $arResult["UF_$k"] = $v;
            }
        }

        return $arResult;
    }

    protected function adjustOrder($arOrder, $isNew)
    {
        if($isNew) {

        }
        return $arOrder;
    }

    protected function readExistingField()
    {
        global $USER_FIELD_MANAGER;

        $hlblId = \GsvOldOrderTable::getHighloadBlock()['ID'];
        $entity = "HLBLOCK_$hlblId";
        $arFields = $USER_FIELD_MANAGER->GetUserFields($entity);

        //gsv_dump($arFields);
        $this->arExistingFields = $arFields;
    }

    protected function readExistingItemField()
    {
        global $USER_FIELD_MANAGER;

        $hlblId = \GsvOldOrderItemTable::getHighloadBlock()['ID'];
        $entity = "HLBLOCK_$hlblId";
        $arFields = $USER_FIELD_MANAGER->GetUserFields($entity);

        //gsv_dump($arFields);
        $this->arExistingItemFields = $arFields;
    }

    protected function readExistingOrders($arOrders)
    {
        $this->arExistingOrders = [];

        $arExistingIds = [];

        foreach($arOrders as $arOrder) {
            $arExistingIds[] = $arOrder['ID'];
        }

        $dbElem = \GsvOldOrderTable::getList([
            'filter' => [
                'UF_ID' => $arExistingIds
            ],
        ]);

        $arOrders = [];

        while($arElem = $dbElem->fetch()) {
            $arOrders[$arElem['UF_ID']] = $arElem;
        }

        $this->arExistingOrders = $arOrders;
        //gsv_dump($arOrders);
    }

    protected function readExistingItems($arOrders)
    {
        $this->arExistingItems = [];

        $arExistingIds = [];

        foreach($arOrders as $arOrder) {
            $arExistingIds[] = $arOrder['ID'];
        }

        //gsv_dump($arExistingIds);

        $dbElem = \GsvOldOrderItemTable::getList([
            'filter' => [
                'UF_ORDER_ID' => $arExistingIds
            ],
        ]);

        $arItems = [];

        while($arElem = $dbElem->fetch()) {
            //gsv_dump($arElem);
            $arItems[$arElem['UF_ORDER_ID']][$arElem['UF_ITEM_ID']] = $arElem;
        }
        //gsv_dump($arItems);

        $this->arExistingItems = $arItems;
        //gsv_dump($arOrders);
    }

    protected static function clearEnumValue($value)
    {
        if(!empty($value)) {
            return preg_replace('/\s+/', ' ', mb_strtolower(trim($value)));
        } else {
            return $value;
        }
    }

    protected function readXml()
    {
        $restApi = $this->getRestApi();
        $arReq = ['lastId' => $this->lastId];

        $arOrders = $restApi->orders($arReq);
        //gsv_dump($arOrders);

        if(isset($arOrders['lastId']) && is_numeric($arOrders['lastId'])) {
            $this->lastId = intval($arOrders['lastId']);
        } else {
            $this->lastId = null;
        }
        if($arOrders['orders']) {
            $arOrders = $arOrders['orders'];
        } else {
            $arOrders = [];
        }

        return $arOrders;
    }

    protected function getRestApi()
    {
        if($this->restApi === null)
        {
            $this->restApi = new SyncRestApi();
        }
        return $this->restApi;
    }

    protected function readExisting()
    {
        $this->readExistingField();
        $this->readExistingOrders($this->arOrders);
        $this->readExistingItemField();
        $this->readExistingItems($this->arOrders);
    }

    protected function applyFields()
    {
        $arFields = [];
        $arProductFields = [];

        $changed = false;

        foreach ($this->arOrders as $arOrder) {
            $arItems = [];
            $arOrder = $this->map($arOrder, $arItems);

            foreach ($arOrder as $field => $value) {
                $arFields[$field] = ['MULTIPLE' => is_array($value)];
            }

            foreach ($arItems as $arItem) {
                foreach ($arItem as $field => $value) {
                    $arProductFields[$field] = ['MULTIPLE' => false];
                }
            }
        }

        $hlblId = \GsvOldOrderTable::getHighloadBlock()['ID'];
        $changed |= $this->ensureUfs($arFields, $hlblId, $this->arExistingFields);
        $hlblId = \GsvOldOrderItemTable::getHighloadBlock()['ID'];
        $changed |= $this->ensureUfs($arProductFields, $hlblId, $this->arExistingItemFields);

        return $changed;
    }

    protected function ensureUfs($arFields, $hlblId, $arExistingFields)
    {
        $entity = "HLBLOCK_$hlblId";
        $changed = false;

        foreach($arFields as $field => $desc) {
            if(isset($arExistingFields[$field])) {
                continue;
            }

            $code = substr($field, 3);

            /**
             * Добавление пользовательского свойства
             */
            $oUserTypeEntity    = new \CUserTypeEntity();

            $aUserFields    = array(
                /*
                *  Идентификатор сущности, к которой будет привязано свойство.
                */
                'ENTITY_ID' => $entity,
                /* Код поля. Всегда должно начинаться с UF_ */
                'FIELD_NAME' => $field,
                /* Указываем, что тип нового пользовательского свойства строка */
                'USER_TYPE_ID' => 'string',
                /*
                * XML_ID пользовательского свойства.
                * Используется при выгрузке в качестве названия поля
                */
                'XML_ID' => $code,
                /* Сортировка */
                'SORT' => 500,
                /* Является поле множественным или нет */
                'MULTIPLE' => $desc['MUNTIPLE'] ? 'Y' : 'N',
                /* Обязательное или нет свойство */
                'MANDATORY' => 'N',
                /*
                * Показывать в фильтре списка. Возможные значения:
                * не показывать = N, точное совпадение = I,
                * поиск по маске = E, поиск по подстроке = S
                */
                'SHOW_FILTER' => 'N',
                /*
                * Не показывать в списке. Если передать какое-либо значение,
                * то будет считаться, что флаг выставлен.
                */
                'SHOW_IN_LIST' => '',
                /*
                * Пустая строка разрешает редактирование.
                * Если передать какое-либо значение, то будет считаться,
                * что флаг выставлен.
                */
                'EDIT_IN_LIST' => '',
                /* Значения поля участвуют в поиске */
                'IS_SEARCHABLE' => 'N',
                /*
            * Дополнительные настройки поля (зависят от типа).
            * В нашем случае для типа string
            */
                'SETTINGS'  => array(
                    /* Значение по умолчанию */
                    'DEFAULT_VALUE' => '',
                    /* Размер поля ввода для отображения */
                    'SIZE'  => '20',
                    /* Количество строчек поля ввода */
                    'ROWS'  => '1',
                    /* Минимальная длина строки (0 - не проверять) */
                    'MIN_LENGTH'  => '0',
                    /* Максимальная длина строки (0 - не проверять) */
                    'MAX_LENGTH'  => '0',
                    /* Регулярное выражение для проверки */
                    'REGEXP' => '',
                ),
                /* Подпись в форме редактирования */
                'EDIT_FORM_LABEL'   => array(
                    'ru'    => $code,
                    'en'    => $code,
                ),
                /* Заголовок в списке */
                'LIST_COLUMN_LABEL' => array(
                    'ru'    => $code,
                    'en'    => $code,
                ),
                /* Подпись фильтра в списке */
                'LIST_FILTER_LABEL' => array(
                    'ru'    => $code,
                    'en'    => $code,
                ),
                /* Сообщение об ошибке (не обязательное) */
                'ERROR_MESSAGE'     => array(
                    'ru'    => 'Ошибка при заполнении пользовательского свойства',
                    'en'    => 'An error in completing the user field',
                ),
                /* Помощь */
                'HELP_MESSAGE'      => array(
                    'ru'    => '',
                    'en'    => '',
                ),
            );

            $iUserFieldId   = $oUserTypeEntity->Add( $aUserFields ); // int

            $this->arExistingFields[$field] = ['ID' => $iUserFieldId];

            $changed = true;
        }

        return $changed;
    }

    protected function applyItems()
    {
        $this->lastId = null;

        foreach ($this->arOrders as $arOrder) {
            $arItems = [];
            $arOrder = $this->map($arOrder, $arItems);
            //gsv_dump($arOrder);
            //gsv_dump($arOrder['UF_ID']);
            //gsv_dump($this->arExistingOrders[$arOrder['UF_ID']]);
            //gsv_dump($this->arExistingOrders);

            if(isset($this->arExistingOrders[$arOrder['UF_ID']])) {
                $arExistingOrder = $this->arExistingOrders[$arOrder['UF_ID']];
            } else {
                $arExistingOrder = null;
            }

            $this->lastId = $arOrder['UF_ID'];

            $arOrder = $this->adjustOrder($arOrder, !$arExistingOrder);
            //gsv_dump($arOrder);
            //gsv_dump($arExistingOrder);

            $orderUfId = $arOrder['UF_ID'];

            if($arExistingOrder) {
                $arOrder = $this->filterChangedProperties($arExistingOrder, $arOrder);
                //gsv_dump($arOrder);
                if($arOrder) {
                    //gsv_dump($arOrder);
                    $res = \GsvOldOrderTable::update($arExistingOrder['ID'], $arOrder);
                }
            } else {
                //gsv_dump($arOrder);
                $res = \GsvOldOrderTable::add($arOrder);
                //gsv_dump($res);
            }

            //gsv_dump($arItems);

            //gsv_dump($this->arExistingItems);
            //gsv_dump($arOrder);
            //gsv_dump($this->arExistingItems[$orderUfId]);

            if(isset($this->arExistingItems[$orderUfId])) {
                $arExistingItems = $this->arExistingItems[$orderUfId];
            } else {
                $arExistingItems = [];
            }

            //gsv_dump($arExistingItems);

            foreach ($arItems as $arItem) {

                $arExistingItem = $arExistingItems[$arItem['UF_ITEM_ID']];
                //gsv_dump($arExistingItem);
                if ($arExistingItem) {
                    unset($arExistingItems[$arItem['UF_ITEM_ID']]);
                    $arItem = $this->filterChangedProperties($arExistingItem, $arItem);
                    //gsv_dump($arItem);
                    if ($arItem) {
                        //gsv_dump($arItem);
                        $res = \GsvOldOrderItemTable::update($arExistingItem['ID'], $arItem);
                    }
                } else {
                    //gsv_dump($arItem);
                    $res = \GsvOldOrderItemTable::add($arItem);
                    //gsv_dump($res);
                }
            }
            //gsv_dump($arExistingItems);
            while($arExistingItems) {
                $arExistingItem = array_shift($arExistingItems);
                //gsv_dump($arExistingItem);

                $res = \GsvOldOrderItemTable::delete($arExistingItem['ID']);
                //gsv_dump($res);
            }
        }
    }

    protected function filterChangedProperties($arOriginal, $arNew)
    {
        foreach ($arNew as $code => $value) {
            if(!array_key_exists($code, $arOriginal)) {
                continue;
            }
            $oldValue = $arOriginal[$code];
            if(is_array($value) && is_array($oldValue)) {
                $changed = array_diff($value, $oldValue) || array_diff($oldValue, $oldValue);
            } else if(is_array($value) || is_array($oldValue)) {
                $changed = true;
            } else {
                $changed = $value != $oldValue;
            }
            if(!$changed) {
                unset($arNew[$code]);
            }
        }
        return $arNew;
    }

    public function execute(...$params)
    {
        if($params) {
            $this->lastId = intval($params[0]);
        } else {
            $this->lastId = 0;
        }

        $this->arOrders = $this->readXml();
        $this->readExisting();

        if($this->applyFields()) {
            return true;
        }
        $this->applyItems();

        if($this->lastId === null) {
            return false;
        } else {
            return [$this->lastId,];
        }
    }

    public static function dump($data)
    {
        gsv_dump($data);
    }

    protected function log($message, $params = [])
    {
        if($params) {
            foreach ($params as &$param) {
                if(!is_string($param) && !is_numeric($param)) {
                    $param = var_export($param, true);
                }
            }
            $message = sprintf($message, ...$params);
        }

        gsv_dump($message, true);
    }
}