<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 7/27/2017
 * Time: 5:02 AM
 */

namespace Tops\sys;


class TIniSettings {
    private $ini;

    public function __construct($ini) {
        $this->ini = $ini;
    }

    public static function Open($path,$returnEmpty=true) {
        $result = @parse_ini_file($path,true);
        if (empty($result)) {
            return $returnEmpty ? array() : false;
        }
        return $result;
    }

    public static function Create($fileName = 'settings.ini', $iniPath = null, $sections = true)
    {
        if ($iniPath === null) {
            $iniPath = TPath::getConfigPath() . $fileName;
        } else {
            $iniPath = TPath::combine($iniPath, $fileName);
        }
        $ini = parse_ini_file($iniPath, $sections);
        return $ini ? new TIniSettings($ini) : false;
    }

    /**
     * Retrieve value by key and section
     *
     * @param $key
     * @param $sectionKey
     * @param bool $defaultValue - returned if value does not exist
     * @return bool|mixed
     */
    public function getValue($key, $sectionKey, $defaultValue = false)
    {
        $section = $this->getSection($sectionKey);
        if (is_array($section) && array_key_exists($key, $section)) {
            return $section[$key];
        }
        return $defaultValue;
    }

    public function getBoolean($key, $sectionKey, $defaultValue = false)
    {
        $section = $this->getSection($sectionKey);
        if (is_array($section) && array_key_exists($key, $section)) {
            return  !empty($section[$key]);
        }
        return $defaultValue;
    }

    /**
     * Get array of values by section key
     *
     * @param $sectionKey
     * @return bool|mixed
     */
    public function getSection($sectionKey)
    {
        if (array_key_exists($sectionKey, $this->ini)) {
            return $this->ini[$sectionKey];
        }
        return false;
    }

    public function getList($sectionKey) {
        $section = $this->getSection($sectionKey);
        $result = array();
        foreach ($section as $item => $enabled) {
            if ($enabled) {
                $result[] = $item;
            }
        }
        return $result;
    }

}