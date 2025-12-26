<?php

namespace Gsv\Components;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();

/**
 * @var $APPLICATION CMain
 * @var $USER CUser
 */

Loc::loadMessages(__FILE__);

//2024-12-01 03:51:03 GMT+8
class AjaxController extends \CBitrixComponent
    implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
    /** @var \Bitrix\Main\ErrorCollection */
    protected $errorCollection;

    public function configureActions()
    {
        //если действия не нужно конфигурировать, то пишем просто так. И будет конфиг по умолчанию
        return [];
    }

    public function onPrepareComponentParams($arParams)
    {
        $this->errorCollection = new \Bitrix\Main\ErrorCollection();

        //подготовка параметров
        //Этот код **будет** выполняться при запуске аяксовых-действий

        $arParams = parent::onPrepareComponentParams($arParams);

        return $arParams;
    }

    public function executeComponent()
    {
        //Внимание! Этот код **не будет** выполняться при запуске аяксовых-действий
        return parent::executeComponent();
    }

    protected function listKeysSignedParameters()
    {
        //перечисляем те имена параметров, которые нужно использовать в аякс-действиях
        return [
            'XML_ID',
            'SOMETHING',
        ];
    }

    //в параметры будут автоматически подставлены данные из REQUEST
    public function doSomethingAction($param1 = '')
    {
        return [];
    }

    /**
     * Getting array of errors.
     * @return Error[]
     */
    public function getErrors()
    {
        return $this->errorCollection->toArray();
    }

    /**
     * Getting once error with the necessary code.
     * @param string $code Code of error.
     * @return Error
     */
    public function getErrorByCode($code)
    {
        return $this->errorCollection->getErrorByCode($code);
    }
}