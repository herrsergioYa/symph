<?php

    if(!function_exists('custom_mail'))
    {
        function custom_mail($to, $subject, $message, $additional_headers='', $additional_parameters='')
        {
            $file = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/mail/';
            if(!is_dir($file))
                mkdir($file, 0775, true);
            $file .= date('Y-m-d_H.i.s') . '.txt';

            while(file_exists($file))
            {
                $file = substr($file, 0, strlen($file) - 4);
                $file .= '_' . randString(3) . '.txt';
            }

            $text = "Subject: $subject\r\nTo: $to\r\n";
            $additional_headers = trim($additional_headers);
            if(strlen($additional_headers))
                $text .=  $additional_headers . "\r\n";
            $additional_parameters  = trim($additional_parameters);
            if(strlen($additional_parameters))
                $text .=  trim($additional_parameters) . "\r\n";
            $text .= "\r\n";
            $text .= $message;

            return file_put_contents($file, $text) !== false;
        }
    }