<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/11/2019
 * Time: 11:47 AM
 */

namespace Tops\sys;


use Tops\sys\TObjectContainer;

abstract class TCmsEvents
{
    private static $enabled = true;
    public static function Handle($eventType, $eventCode, $argument=null) {
        if (self::$enabled) {
            /**
             * @var TCmsEvents
             */
            $handler = TObjectContainer::Get('tops.eventhandler.' . $eventType);
            if ($handler) {
                return $handler->handleEvent($eventCode, $argument);
            }
        }
        return false;
    }

    public static function Enable($enabled=true) {
        self::$enabled = $enabled;
    }

    /**
     * @param $eventCode
     * @param $argument
     * @return boolean
     */
    abstract function handleEvent($eventCode, $argument);

}