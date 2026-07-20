<?php

namespace Peanut\bootstrap;

class PathFinder
{
    public static function stripDriveLetter($path)
    {
        $path = str_replace('\\', '/', $path);
        $p = strpos($path, ':');
        if ($p === 1) {
            return strlen($path) < 3 ? '' : substr($path, 2);
        }
        return $path;

    }

    public static function fixSlashes($path)
    {
        $path = str_replace('\\', '/', $path);
        while (strpos($path, '//') !== false) {
            $path = str_replace('//', '/', $path);
        }
        return trim($path);
    }

    public static function normalize($path,$testPathExists=true)
    {
        $path = self::fixSlashes($path);

        if ($testPathExists) {
            $path = realpath($path);
            if ($path === false) {
                return false;
            }
        }
        return self::stripDriveLetter($path);
    }

    /**
     *  Convert a file system path to a URL (protocol omitted)
     * @param $path
     * @return array|false|string|string[]
     * @throws \Exception
     */
    public static function ToUrl($path) {
        if (!defined('DIR_ROOT')) {
            throw new \Exception('DIR_ROOT not defined');
        }
        $root = DIR_ROOT;
        if (str_starts_with($path,$root.'/')) {
            $path = substr($path,strlen($root));
        }
        $path = self::normalize($path,false);
        if (str_ends_with($path,'/')) {
            $path = substr($path,0,strlen($path)-1);
        }
        if (!str_starts_with($path,'/')) {
            $path = "/$path";
        }
        if (is_dir($root.$path)) {
            return $path;
        }
        return false;
    }
    /**
     * @throws \Exception
     */
    public static function getDocumentRoot(): string
    {
        if (defined('DIR_ROOT')) {
            $baseDir = self::normalize(DIR_ROOT);
        } else {
            $path = $_SERVER['DOCUMENT_ROOT'] ?? null;
            if (empty($path)) {
                $path = __DIR__;
                while ($path !== false) {
                    if (file_exists("$path/index.php")) {
                        break; // return $path;
                    }
                    $path = realpath($path . '/..');
                }
            }
            $baseDir = self::normalize($path);
        }

        if (empty($baseDir)) {
            throw new \Exception("Unable to determine document root");
        }
        return $baseDir;
    }

}