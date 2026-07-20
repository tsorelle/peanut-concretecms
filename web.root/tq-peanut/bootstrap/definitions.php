<?php
/**
 * These definations are calculated from directory structure and conventions.
 * They replace previous configuration settings.
 * See: application/config/peanut-bootstrap.php
 *
 * Conventions:
 *  DIR_ prefix indicates an absolute directiry path
 *  URL_ prefix indicates an absolute URL path, beginning with a slash
 */
use Peanut\bootstrap\PathFinder;

/**
 * PHP version check
 */
defined('PEANUT_PHP_VERSION') or define('PEANUT_PHP_VERSION', 80200);

if (PHP_VERSION_ID < PEANUT_PHP_VERSION) {
    exit('PHP version 8.2 or higher is required.');
}
/*
 * Lightweight version of Tops/sys/TPath
 */
include_once 'PathFinder.php';

if (!defined('DIR_PNUT_BOOTSTRAP')) {
    define('DIR_PNUT_BOOTSTRAP', __DIR__);
}
/**
 * Document root can be pre-defined for testing purposes.
 */
if (!defined('DIR_ROOT')) {
    $path = PathFinder::getDocumentRoot();
    define('DIR_ROOT', $path);
}

/**
 * Absolute path to peanut installation
 */
if (!defined('DIR_PEANUT_ROOT')) {
    $path = PathFinder::normalize(__DIR__ . '/..');
    define('DIR_PEANUT_ROOT', $path);
}

/**
 * Root URL to peanut installation
 */
if (!defined('URL_PEANUT_ROOT')) {
    $path = substr(DIR_PEANUT_ROOT, strlen(DIR_ROOT));
    define('URL_PEANUT_ROOT', $path);
}

/**
 * Absolute path to application
 */
if (!defined('PNUT_APPLICATION')) {
    $path = realpath(__DIR__."/../application");
    if ($path) {
        $path = PathFinder::normalize($path);
        define('PNUT_APPLICATION', $path);
    }
    else if  (is_dir(DIR_ROOT . '/application')){
        $path = PathFinder::normalize(DIR_ROOT . '/application');
        define('PNUT_APPLICATION', $path);
    }
}
if (!defined('PNUT_APPLICATION')) {
    exit('Application directory not found.');
}

if (!defined('URL_APPLICATION')) {
    $path = substr(PNUT_APPLICATION, strlen(DIR_ROOT));
    define('URL_APPLICATION', $path);
}

if (!defined('DIR_CONFIGURATION')) {
    define('DIR_CONFIGURATION', DIR_PEANUT_ROOT . '/config');
}

unset($path);