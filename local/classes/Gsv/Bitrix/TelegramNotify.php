<?php

namespace Gsv\Bitrix;

use Bitrix\Main;
use Bitrix\Main\Mail\Context;
use Bitrix\Main\Mail\EventMessageCompiler;
use Bitrix\Main\Mail\StopException;
use Bitrix\Main\Mail\Internal as MailInternal;
use Bitrix\Main\Mail\Event as MailEvent;
use Bitrix\Main\Mail\EventMessageCompiler as MailMessage;
use Gsv\Util\Http;
use Gsv\Util\TelegramBot;
use Gsv\Bitrix\TelegramBot as BXTelegramBot;

class TelegramNotify extends \Gsv\Util\TelegramNotify
{
    protected static $arUserCache = [];

    public function __construct(?TelegramBot $telegram = null, ?Http\HttpClient $httpClient = null)
    {
        $telegram = $telegram ?? new BXTelegramBot();
        parent::__construct($telegram, $httpClient);
    }

    public function sendMessageToUser($message, $isHtml, $USER_ID = null)
    {
        if(empty($USER_ID)) {
            if($GLOBALS['USER']->IsAuthorized()) {
                $USER_ID = $GLOBALS['USER']->GetID();
            } else {
                return false;
            }
        }

        if(!isset(self::$arUserCache[$USER_ID])){
            $arUser = \Bitrix\Main\UserTable::getRow([
                'filter' => [
                    'ID' => $USER_ID,
                ],
                'select' => [
                    'UF_TELEGRAM_ID',
                ],
            ]);
            self::$arUserCache[$USER_ID] = $arUser['UF_TELEGRAM_ID'];
        }

        if(self::$arUserCache[$USER_ID]) {
            return $this->sendMessage(self::$arUserCache[$USER_ID], $message, $isHtml);
        } else {
            return false;
        }
    }

