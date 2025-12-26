<?php

if(!function_exists('custom_mail')) {
    function custom_mail ($to, $subject, $message, $additionalHeaders = '', $additional_parameters="")
    {
        $arEmails = [
            'from' => '',
            'to' => $to,
            'cc' => '',
            'bcc' => '',
            'reply-to' => '',
        ];
        $additional_headers = trim($additionalHeaders);
        $headers = explode("\n", $additional_headers);
        foreach ($headers as $header){
            $arHeader=explode(':',$header);
            $header = ToLower($arHeader[0]);
            if ($header == 'from' || $header == 'cc' || $header == 'bcc' || $header == 'reply-to') {
                if(strlen($arEmails[$header])) {
                    $arEmails[$header] .= ',';
                }
                $arEmails[$header] .= trim($arHeader[1]);
            }
        }

        if (!empty($arEmails['from'])) {

            foreach ($arEmails as $type => $emails) {
                $emailList = [];
                if($emails != '') {
                    foreach (explode(',', $emails) as $value) {
                        $value = trim($value);
                        $matches = [];
                        if (preg_match("/^\s*(.*)<(.+)>\s*$/", $value, $matches)) {
                            $value = [
                                trim($matches[2]),
                                trim($matches[1]),
                            ];
                        } else {
                            $value = [trim($value)];
                        }
                        $emailList[] = $value;
                    }
                }
                $arEmails[$type] = $emailList;
            }

            $from = $arEmails['from'][0];

            if(defined('GSV_MAILBOX') && GSV_MAILBOX && is_array(GSV_MAILBOX)) {
                $arSender = GSV_MAILBOX[$from[0]];
            } else {
                $arSender = false;
            }

            if(empty($arSender) || !is_array($arSender)) {
                $arSender = \Bitrix\Main\Config\Option::get('gsv.custom_php_mail', $from[0], '');
                if($arSender) {
                    $arSender = unserialize($arSender);
                }
            }

            if(empty($arSender) || !is_array($arSender))
            {
                if($additional_parameters != "")
                {
                    return @mail($to, $subject, $message, $additional_headers, $additional_parameters);
                }

                return @mail($to, $subject, $message, $additional_headers);
            }

            $mail = new \PHPMailer\PHPMailer\PHPMailer();

            $mail -> isSMTP();
            $mail -> SMTPAuth = true;
            $mail -> SMTPDebug = 0;

            $mail -> Host = $arSender['SMTP_HOST'];
            $mail -> Port = $arSender['SMTP_PORT'];
            $mail -> Username = $arSender['LOGIN'] ?: $from[0];
            $mail -> Password = $arSender['PASSWORD'];

            if($arSender['SECURE'] == 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else if($arSender['SECURE'] == 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }
            if($arSender['STARTTLS'] == 'Y') {
                $mail->SMTPAutoTLS = true;
            } else if($arSender['STARTTLS'] == 'N') {
                $mail->SMTPAutoTLS = false;
            }

            if(count($from) > 1) {
                $mail->setFrom($from[0], $from[1]);
            } else {
                $mail->setFrom($from[0]);
            }

            $mail -> isHTML();
            $mail -> CharSet = 'UTF-8';

            $address = $arEmails['to'];
            foreach ($address as $addr)
            {
                if(count($addr) > 1) {
                    $mail->addAddress($addr[0], $addr[1]);
                } else {
                    $mail->addAddress($addr[0]);
                }
            }
            $cc = $arEmails['cc'];
            if($cc)
            {
                foreach ($cc as $c) {
                    if(count($c) > 1) {
                        $mail->addCC($c[0], $c[1]);
                    } else {
                        $mail->addCC($c[0]);
                    }
                }
            }
            $bcc = $arEmails['bcc'];
            if($bcc)
            {
                foreach ($bcc as $c) {
                    if(count($c) > 1) {
                        $mail->addBCC($c[0], $c[1]);
                    } else {
                        $mail->addBCC($c[0]);
                    }
                }
            }
            $replyTo = $arEmails['reply-to'];
            if($replyTo)
            {
                foreach ($replyTo as $c) {
                    if(count($c) > 1) {
                        $mail->addReplyTo($c[0], $c[1]);
                    } else {
                        $mail->addReplyTo($c[0]);
                    }
                }
            }

            $headers = explode("\n", $additionalHeaders);
            $attachHeader = '#Content-Type\s*:\s*multipart/(\w+)\s*;\s*boundary\s*=\s*"(.+)"#i';
            $simpleHeader = '#Content-Type\s*:\s*([\w\W]+)#i';
            foreach ($headers as $h) {
                $matches = [];
                if (preg_match($attachHeader, $h, $matches)) {
                    $bndr = $matches[2];
                    $bndr = trim($bndr, '"');
                    $mail -> ContentType = 'multipart/'. $matches[1] . '; boundary="' . $bndr . '"';
                } else if (preg_match($simpleHeader, $h, $matches)) {
                    $mail -> ContentType = trim($matches[1]);
                }
            }

            $mail -> Subject = $subject;
            $mail -> Body = $message;
            //$mail -> From = $from[0];
            //$mail -> FromName = count($from) > 1 ? $from[1] : $from[0];
            return $mail -> send();

        }
    }
}
