<?php
/**
 * Production configuration for the digital commerce platform.
 *
 * This file defines the fixed database credentials supplied by the operator so
 * the application can connect without relying on the installer flow.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'maxiprov_test1');
define('DB_USER', 'maxiprov_test1');
define('DB_PASSWORD', 'E9~B,s-rg_B1U0QE');

// Optional integrations can be populated later if needed.
define('TELEGRAM_BOT_TOKEN', '');
define('TELEGRAM_CHAT_ID', '');

define('DEFAULT_LANGUAGE', 'en');
