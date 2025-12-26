<?php

namespace Gsv\Agents;

use Gsv\Helpers\SyncRestApi;

class ImportUsersAgent extends \Gsv\Bitrix\Agent
{
    protected $arExistingUsers = [];
    protected $arExistingGroups = [];

    protected $arUsers;

    protected const MAPPING = [
        'ID' => 'UF_OLD_ID',
        'LOGIN' => 'LOGIN',
        'EMAIL' => 'EMAIL',
        'NAME' => 'NAME',
        'SECOND_NAME' => 'SECOND_NAME',
        'LAST_NAME' => 'LAST_NAME',
        'XML_ID' => 'XML_ID',
        'PERSONAL_PHONE' => 'PERSONAL_PHONE',
        'PERSONAL_MOBILE' => 'PERSONAL_MOBILE',
        'PERSONAL_BIRTHDAY' => 'PERSONAL_BIRTHDAY',
        'WORK_PHONE' => 'WORK_PHONE',
        'PHONE_NUMBER' => 'PHONE_NUMBER',
    ];

    /** @var \CUser $obUser */
    protected $obUser = null;

    /** @var \CGroup $obGroup */
    protected $obGroup = null;

    /** @var ?SyncRestApi $restApi  */
    protected ?SyncRestApi $restApi = null;

    /** @var int $lastId */
    protected $lastId = 0;

    public function getModulesToLoad(&$arModules)
    {
        parent::getModulesToLoad($arModules);

    }

    protected function map($arUser)
    {
        $arResult = ['GROUP_ID' => []];

        foreach (static::MAPPING as $from => $to) {
            if(empty($arUser[$from])) {
                $arResult[$to] = false;
            } else {
                $arResult[$to] = $arUser[$from];
            }
        }

        foreach ($arUser['GROUPS'] as $grpName) {
            $grpNameLC = static::clearEnumValue($grpName);
            if(!isset($this->arExistingGroups[$grpNameLC])) {
                if($this->obGroup === null) {
                    $this->obGroup = new \CGroup();
                }
                $arGroup = ['NAME' => $grpName];
                $arGroup['ID'] = $this->obGroup->Add($arGroup);
                $this->arExistingGroups[$grpNameLC] = $arGroup;
            }
            $arResult['GROUP_ID'][] = $this->arExistingGroups[$grpNameLC]['ID'];
        }

        return $arResult;
    }

    protected function adjustUser($arUser, $isNew)
    {
        if($isNew) {
            $password = \Bitrix\Main\Security\Random::getString(32, true);
            $arUser['PASSWORD'] = $arUser['CONFIRM_PASSORD'] = $password;
        }
        return $arUser;
    }

    protected function readExistingGroups()
    {
        $this->arExistingGroups = [];
        $dbElem = \Bitrix\Main\GroupTable::getList([
            'select' => ['ID', 'NAME'],
        ]);
        while($arElem = $dbElem->Fetch()) {
            $nameLC = static::clearEnumValue($arElem['NAME']);
            $this->arExistingGroups[$nameLC] = $arElem;
        }
    }

    protected function readExistingUsers()
    {
        $this->arExistingUsers = [];

        $arExistingIds = [];

        foreach($this->arUsers as $arUser) {
            $arExistingIds[] = $arUser['ID'];
        }

        $arSelect = array_flip(array_values(static::MAPPING));
        unset($arSelect['UF_OLD_ID']);
        if(isset($arSelect['PHONE_NUMBER'])) {
            unset($arSelect['PHONE_NUMBER']);
            $arSelect = array_keys($arSelect);
            $arSelect['PHONE_NUMBER'] = 'PHONE_AUTH.PHONE_NUMBER';
        } else {
            $arSelect = array_keys($arSelect);
        }
        $arSelect[] = 'ID';
        $arSelect[] = 'UF_OLD_ID';

        $dbElem = \Bitrix\Main\UserTable::getList([
            'filter' => [
                '!UF_OLD_ID' => false,
                'UF_OLD_ID' => $arExistingIds,
            ],
            'select' => $arSelect,
        ]);

        $arUsers = [];

        while($arElem = $dbElem->Fetch()) {
            $arElem['GROUP_ID'] = [];
            $arUsers[$arElem['ID']] = $arElem;
        }

        $dbGrp = \Bitrix\Main\UserGroupTable::getList([
            'filter' => ['USER_ID' => array_keys($arUsers)],
            'select' => ['USER_ID', 'GROUP_ID'],
        ]);
        while($arGrp = $dbGrp->fetch()) {
            $arUsers[$arGrp['USER_ID']]['GROUP_ID'][] = $arGrp['GROUP_ID'];
        }

        foreach ($arUsers as $arElem) {
            $this->arExistingUsers[$arElem['UF_OLD_ID']] = $arElem;
        }
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

        $arUsers = $restApi->users($arReq);

        if(isset($arUsers['lastId']) && is_numeric($arUsers['lastId'])) {
            $arUsers['lastId'] = intval($arUsers['lastId']);
            if($arUsers['lastId'] != $this->lastId) {
                $this->lastId = intval($arUsers['lastId']);
            } else {
                $this->lastId = null;
            }
        } else {
            $this->lastId = null;
        }
        if($arUsers['users']) {
            $arUsers = $arUsers['users'];
        } else {
            $arUsers = [];
        }

        return $arUsers;
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
        $this->readExistingGroups();
        $this->readExistingUsers();
    }

    protected function applyItems()
    {
        foreach ($this->arUsers as $arUser) {
            $arUser = $this->map($arUser);

            if(isset($this->arExistingUsers[$arUser['UF_OLD_ID']])) {
                $arExistingUser = $this->arExistingUsers[$arUser['UF_OLD_ID']];
            } else {
                $arExistingUser = null;
            }

            $arUser = $this->adjustUser($arUser, !$arExistingUser);
            //gsv_dump($arUser);
            //gsv_dump($arExistingUser);

            if($arExistingUser) {
                $arUser = $this->filterChangedProperties($arExistingUser, $arUser);
                //gsv_dump($arUser);
                if($arUser) {
                    if($this->obUser === null) {
                        $this->obUser = new \CUser();
                    }
                    $this->obUser->Update($arExistingUser['ID'], $arUser);
                }
            } else {
                if($this->obUser === null) {
                    $this->obUser = new \CUser();
                }
                //gsv_dump($arUser);
                $USER_ID = $this->obUser->Add($arUser);
                //gsv_dump([$USER_ID, $this->obUser->LAST_ERROR]);
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

        $this->arUsers = $this->readXml();
        $this->readExisting();

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