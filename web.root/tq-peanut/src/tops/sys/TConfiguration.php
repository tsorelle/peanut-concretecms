<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/25/2017
 * Time: 6:27 AM
 */

namespace Tops\sys;


/**
 * Class AftmConfiguration
 * @package Tops
 *
 * Handles access to configuration file config.ini, located in same directory as this class file.
 * For ini file documentation see http://php.net/manual/en/function.parse-ini-file.php
 *
 */
class TConfiguration
{
    /**
     * @var array
     */
    private static $ini = null;
    private static $isValid = true;
    private static $requireFiles = false;
    private static $throwExceptions = true;

    // private static $configs = array();

    public static function clearCache()
    {
        self::$ini = null;
        self::$isValid = true;
        self::$requireFiles = false;
        self::$throwExceptions = true;
    }

    public static function reset()
    {
        self::$ini = null;
        self::$isValid = true;
        self::$requireFiles = false;
        self::$throwExceptions = true;

    }

    public static function requireFiles($value = true)
    {
        self::$requireFiles = $value;
    }

    public static function throwExceptions($value = true)
    {
        self::$throwExceptions = $value;
    }

    private static function getIni(array $packages = null)
    {
        if (self::$ini === null) {
            self::$isValid = true;
            self::$ini = self::loadIni();
        }
        // self::$configs = array();
        return self::$ini;
    }

    private static function addError(array $ini = array(), $message='')
    {
        if (!isset($ini['errors'])) {
            $ini['errors'] = array();
        }


    }

    private static function loadIni($fileName = 'settings.ini', $iniPath = null)
    {


        if ($iniPath === null) {
            $iniPath = TPath::getConfigPath().$fileName;
        } else {
            $iniPath = TPath::combine($iniPath, $fileName);
        }
        if (file_exists($iniPath)) {
            $result = parse_ini_file($iniPath, true);
            if ($result === false) {
                self::$isValid = false;
                // php8 compatible
                $lastError = error_get_last();
                $errMsg = $lastError['message'] ??  'Could not parse ini file at ' . $iniPath;
                throw new \Exception($errMsg);
            }
            return $result;
        } else {
            // note: has to be in English since translation services dont work if TCongiguration is not functioning.
            $fileNotFound = sprintf("No configuration file found at '%s'",$iniPath);
            if (self::$requireFiles) {
                throw new \Exception("Ini error: $fileNotFound");
            }
            return array('errors' => array($fileName => $fileNotFound));
        }
    }

    public static function loadAppSettings($files = 'settings.ini')
    {
        if (self::$ini == null) {
            self::$ini = array();
        }
        $files = explode(',', $files);
        foreach ($files as $fileName) {
            self::addSettings($fileName);
        }
    }

    public static function addSettings($fileName, $iniPath = null, $replaceExisting = true)
    {
        $ini = self::loadIni($fileName, $iniPath);
        if ($ini !== false) {
            $sections = array_keys($ini);
            foreach ($sections as $section) {
                if (!array_key_exists($section, self::$ini)) {
                    self::$ini[$section] = array();
                }
                foreach ($ini[$section] as $key => $value) {
                    if ($replaceExisting || !array_key_exists($key, self::$ini)) {
                        self::$ini[$section][$key] = $value;
                    }
                }
            }
        }
    }

    public static function getErrors()
    {
        if (array_key_exists('errors', self::$ini)) {
            return self::$ini['errors'];
        }
        return array();
    }

    public static function hasErrors()
    {
        return !empty(self::$ini['errors']);
    }

    public static function getFatalErrors()
    {
        $result = array();
        $errors = self::getErrors();

        foreach ($errors as $key => $error) {
            if (substr($error, 0, 12) === 'Fatal error:') {
                $result[$key] = $error;
            }
        }
        return $result;
    }

    public static function isValid()
    {
        return self::$isValid;
    }


    /**
     * Retrieve value by key and section
     *
     * @param $key
     * @param $sectionKey
     * @param bool $defaultValue - returned if value does not exist
     * @return bool|mixed
     */
    public static function getValue($key, $sectionKey, $defaultValue = false)
    {
        $section = self::getIniSection($sectionKey);
        if (is_array($section) && array_key_exists($key, $section)) {
            return $section[$key];
        }
        return $defaultValue;
    }

    public static function getBoolean($key, $sectionKey, $defaultValue = false)
    {
        $section = self::getIniSection($sectionKey);
        if (is_array($section) && array_key_exists($key, $section)) {
            return !empty($section[$key]);
        }
        return $defaultValue;
    }

    /**
     * Get array of values by section key
     *
     * @param $sectionKey
     * @return bool|mixed
     */
    public static function getIniSection($sectionKey)
    {
        $ini = self::getIni();
        if (array_key_exists($sectionKey, $ini)) {
            return $ini[$sectionKey];
        }
        return false;
    }

    public static function getMultipleValues($key, $sectionKey,$default=false)
    {
        $result = array();
        $keys = TConfiguration::getValue($key, $sectionKey);
        if ($keys !== false) {
            $keys = explode(',', $keys);
            foreach ($keys as $key) {
                $result[] = self::getCrossReferenceValue($key,$default);
            }
        }
        return $result;
    }

    public static function getCrossReferenceValue($value, $default= false)
    {
        $parts = explode(':section:', $value);
        if (sizeof($parts) == 1) {
            return $value;
        }
        $key = $parts[0];
        $section = $parts[1];
        if (empty($key)) {
            return self::getIniSection($section);
        }
        return self::getValue($key, $section, $default);
    }
}