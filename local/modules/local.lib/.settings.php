<?php
return [
    'controllers' => [
        'value' => [
            'namespaces' => [
                '\\Local\\Lib\\Controller' => 'api', //local:lib.api.<class_with_namespace>.<action>
                '\\Local\\Lib\\Actions',//local:lib.actions.<class_with_namespace>.<action>
            ],
            //'defaultNamespace' => '\\Local\\Lib\\Ajax',//local:lib.<class_with_namespace>.<action>
        ],
        'readonly' => true
    ]
];