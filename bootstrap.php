<?php
/**
 * Added by Jeff Davis 4/2020
 */

use League\CLImate\CLImate;

require __DIR__ . '/vendor/autoload.php';

define('PROJECT_ROOT', __DIR__);

/**
 * This loads variables from the file called .env into the environment
 * Retrieve them with getenv('NAME');
 */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (getenv('APP_DEBUG') == 'true') {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
} else {
    ini_set("display_errors", 0);
}

