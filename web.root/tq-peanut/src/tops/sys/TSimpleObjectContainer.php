<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 7/3/2017
 * Time: 5:52 AM
 */

namespace Tops\sys;


class TSimpleObjectContainer implements IObjectContainer
{
    private $instances = array();
    private $classes = array();
    private $config = array();

    public function __construct()
    {
        $configPath = TPath::getConfigPath()."classes.ini";
        if (file_exists($configPath)) {
            $this->config = parse_ini_file($configPath, true);
        }
        else {
            $this->config = array();
        }
    }

    public function get($key)
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        };

        if (isset($this->classes[$key])) {
            $className = $this->classes[$key];
            $isSingleton = false;
        } else {
            if (empty($this->config[$key]['type'])) {
                return false;
            }
            $section = $this->config[$key];
            $className = $section['type'];
            $this->classes[$key] = $className;
            $isSingleton = !empty($section['singleton']);
        }

        $instance = new $className();
        if ($isSingleton) {
            $this->instances[$key] = $instance;
        }
        return $instance;
    }

    public function hasDefinition($key)
    {
        return !empty($this->config[$key]['type']);
    }
}