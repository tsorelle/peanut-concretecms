<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/7/2015
 * Time: 9:34 AM
 */

namespace Tops\cache;


class TSessionCache extends TAbstractCache {

    const cacheKey = 'tops-session-cache';

    function __construct() {
        if (!array_key_exists(self::cacheKey,$_SESSION)) {
            $_SESSION[self::cacheKey] = array();
        }
    }


    /**
     * @param $keys[]
     * @return TCachedItem
     */
    function GetCachedItem($keys)
    {
        $category = $keys[0];
        $key = $keys[1];
        if (array_key_exists(self::cacheKey, $_SESSION)) {
            $cache = $_SESSION[self::cacheKey];
            if (isset($cache[$category][$key])) {
                $data = $cache[$category][$key];
                if (empty($data)) {
                    return null;
                }
                return TCachedItem::Deserialize($data);
            }
        }
        return null;
    }

    /**
     * @param $keys string[]
     * @param $value TCachedItem
     */
    function SetCachedItem($keys, TCachedItem $item)
    {
        $category = $keys[0];
        $key = $keys[1];
        if (!array_key_exists($category,$_SESSION[self::cacheKey])) {
            $_SESSION[self::cacheKey][$category] = array();
        }
        $_SESSION[self::cacheKey][$category][$key] = $item->Serialize();
    }

    function FlushCachedItems($category = null)
    {
        if ($category) {
            $_SESSION[self::cacheKey][$category] = array();
        }
        else {
            $_SESSION[self::cacheKey] = array();
        }
    }

    function DeleteCachedItem($keys)
    {
        $category = $keys[0];
        $key = $keys[1];
        unset($_SESSION[self::cacheKey][$category][$key]);
    }
}