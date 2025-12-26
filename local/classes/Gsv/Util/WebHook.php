<?php

namespace Gsv\Util;

abstract class WebHook
{
    /**
     * @var array $request
     */
    protected $request;

    /**
     * @var string|false|null $action
     */
    protected $action;

    /**
     * @var array $response
     */
    protected $response;

    public function __construct()
    {

    }

    protected function init()
    {

    }

    public function execute()
    {
        $this->request = $this->parseRequest();
        if(!$this->isCalled($this->request)) {
            $this->sendBadRequest();
            return false;//Never called!
        } else if(!($auth = $this->isAllowed($this->request))) {
            if($auth === null) {
                $this->sendAuthorizationNeeded();
            } else {
                $this->sendNotAllowed();
            }
            return false;//Never called for now!
        } else {
            $this->action = $this->getActionName($this->request);
            if($this->action === false) {
                //404. It is an error
                $this->sendNotFound();
                return false;//Never called for now!
            } else if($this->action === null) {
                //200. No action needed. It is Ok.
                $this->sendEmptyResponse();
                return true;//Never called for now!
            } else {
                $method = $this->action . 'Action';//The $method is allowed to be 'Action'!
                $arMethod = [$this, $method];
                $this->response = $this->exec($arMethod, $this->request, $this->action);
                if($this->response) {
                    $this->response = array_shift($this->response);
                    $this->sendResponse($this->response);
                    return true;//Never called for now!
                } else {
                    //404. It is an error
                    $this->sendNotFound();
                    return false;//Never called for now!
                }
            }
        }
    }

    protected function parseRequest()
    {
        return [];
    }

    /**
     * @param array $request
     * @return boolean
     */
    protected function isCalled($request)
    {
        return true;
    }

    /**
     * @param array $request
     * @return ?bool
     */
    protected abstract function isAllowed($request);
    /*{
        return false;
    }*/

    /**
     * @param array $request
     * @return string|false
     */
    protected abstract function getActionName($request);
    /*{
        if(empty($request['action'])) {
            return false;
        } else {
            return $request['action'];
        }
    }*/

    /**
     * @param callable $arMethod
     * @param array $request
     * @param string $action
     * @return array|false
     */
    protected function exec($arMethod, $request, $action)
    {
        if(is_callable($arMethod)) {
            return [$arMethod($request)];
        } else {
            return false;
        }
    }

    /**
     * @param array $response
     * @return void
     */
    protected function sendResponse($response)
    {
        $body = json_encode($response);
        $this->send(200, $body, 'application/json');
    }

    /**
     * @return void
     */
    protected function sendEmptyResponse()
    {
        $this->send(204, '');
    }

    /**
     * @return void
     */
    protected function sendBadRequest()
    {
        $this->send(400, '');
    }

    /**
     * @return void
     */
    protected function sendAuthorizationNeeded()
    {
        $this->send(401, '');
    }

    /**
     * @return void
     */
    protected function sendNotAllowed()
    {
        $this->send(403, '');
    }

    /**
     * @return void
     */
    protected function sendNotFound()
    {
        $this->send(404, '');
    }

    /**
     * @param string|integer $code
     * @param string $body
     * @param ?string $contentType
     * @return void
     */
    protected function send($code, $body, $contentType = null)
    {
        http_response_code($code);
        if($contentType !== null) {
            header('Content-Type: ' . $contentType);
        }
        echo $body;
        die();
    }
}