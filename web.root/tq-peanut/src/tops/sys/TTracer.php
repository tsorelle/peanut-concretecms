<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/25/2019
 * Time: 7:54 AM
 */

namespace Tops\sys;


class TTracer
{
    private static $messages;
    private static $enabled;


    public static function Start() {
        self::$enabled = true;
    }

    public static function Stop() {
        self::$enabled = false;
    }

    public static function Enabled() {
        return !empty(self::$enabled);
    }

    public static function AddMessage(string $message)
    {
        if (self::$enabled) {
            if (!isset(self::$messages)) {
                self::$messages = [];
            }
            self::$messages[] = $message;
        }
    }

    public static function GetMessages() {
        return empty(self::$messages) ? [] : self::$messages;
    }

    public static function GetMesssageAsString($delimiter = ";\n") {
        if (isset(self::$messages)) {
            return count(self::$messages) == 0 ? 'No messsages' : join($delimiter,self::$messages);
        }
        else {
            return null;
        }
    }
    public static function Print($message,$arr=null): void
    {
        if (self::$enabled) {
            print "<pre>\n";
            print("$message\n");
            if ($arr) {
                print_r($arr);
            }
            print "</pre>\n";
            }
    }
}