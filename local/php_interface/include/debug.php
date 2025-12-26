<?php

if (gsv_is_debug()) {
    if (file_exists(__DIR__ . '/../mail/debug_mail.php')) {
        require __DIR__ . '/../mail/debug_mail.php';
    }
}