<?php

namespace Sprint\Migration;


class VersionAddedGsvAuthCodeTable20250405121724 extends Version
{
    protected $author = "sgaevoy";

    protected $description = "Таблица для хранения кодов авторизации как зарегистрированным, так и незарегистрированным.
Поддерживает расширение на другие каналы связи, кроме SMS и E-mail, например, можно добавить бота Telegram.";

    protected $moduleVersion = "5.0.0";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
    $hlblockId = $helper->Hlblock()->saveHlblock(array (
  'NAME' => 'GsvAuthCode',
  'TABLE_NAME' => 'gsv_auth_code',
  'LANG' => 
  array (
    'ru' => 
    array (
      'NAME' => 'Код авторизации/регистрации',
    ),
    'en' => 
    array (
      'NAME' => 'Auth/Register Code',
    ),
  ),
));
    $helper->Hlblock()->saveGroupPermissions($hlblockId, array (
  'administrators' => 'W',
  'everyone' => 'R',
));
        $helper->Hlblock()->saveField($hlblockId, array (
  'FIELD_NAME' => 'UF_LOGIN',
  'USER_TYPE_ID' => 'string',
  'XML_ID' => 'LOGIN',
  'SORT' => '100',
  'MULTIPLE' => 'N',
  'MANDATORY' => 'Y',
  'SHOW_FILTER' => 'N',
  'SHOW_IN_LIST' => 'Y',
  'EDIT_IN_LIST' => 'Y',
  'IS_SEARCHABLE' => 'N',
  'SETTINGS' => 
  array (
    'SIZE' => 20,
    'ROWS' => 1,
    'REGEXP' => '',
    'MIN_LENGTH' => 0,
    'MAX_LENGTH' => 0,
    'DEFAULT_VALUE' => '',
  ),
  'EDIT_FORM_LABEL' => 
  array (
    'en' => 'E-mail/Phone',
    'ru' => 'E-mail/телефон',
  ),
  'LIST_COLUMN_LABEL' => 
  array (
    'en' => 'E-mail/Phone',
    'ru' => 'E-mail/телефон',
  ),
  'LIST_FILTER_LABEL' => 
  array (
    'en' => 'E-mail/Phone',
    'ru' => 'E-mail/телефон',
  ),
  'ERROR_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
  'HELP_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
));
            $helper->Hlblock()->saveField($hlblockId, array (
  'FIELD_NAME' => 'UF_CODE_TYPE',
  'USER_TYPE_ID' => 'string',
  'XML_ID' => 'CODE_TYPE',
  'SORT' => '100',
  'MULTIPLE' => 'N',
  'MANDATORY' => 'Y',
  'SHOW_FILTER' => 'N',
  'SHOW_IN_LIST' => 'Y',
  'EDIT_IN_LIST' => 'Y',
  'IS_SEARCHABLE' => 'N',
  'SETTINGS' => 
  array (
    'SIZE' => 20,
    'ROWS' => 1,
    'REGEXP' => '',
    'MIN_LENGTH' => 0,
    'MAX_LENGTH' => 0,
    'DEFAULT_VALUE' => '',
  ),
  'EDIT_FORM_LABEL' => 
  array (
    'en' => 'Code Type',
    'ru' => 'Тип кода',
  ),
  'LIST_COLUMN_LABEL' => 
  array (
    'en' => 'Code Type',
    'ru' => 'Тип кода',
  ),
  'LIST_FILTER_LABEL' => 
  array (
    'en' => 'Code Type',
    'ru' => 'Тип кода',
  ),
  'ERROR_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
  'HELP_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
));
            $helper->Hlblock()->saveField($hlblockId, array (
  'FIELD_NAME' => 'UF_DATE_SENT',
  'USER_TYPE_ID' => 'datetime',
  'XML_ID' => 'DATE_SENT',
  'SORT' => '100',
  'MULTIPLE' => 'N',
  'MANDATORY' => 'N',
  'SHOW_FILTER' => 'N',
  'SHOW_IN_LIST' => 'Y',
  'EDIT_IN_LIST' => 'Y',
  'IS_SEARCHABLE' => 'N',
  'SETTINGS' => 
  array (
    'DEFAULT_VALUE' => 
    array (
      'TYPE' => 'NOW',
      'VALUE' => '',
    ),
    'USE_SECOND' => 'Y',
    'USE_TIMEZONE' => 'N',
  ),
  'EDIT_FORM_LABEL' => 
  array (
    'en' => 'Date Sent',
    'ru' => 'Время первой отправки',
  ),
  'LIST_COLUMN_LABEL' => 
  array (
    'en' => 'Date Sent',
    'ru' => 'Время первой отправки',
  ),
  'LIST_FILTER_LABEL' => 
  array (
    'en' => 'Date Sent',
    'ru' => 'Время первой отправки',
  ),
  'ERROR_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
  'HELP_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
));
            $helper->Hlblock()->saveField($hlblockId, array (
  'FIELD_NAME' => 'UF_DATE_RESENT',
  'USER_TYPE_ID' => 'datetime',
  'XML_ID' => 'DATE_RESENT',
  'SORT' => '100',
  'MULTIPLE' => 'N',
  'MANDATORY' => 'N',
  'SHOW_FILTER' => 'N',
  'SHOW_IN_LIST' => 'Y',
  'EDIT_IN_LIST' => 'Y',
  'IS_SEARCHABLE' => 'N',
  'SETTINGS' => 
  array (
    'DEFAULT_VALUE' => 
    array (
      'TYPE' => 'NOW',
      'VALUE' => '',
    ),
    'USE_SECOND' => 'Y',
    'USE_TIMEZONE' => 'N',
  ),
  'EDIT_FORM_LABEL' => 
  array (
    'en' => 'Date Resent',
    'ru' => 'Время последней отправки',
  ),
  'LIST_COLUMN_LABEL' => 
  array (
    'en' => 'Date Resent',
    'ru' => 'Время последней отправки',
  ),
  'LIST_FILTER_LABEL' => 
  array (
    'en' => 'Date Resent',
    'ru' => 'Время последней отправки',
  ),
  'ERROR_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
  'HELP_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
));
            $helper->Hlblock()->saveField($hlblockId, array (
  'FIELD_NAME' => 'UF_ATTEMPTS',
  'USER_TYPE_ID' => 'double',
  'XML_ID' => 'ATTEMPTS',
  'SORT' => '100',
  'MULTIPLE' => 'N',
  'MANDATORY' => 'Y',
  'SHOW_FILTER' => 'N',
  'SHOW_IN_LIST' => 'Y',
  'EDIT_IN_LIST' => 'Y',
  'IS_SEARCHABLE' => 'N',
  'SETTINGS' => 
  array (
    'PRECISION' => 4,
    'SIZE' => 20,
    'MIN_VALUE' => 0.0,
    'MAX_VALUE' => 0.0,
    'DEFAULT_VALUE' => 0.0,
  ),
  'EDIT_FORM_LABEL' => 
  array (
    'en' => 'Attempts',
    'ru' => 'Число попыток',
  ),
  'LIST_COLUMN_LABEL' => 
  array (
    'en' => 'Attempts',
    'ru' => 'Число попыток',
  ),
  'LIST_FILTER_LABEL' => 
  array (
    'en' => 'Attempts',
    'ru' => 'Число попыток',
  ),
  'ERROR_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
  'HELP_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
));
            $helper->Hlblock()->saveField($hlblockId, array (
  'FIELD_NAME' => 'UF_OTP_SECRET',
  'USER_TYPE_ID' => 'string',
  'XML_ID' => 'OTP_SECRET',
  'SORT' => '100',
  'MULTIPLE' => 'N',
  'MANDATORY' => 'Y',
  'SHOW_FILTER' => 'N',
  'SHOW_IN_LIST' => 'Y',
  'EDIT_IN_LIST' => 'Y',
  'IS_SEARCHABLE' => 'N',
  'SETTINGS' => 
  array (
    'SIZE' => 20,
    'ROWS' => 1,
    'REGEXP' => '',
    'MIN_LENGTH' => 0,
    'MAX_LENGTH' => 0,
    'DEFAULT_VALUE' => '',
  ),
  'EDIT_FORM_LABEL' => 
  array (
    'en' => 'Otp Secret',
    'ru' => 'Otp Secret',
  ),
  'LIST_COLUMN_LABEL' => 
  array (
    'en' => 'Otp Secret',
    'ru' => 'Otp Secret',
  ),
  'LIST_FILTER_LABEL' => 
  array (
    'en' => 'Otp Secret',
    'ru' => 'Otp Secret',
  ),
  'ERROR_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
  'HELP_MESSAGE' => 
  array (
    'en' => '',
    'ru' => '',
  ),
));
        }
}
