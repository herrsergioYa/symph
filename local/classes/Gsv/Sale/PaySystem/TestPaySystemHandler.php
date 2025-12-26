<?php

//namespace Gsv\Sale\PaySystem;
namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Request;
use Bitrix\Sale\Payment;
use \Bitrix\Sale\PaySystem\ServiceHandler;
//use \Bitrix\Sale\PaySystem\IReturn;
use \Bitrix\Sale\PaySystem\IHold;

//TODO: It is RAW!!
class TestPaySystemHandler extends ServiceHandler implements /*IReturn,*/ IHold
{
    public function initiatePay(Payment $payment, Request $request = null)
    {
        $params = array('URL' => $this->getUrl($payment, 'pay'));
		$this->setExtraParams($params);

		return $this->showTemplate($payment, "template");
	}

    public static function getIndicativeFields()
    {
        return array('BX_HANDLER' => 'TEST_PAYSSYSTEM');
    }

    public function getCurrencyList()
    {
        // TODO: Implement getCurrencyList() method.
    }

    public function cancel(Payment $payment)
    {
        // TODO: Implement cancel() method.
    }

    public function confirm(Payment $payment)
    {
        // TODO: Implement confirm() method.
    }

    public function processRequest(Payment $payment, Request $request)
    {
        // TODO: Implement processRequest() method.
    }

    public function getPaymentIdFromRequest(Request $request)
    {
        // TODO: Implement getPaymentIdFromRequest() method.
    }
}