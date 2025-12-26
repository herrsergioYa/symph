<?php

namespace Gsv\Ajax;

use \Bitrix\Main;
use \Bitrix\Main\Security;
use \Bitrix\Main\Security\Mfa;

class ShortCode extends Main\Authentication\ShortCode
{
    /**
     * @var string $login
     */
    protected $login = '';

    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';

    /**
     * @var string $type
     */
    protected $type = self::TYPE_EMAIL;

    /**
     * @var array $code
     */
    protected $code;
    //protected $checkInterval = 300; //seconds, a half of the real time window
    //protected $resendInterval = 60; //seconds

    /**
     * ShortCode constructor.
     * @param string $login
     * @param string $type
     */
    public function __construct($login, $type = self::TYPE_EMAIL)
    {
        $this->login = $login;
        $this->type = $type;

        if($this->type != self::TYPE_EMAIL && $this->type != self::TYPE_SMS)
        {
            throw new Main\ObjectException("Unknown type:" . $type);
        }

        if(!$this->load())
        {
            throw new Main\ObjectException("Internal error");
        }
    }



    /**
     * Generates a 6-number code.
     * @return bool|string
     */
    public function generate()
    {
        $totp = new Mfa\TotpAlgorithm();
        $totp->setInterval($this->checkInterval);
        $totp->setSecret(base64_decode($this->code->getUfOtpSecret()));

        $timecode = $totp->timecode(time());
        $shortCode = $totp->generateOTP($timecode);

        return $shortCode;
    }

    /**
     * Verifies the 6-number code.
     * @param string $code
     * @return Main\Result
     */
    public function verify($code)
    {
        $result = new Main\Result();

        $attempts = (int)$this->code->getUfAttempts();

        if($attempts >= 3)
        {
            $result->addError(new Main\Error("Retry count exceeded.", "ERR_RETRY_COUNT"));
            return $result;
        }

        $totp = new Main\Security\Mfa\TotpAlgorithm();
        $totp->setInterval($this->checkInterval);
        $totp->setSecret(base64_decode($this->code->getUfOtpSecret()));

        $otpResult = false;
        try
        {
            list($otpResult, ) = $totp->verify($code);
        }
        catch(Main\ArgumentException $e)
        {
        }

        if($otpResult)
        {
            $this->code->setUfDateSent(null);
            $this->code->setUfDateResent(null);
        }
        else
        {
            $result->addError(new Main\Error("Incorrect code.", "ERR_CONFIRM_CODE"));

            $this->code->setUfAttempts($attempts + 1);
        }

        $this->code->save();

        return $result;
    }

    /**
     * Checks if previous dispatch time is outside the interval.
     * @return Main\Result
     */
    public function checkDateSent()
    {
        $result = new Main\Result();

        $resultData = [
            "checkInterval" => $this->checkInterval*2,
            "resendInterval" => $this->resendInterval,
        ];

        //alowed only once in a interval
        if($this->code->getUfDateResent())
        {
            $currentDateTime = new Main\Type\DateTime();
            $interval = $currentDateTime->getTimestamp() - $this->code->getUfDateResent()->getTimestamp();

            if($interval < $this->resendInterval)
            {
                $resultData["secondsLeft"] = $this->resendInterval - $interval;
                $resultData["secondsPassed"] = $interval;
                $result->addError(new Main\Error("Timeout not expired yet."));
            }
        }

        $result->setData($resultData);

        return $result;
    }

    /**
     * Saves last sent date.
     * @return bool
     */
    public function saveDateSent()
    {
        $currentDateTime = new Main\Type\DateTime();

        if($this->code->getUfDateSent())
        {
            if(($currentDateTime->getTimestamp() - $this->code->getUfDateSent()->getTimestamp()) > $this->checkInterval*2)
            {
                //reset attempts only for the new code (when time passes)
                $this->code->setUfAttempts(0);
                $this->code->setUfDateSent(null);
            }
        }

        if(!$this->code->getUfDateSent())
        {
            //first time only
            $this->code->setUfDateSent($currentDateTime);
        }
        $this->code->setUfDateResent($currentDateTime);

        $this->code->save();

        return true;
    }

    /**
     * @return Main\EO_User|false
     */
    public function getUser()
    {
        return false;
    }
//
//    /**
//     * @param int $userId
//     */
//    public static function deleteByUser($userId)
//    {
//        \GsvAuthCodeTable::deleteByFilter([
//            "LOGIN" => $userId
//        ]);
//    }

    protected function load()
    {
        $login = $this->login;
        if($this->type == self::TYPE_SMS) {
            $login = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($login);
        }
        $arFilter = [
            "UF_LOGIN" => $login,
            "UF_CODE_TYPE" => $this->type,
        ];

        $code = \GsvAuthCodeTable::getList([
            'filter' => $arFilter,
            'limit' => 1,
        ])->fetchObject();

        if(!$code)
        {
            //first time for the user, should create a record
            $code = \GsvAuthCodeTable::createObject();
            $code->setUfLogin($login);
            $code->setUfCodeType($this->type);
            $code->setUfOtpSecret(base64_encode(Security\Random::getBytes(48)));
            $code->setUfDateResent(null);

            $result = $code->save();
            if(!$result->isSuccess())
            {
                return false;
            }
        }

        $this->code = $code;

        return true;
    }
}