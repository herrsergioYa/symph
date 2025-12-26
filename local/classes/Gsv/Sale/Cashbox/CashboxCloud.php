<?php

namespace Gsv\Sale\Cashbox;

use Bitrix\Sale;
use Bitrix\Sale\Cashbox\CashboxPaySystem;
use Bitrix\Sale\Cashbox\Check;
use Bitrix\Sale\Cashbox\CheckManager;
use Bitrix\Main;
use Bitrix\Catalog;
use Bitrix\Main\Localization;
use Bitrix\Sale\Cashbox\Errors;
use Bitrix\Sale\Cashbox\Internals\CashboxCheckTable;

//TODO: It is RAW!!
//class CashboxCloud extends \Bitrix\Sale\Cashbox\CashboxPaySystem
class CashboxCloud extends \Bitrix\Sale\Cashbox\Cashbox/*PaySystem*/ implements
    \Bitrix\Sale\Cashbox\IPrintImmediately,
    \Bitrix\Sale\Cashbox\ICheckable
{
    const CHEQUE_TYPES = [
        'income' => 'Income'
    ];

    const VAT = [
        'none' => null,
        0 => 0,
        10 => 10,
        20 => 20,
    ];

    const CHEQUE_METHODS = [
        'fullprepayment' => 1,//FullPrepayment, предоплата 100%
        'sell' => 4,//FullPay, полный расчёт
        'advancepayment' => 3,//AdvancePay, аванс
        'prepayment' => 2,//PartialPrepayment, предоплата
    ];

    #[\Override] public function buildCheckQuery(Check $check) : Sale\Result
    {
        $result = new Sale\Result();

        $fields = $check->getDataForCheck();

        $items = [];
        $totalSum = 0;

        $method = static::CHEQUE_METHODS[$fields['type']];

        if(empty($method)) {
            $result->addError(new Main\Error('Неизвестный тип чека'));
        }

        foreach ($fields['items'] as $basketItem) {

            $item = [
                'Label' => $basketItem['name'] . ', шт',
                'Price' => number_format($basketItem['price'], 2, ".", ''),
                'Quantity' => $basketItem['quantity'],
                'Vat' => static::getVatType($basketItem['vat']),
                'Amount' => $basketItem['price'] * $basketItem['quantity'],//$basketItem['sum']?
                'Method' => $method,
                'Ean13' => null,
            ];

            //HACK: We do force Vat=20%
            $item['Vat'] = 20;

            $totalSum += $item['Amount'];

            $items[] = $item;
        }

        $controlSum = $fields['total_sum'];

        if($totalSum != $controlSum) {
            $diff = $controlSum - $totalSum;
            $isSumOk = false;
            // Взял из старого кода. Сомнительная мера, конечно.
            // ищем куда приткнуть разницу (количество товара == 1)
            foreach ($items as $id=>$item) {
                if($item['Quantity'] == 1) {
                    $items[$id]['Price'] = number_format($item['Price'] + $diff, 2, ".", '');
                    $items[$id]['Amount'] = number_format($item['Amount'] + $diff, 2, ".", '');
                    $isSumOk = true;
                    break;
                }
            }
        } else {
            $isSumOk = true;
        }

        if(!$isSumOk) {
            $result->addError(new Errors\Error('Не удалось раскидать сумму по товарам'));
            return $result;
        }

        $receipt = [
            'Items' => $items,
            'TaxationSystem' => $this->getValueFromSettings('TAX', 'TYPE'),
            'Amounts' => number_format($controlSum, 2, ".", ''),
        ];

        if($receipt['TaxationSystem'] == 'NONE') {
            unset($receipt['TaxationSystem']);
        }

        $order = $fields['order'];
        if($order) {
            /** @var \Bitrix\Sale\PropertyValue $obProperty */
            foreach ($order->getPropertyCollection() as $obProperty) {
                $arProperty = $obProperty->getProperty();
                if($arProperty['IS_EMAIL'] == 'Y') {
                    $value = $obProperty->getValue();
                    if(is_array($value)) {
                        $value = array_values($value)[0];
                    }
                    if($value) {
                        $receipt['Email'] = $value;
                    }
                } else if($arProperty['IS_PHONE'] == 'Y') {
                    $value = $obProperty->getValue();
                    if(is_array($value)) {
                        $value = array_values($value)[0];
                    }
                    $value = preg_replace('/\D+/', '', $value);
                    if($value) {
                        $value = '+' . $value;
                        $receipt['Phone'] = $value;
                    }
                }
            }
        }

        $data = [
            'Inn' => $this->getValueFromSettings('BASE', 'INN'),
            'Type' => static::CHEQUE_TYPES[$fields['calculated_sign']],
            'CustomerReceipt' => $receipt,
            'InvoiceId' => '' . $fields['order']->getId(),// . '/' . $payment->getId(),
            'AccountId' => '' . $fields['order']->getField('USER_ID'),
        ];

        if(empty($data['Type'])) {
            $result->addError(new Main\Error('Неизвестный тип расчета'));
        }

        $result->setData($data);
        return $result;
    }

    protected function getPrintUrl(): string
    {
        $url = 'https://api.cloudpayments.ru/kkt/receipt';
        return $url;
    }

    protected function getCheckUrl(): string
    {
        $url = 'https://api.cloudpayments.ru/kkt/receipt/status/get';
        return $url;
    }

    protected function send(string $url, Sale\Payment $payment, array $fields): Sale\Result
    {
        $result = new  Sale\Result();

        if($this->isDebug()) {
            $uuid = $fields['InvoiceId'];
            $filename = date('Y-m-d_H-i-s');
            $filename = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/cloud/send/' . $filename . '.log';
            gsv_dump($fields, $filename);
        } else {
            $uuid = '';
            $result->addError(new Main\Error('Внутренняя ошибка'));
        }

        $result->setData(['UUID' => $uuid]);

        return $result;
    }

    protected function request(string $url, array $fields): Sale\Result
    {
        $result = new Sale\Result();

        if($this->isDebug()) {
            $response = [
                "Model" => "Processed",
                "Success" => true,
            ];
            $filename = date('Y-m-d_H-i-s');
            $filename = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/cloud/request/' . $filename . '.log';
            gsv_dump($fields, $filename);
        } else {
            $response = null;
            $result->addError(new Main\Error('Внутренняя ошибка'));
        }

        if($result->isSuccess()) {
            $response['Id'] = $fields['Id'];//HACK: The response does not provide an Id
            $result->setData($response);
        }

        return $result;
    }

    protected function getDataForCheck(Check $check): Sale\Result
    {
        $result = [];
        $result['Id'] = $check->getField('EXTERNAL_UUID');
        if(empty($result['Id'])) {
            $result = new Sale\Result();
            $result->addError(new Errors\Error('Чек не был отправлен на печать'));
            return $result;
        }
        $result = static::wrapResult($result);
        return $result;
    }

    public static function getName()
    {
        //parent::getName();
        return 'Test Cashbox';
    }

    #[\Override] public function buildZReportQuery($id)
    {
        // TODO: Implement buildZReportQuery() method.
        return [];
    }

    #[\Override] public function check(Check $check)
    {
        $result = $this->getDataForCheck($check);

//        $payment = CheckManager::getPaymentByCheck($check);
//        if($payment === null) {
//            $result->addError(new Main\Error('В чеке не указан платеж'));
//        }

        if (!$result->isSuccess())
            return $result;

        $url = $this->getCheckUrl();
        $fields = $result->getData();
        $result = $this->request($url, $fields);

        $checkData = $result->getData();
        $checkData['ID'] = $check->getField('ID');//HACK: The response does not provide the ID

        return static::applyCheckResult($checkData);
    }

    #[\Override] public function printImmediately(Check $check)
    {
        $result = new Sale\Result();

        $pkCheck = $this->buildCheckQuery($check);
        if(!$pkCheck->isSuccess()) {
            $result->addErrors($pkCheck->getErrors());
        }

        $payment = CheckManager::getPaymentByCheck($check);
        if($payment === null) {
            $result->addError(new Main\Error('В чеке не указан платеж'));
        }

        if(!$result->isSuccess()) {
            return $result;
        }

        $url = $this->getPrintUrl();
        $fields = $pkCheck->getData();
        $printResult = $this->send($url, $payment, $fields);

        return $printResult;
    }

    protected static function extractCheckData(array $data)
    {
        $result = array();

        if (!$data['Success'])
        {
            $result['ERROR'] = array(
                //'CODE' => '500',
                'MESSAGE' => 'Ошибка запроса к серверу кассы',
                'TYPE' => Errors\Warning::TYPE
            );
            return $result;
        }

        if($data['Model'] == 'Queued')
        {
            if($data['Warnings'])
            {
                $result['ERROR'] = array(
                    //'CODE' => '500',
                    'MESSAGE' => $data['Warnings'][0]['Description'],
                    'TYPE' => Errors\Warning::TYPE
                );
            }
            return $result;
        }

        if($data['ID'])
            $checkInfo = CashboxCheckTable::getById($data['ID'])->fetch();
        else
            $checkInfo = CheckManager::getCheckInfoByExternalUuid($data['Id']);
        if (empty($checkInfo))
        {
            return $result;
        }

        if ($data['Model'] == 'Error') {
            $result['ERROR'] = array(
                'CODE' => '500',
                'MESSAGE' => $data['Message'],
                'TYPE' => Errors\Error::TYPE
            );
        }

        if ($data['Model'] != 'Processed') {
            $result['ERROR'] = array(
                //'CODE' => '500',
                'MESSAGE' => 'Неизвестный статус чека',
                'TYPE' => Errors\Warning::TYPE
            );
        }

        $result['ID'] = $checkInfo['ID'];
        //Do we need this one? Is the CHECK_TYPE ignored?
        $result['CHECK_TYPE'] = $checkInfo['TYPE'];

        $result['LINK_PARAMS'] = [
            'CUSTOM_LINK' => 'https://something.org/check/' . $result['ID'],
        ];

        return $result;
    }

    public static function getOption($option)
    {
        return \Bitrix\Main\Config\Option::get('gsv.cashbox_cloud', $option, '');
    }

    /**
     * @param int $modelId
     * @return array
     */
    public static function getSettings($modelId = 0)
    {
        $settings = array(
            'BASE' => array(
                'LABEL' => 'Организация',
                'REQUIRED' => 'Y',
                'ITEMS' => array(
                    'INN' => array(
                        'TYPE' => 'STRING',
                        'LABEL' => 'ИНН',
                    ),
                )
            ),
            'AUTH' => array(
                'LABEL' => 'Доступы к кассе',
                'REQUIRED' => 'Y',
                'ITEMS' => array(
                    'LOGIN' => array(
                        'TYPE' => 'STRING',
                        'LABEL' => 'Login',
                    ),
                    'TOKEN' => array(
                        'TYPE' => 'STRING',
                        'LABEL' => 'Token',
                    ),
                )
            ),
            'SERVICE' => array(
                'LABEL' => 'Касса',
                'REQUIRED' => 'Y',
                'ITEMS' => array(
                    'TEST' => array(
                        'TYPE' => 'ENUM',
                        'VALUE' => '1',
                        'LABEL' => 'Тестовый режим',
                        'OPTIONS' => array(
                            '1' => 'Да',
                            '0' => 'Нет',
                        )
                    ),
//                    'INN' => array(
//                        'TYPE' => 'STRING',
//                        'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_SERVICE_INN_LABEL')
//                    ),
//                    'P_ADDRESS' => array(
//                        'TYPE' => 'STRING',
//                        'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_SERVICE_URL_LABEL')
//                    ),
                )
            ),
            'TAX' => [
                'LABEL' => 'Налоги',
                'ITEMS' => array(
                    'TYPE' => array(
                        'TYPE' => 'ENUM',
                        'VALUE' => 'NONE',
                        'LABEL' => 'Тип налогооблажения',
                        'OPTIONS' => array(
                            'NONE' => 'По умолчанию',
                            '0' => 'Общая система налогообложения',
                            '1' => 'Упрощенная система налогообложения (Доход)',
                            '2' => 'Упрощенная система налогообложения (Доход минус Расход)',
                            '3' => 'Единый налог на вмененный доход',
                            '4' => 'Единый сельскохозяйственный налог',
                            '5' => 'Патентная система налогообложения',
                        )
                    ),
                )
            ]
        );

        /*$settings['PAYMENT_TYPE'] = array(
            'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_P_TYPE'),
            'REQUIRED' => 'Y',
            'ITEMS' => array()
        );

        $systemPaymentType = array(
            Check::PAYMENT_TYPE_CASH => 0,
            Check::PAYMENT_TYPE_CASHLESS => 1,
        );
        foreach ($systemPaymentType as $type => $value)
        {
            $settings['PAYMENT_TYPE']['ITEMS'][$type] = array(
                'TYPE' => 'STRING',
                'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_P_TYPE_LABEL_'.ToUpper($type)),
                'VALUE' => $value
            );
        }*/

//        $settings['VAT'] = array(
//            'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_SETTINGS_VAT'),
//            'REQUIRED' => 'Y',
//            'ITEMS' => array(
//                /*'NOT_VAT' => array(
//                    'TYPE' => 'STRING',
//                    'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_SETTINGS_VAT_LABEL_NOT_VAT'),
//                    'VALUE' => 'none'
//                )*/
//            )
//        );

        /*if (Main\Loader::includeModule('catalog'))
        {
            $dbRes = Catalog\VatTable::getList(['filter' => ['ACTIVE' => 'Y']]);
            $vatList = $dbRes->fetchAll();
            if ($vatList)
            {
                $defaultVatList = static::VAT;

                foreach ($vatList as $vat)
                {
                    if($vat['EXCLUDE_VAT'])
                        $rate = 'none';
                    else
                        $rate = (int)$vat['RATE'];

                    if (isset($defaultVatList[$rate]))
                        $value = $defaultVatList[$rate];
                    else
                        $value = '';

                    $settings['VAT']['ITEMS'][(int)$vat['ID']] = array(
                        'TYPE' => 'STRING',
                        'LABEL' => $vat['NAME']. (is_numeric($rate) ? ' ['.$rate.'%]' : ''),
                        'VALUE' => $value,
                        'DEFAULT' => is_numeric($rate) ? $rate : null),
                    );
                }
            }
        }*/

        /*$settings['TAX'] = array(
            'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_SNO'),
            'REQUIRED' => 'Y',
            'ITEMS' => array(
                'SNO' => array(
                    'TYPE' => 'ENUM',
                    'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_SNO_LABEL'),
                    'VALUE' => 'osn',
                    'OPTIONS' => array(
                        'osn' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_OSN'),
                        'usn_income' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_UI'),
                        'usn_income_outcome' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_UIO'),
                        'envd' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_ENVD'),
                        'esn' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_ESN'),
                        'patent' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_PATENT')
                    )
                )
            )
        );*/

        return $settings;
    }

    public static function getVatRates()
    {
        static $vatRates = null;

        if($vatRates === null) {
            $vatRates = [
                0 => static::VAT['none'], //Fallback?
            ];

            if (Main\Loader::includeModule('catalog')) {
                $dbRes = Catalog\VatTable::getList(['filter' => ['ACTIVE' => 'Y']]);
                $vatList = $dbRes->fetchAll();
                if ($vatList) {
                    $defaultVatList = static::VAT;

                    foreach ($vatList as $vat) {
                        if ($vat['EXCLUDE_VAT'] == 'Y') {
                            $vatRates[(int)$vat['ID']] = $defaultVatList['none'];
                        } elseif (isset($defaultVatList[(int)$vat['RATE']])) {
                            $vatRates[(int)$vat['ID']] = $defaultVatList[(int)$vat['RATE']];
                        }
                    }
                }
            }
        }

        return $vatRates;
    }

    public static function getVatType($vatId)
    {
        $vatRates = static::getVatRates();
        return $vatRates[(int)$vatId];
    }

    public function isDebug()
    {
        return !!$this->getValueFromSettings('SERVICE', 'TEST');
    }

    protected static function wrapResult(array $data)
    {
        $result = new Sale\Result();
        $result->setData($data);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public static function getFfdVersion(): ?float
    {
        return 1.05;
    }

    /**
     * @param array $linkParams
     * @return string
     */
    public function getCheckLink(array $linkParams)
    {
        if ($linkParams['CUSTOM_LINK']) {
            return $linkParams['CUSTOM_LINK'];
        }

        return '';
    }
}