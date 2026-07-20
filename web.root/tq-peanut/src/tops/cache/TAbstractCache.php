<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/7/2015
 * Time: 7:16 AM
 */

namespace Tops\cache;


abstract class TAbstractCache implements ITopsCache {

    /**
     * @param $key
     * @return TCachedItem
     */
    abstract function GetCachedItem($keys);

    /**
     * @param $key string
     * @param $value TCachedItem
     */
    abstract function SetCachedItem($keys,TCachedItem $item);

    abstract function FlushCachedItems($category=null);

    abstract function DeleteCachedItem($keys);


    private function parseKey($key) {
        if (strstr($key,'.')) {
            return explode('.',$key);
        }
        return array('default',$key);
    }

    public function Get($key)
    {
        $keys = $this->parseKey($key);
        // $this->FlushCachedItems();

        $result = $this->GetCachedItem($keys);

        if ($result) {
            if ($result->hasExpired($result)) {
                $this->DeleteCachedItem($keys);
            }
            else {
                return $result->getValue();
            }
        }
        return null;
    }


    public function Remove($key)
    {
        $keys = $this->parseKey($key);
        $this->DeleteCachedItem($keys);
    }

    public function Set($key, $value, $duration = null)
    {
        $keys = $this->parseKey($key);
        if ($value === null) {
            $this->DeleteCachedItem($keys);
        }
        else {
            $item = TCachedItem::Create($value, $duration);
            $this->SetCachedItem($keys, $item);
        }
    }

    public function Flush($category = null)
    {
        $this->FlushCachedItems($category);
    }
}