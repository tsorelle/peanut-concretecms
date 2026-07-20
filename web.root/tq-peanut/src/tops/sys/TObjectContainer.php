<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/30/2017
 * Time: 7:10 AM
 */

namespace Tops\sys;

/**
 * Class TObjectContainer
 * @package Tops\sys
 * Temporary "poor man's" dependency injection
 * Only supports parameterless singleton instances.
 */
class TObjectContainer
{
    /**
     * @var IObjectContainer
     */
    private static $container;

    public static function ClearCache() {
        self::$container = null;
    }

    private static function GetContainer()
    {
        if (!isset(self::$container)) {
            $className = TConfiguration::getValue('container','classes','\Tops\sys\TSimpleObjectContainer');
            self::$container = new $className();
        }
        return self::$container;
    }

    /**
     * @param $key
     * @return mixed
     *
     * Retrieve instance from the container.
     */
    public static function Get($key) {
        return self::GetContainer()->get($key);
    }

    /**
     * @param $id
     * @return bool
     */
    public static function HasDefinition($key) {
        return self::GetContainer()->hasDefinition($key);
    }
}