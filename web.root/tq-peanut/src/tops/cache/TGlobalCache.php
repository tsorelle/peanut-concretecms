<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/7/2015
 * Time: 7:34 AM
 */

namespace Tops\cache;


class TGlobalCache extends TAbstractCache {

    const cacheKey = 'tops-global-cache';

    function __construct() {
        if (!array_key_exists(self::cacheKey,$GLOBALS)) {
            $GLOBALS[self::cacheKey] = array();
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
        $cache = $GLOBALS[self::cacheKey];
        if (isset($cache[$category][$key])) {
            $data = $cache[$category][$key];
            if (empty($data)) {
                return null;
            }
            return TCachedItem::Deserialize($data);
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
        if (!array_key_exists($category,$GLOBALS[self::cacheKey])) {
            $GLOBALS[self::cacheKey][$category] = array();
        }
        $GLOBALS[self::cacheKey][$category][$key] = $item->Serialize();
    }

    function FlushCachedItems($category = null)
    {
        if ($category) {
            $GLOBALS[self::cacheKey][$category] = array();
        }
        else {
            $GLOBALS[self::cacheKey] = array();
        }
    }

    function DeleteCachedItem($keys)
    {
        $category = $keys[0];
        $key = $keys[1];
        unset($GLOBALS[self::cacheKey][$category][$key]);
    }
}