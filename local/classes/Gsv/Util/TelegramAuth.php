<?php

namespace Gsv\Util;

class TelegramAuth
{
    const TIMEOUT = 401;
    const FORBIDDEN = 403;

    /**
     * @var TelegramBot
     */
    protected $bot;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->bot->getName();
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->bot->setName($name);
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->bot->getToken();
    }

    /**
     * @param mixed $token
     */
    public function setToken($token): void
    {
        $this->bot->setToken($token);
    }

    /**
     * @return mixed
     */
    public function getBot()
    {
        return $this->bot;
    }

    /**
     * @param mixed $bot
     */
    public function setBot($bot): void
    {
        $this->bot = $bot;
    }

    public function __construct(?TelegramBot $bot = null)
    {
        $this->bot = $bot ?? new TelegramBot();
    }

    public function checkTelegramAuthorization($auth_data)
    {
        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);
        $data_check_arr = [];
        foreach ($auth_data as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key = hash('sha256', $this->getToken(), true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);
        if (strcmp($hash, $check_hash) !== 0) {
            return [
                'OK' => false,
                'CODE' => static::FORBIDDEN,
                'MSG' => 'Data is NOT from Telegram',
            ];
        }
        if ((time() - $auth_data['auth_date']) > 86400) {
            return [
                'OK' => false,
                'CODE' => static::TIMEOUT,
                'MSG' => 'Data is outdated',
            ];
        }
        return [
            'OK' => true,
            'CODE' => 0,
            'AUTH' => $auth_data,
        ];
    }
}