    /**
     * @param array $arEvent
     * @return string
     * @throws Main\ArgumentException
     * @throws Main\ArgumentNullException
     * @throws Main\ArgumentTypeException
     */
    public static function handleEvent(array $arEvent)
    {
        if(!isset($arEvent['FIELDS']) && isset($arEvent['C_FIELDS']))
            $arEvent['FIELDS'] = $arEvent['C_FIELDS'];

        if(!is_array($arEvent['FIELDS']))
            throw new Main\ArgumentTypeException("FIELDS" );

        $flag = MailEvent::SEND_RESULT_TEMPLATE_NOT_FOUND; // no templates
        $arResult = array(
            "Success" => false,
            "Fail" => false,
            "Was" => false,
            "Skip" => false,

            'Items' => [],
        );
/*
        $trackRead = null;
        $trackClick = null;
        if(array_key_exists('TRACK_READ', $arEvent))
            $trackRead = $arEvent['TRACK_READ'];
        if(array_key_exists('TRACK_CLICK', $arEvent))
            $trackClick = $arEvent['TRACK_CLICK'];*/

        $arSites = explode(",", $arEvent["LID"]);
        if(empty($arSites))
        {
            return $flag;
        }

        // get charset and server name for languages of event
        // actually it's one of the sites (let it be the first one)
        $charset = false;
        $serverName = null;

        static $sites = array();
        $infoSite = reset($arSites);

        if(!isset($sites[$infoSite]))
        {
            $siteDb = Main\SiteTable::getList(array(
                'select' => array('SERVER_NAME', 'CULTURE_CHARSET'=>'CULTURE.CHARSET'),
                'filter' => array('=LID' => $infoSite)
            ));
            $sites[$infoSite] = $siteDb->fetch();
        }

        if(is_array($sites[$infoSite]))
        {
            $charset = $sites[$infoSite]['CULTURE_CHARSET'];
            $serverName = $sites[$infoSite]['SERVER_NAME'];
        }

        if(!$charset)
        {
            return $flag;
        }

        // get filter for list of message templates
        $arEventMessageFilter = array();
        $MESSAGE_ID = intval($arEvent["MESSAGE_ID"]);
        if($MESSAGE_ID > 0)
        {
            $eventMessageDb = MailInternal\EventMessageTable::getById($MESSAGE_ID);
            if($eventMessageDb->Fetch())
            {
                $arEventMessageFilter['=ID'] = $MESSAGE_ID;
                $arEventMessageFilter['=ACTIVE'] = 'Y';
            }
        }
        if(empty($arEventMessageFilter))
        {
            $arEventMessageFilter = array(
                '=ACTIVE' => 'Y',
                '=EVENT_NAME' => $arEvent["EVENT_NAME"],
                '=EVENT_MESSAGE_SITE.SITE_ID' => $arSites,
            );

            if($arEvent["LANGUAGE_ID"] <> '')
            {
                $arEventMessageFilter[] = array(
                    "LOGIC" => "OR",
                    array("=LANGUAGE_ID" => $arEvent["LANGUAGE_ID"]),
                    array("=LANGUAGE_ID" => null),
                );
            }
        }

        // get list of message templates of event
        $messageDb = MailInternal\EventMessageTable::getList(array(
            'select' => array('ID'),
            'filter' => $arEventMessageFilter,
            'group' => array('ID')
        ));

        while($arMessage = $messageDb->fetch())
        {
            $eventMessage = MailInternal\EventMessageTable::getRowById($arMessage['ID']);

            $eventMessage['FILE'] = array();
            $attachmentDb = MailInternal\EventMessageAttachmentTable::getList(array(
                'select' => array('FILE_ID'),
                'filter' => array('=EVENT_MESSAGE_ID' => $arMessage['ID']),
            ));
            while($arAttachmentDb = $attachmentDb->fetch())
            {
                $eventMessage['FILE'][] = $arAttachmentDb['FILE_ID'];
            }

            $context = new Context();
            $arFields = $arEvent['FIELDS'];

            foreach (GetModuleEvents("main", "OnBeforeEventSend", true) as $event)
            {
                if(ExecuteModuleEventEx($event, array(&$arFields, &$eventMessage, $context, &$arResult)) === false)
                {
                    continue 2;
                }
            }

            // get message object for send mail
            $arMessageParams = array(
                'EVENT' => $arEvent,
                'FIELDS' => $arFields,
                'MESSAGE' => $eventMessage,
                'SITE' => $arSites,
                'CHARSET' => $charset,
            );
            $message = EventMessageCompiler::createInstance($arMessageParams);
            try
            {
                $message->compile();
            }
            catch(StopException $e)
            {
                $arResult["Was"] = true;
                $arResult["Fail"] = true;
                continue;
            }

            // send mail
            $arResult["Items"][] = $message;
            /*$result = Main\Mail\Mail::send(array(
                'TO' => $message->getMailTo(),
                'SUBJECT' => $message->getMailSubject(),
                'BODY' => $message->getMailBody(),
                'HEADER' => $message->getMailHeaders(),
                'CHARSET' => $message->getMailCharset(),
                'CONTENT_TYPE' => $message->getMailContentType(),
                'MESSAGE_ID' => $message->getMailId(),
                'ATTACHMENT' => $message->getMailAttachment(),
                'TRACK_READ' => $trackRead,
                'TRACK_CLICK' => $trackClick,
                'LINK_PROTOCOL' => Config\Option::get("main", "mail_link_protocol", ''),
                'LINK_DOMAIN' => $serverName,
                'CONTEXT' => $context,
            ));
            if($result)*/
                $arResult["Success"] = true;
            /*else
                $arResult["Fail"] = true;*/

            $arResult["Was"] = true;
        }

        if($arResult["Was"])
        {
            if($arResult["Success"])
            {
                if($arResult["Fail"])
                    $flag = MailEvent::SEND_RESULT_PARTLY; // partly sent
                else
                    $flag = MailEvent::SEND_RESULT_SUCCESS; // all sent
            }
            else
            {
                if($arResult["Fail"])
                    $flag = MailEvent::SEND_RESULT_ERROR; // all templates failed
            }
        }
        elseif($arResult["Skip"])
        {
            $flag = MailEvent::SEND_RESULT_NONE; // skip this event
        }

        $arResult['Flag'] = $flag;

        return $arResult;
    }

    public static function parseEvent(MailMessage $obMessage)
    {
        $type = $obMessage->getMailContentType();
        $isHtml = strpos(strtoupper($type), 'HTML') !== false;
        $message = $obMessage->getMailBody();

        return [
            'html' => $isHtml,
            'message' => $message,
        ];
    }
}