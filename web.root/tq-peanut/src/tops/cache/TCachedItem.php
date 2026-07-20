<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/7/2015
 * Time: 6:14 AM
 */

namespace Tops\cache;




class TCachedItem {
    private $value = null;
    private $expirationTime;

    public static function Create($data, $duration=120) {
        $instance = new TCachedItem();
        $instance->value = $data;
        if (!$duration) {
            $instance->expirationTime = false;
        }
        else {
            $intervalSpec = is_numeric(($duration)) ?
                'PT'.$duration.'S' :
                'P'.$duration;
            $interval = new  \DateInterval($intervalSpec);
            $instance->expirationTime = new \DateTime();
            $instance->expirationTime->add($interval);
        }
        return $instance;
    }
    
    public static function Deserialize($item)
    {
        if (empty($item) || !isset($item->value) || !isset($item->expirationTime)) {
            return null;
        }
        $instance = new TCachedItem();
        $instance->value = $item->value;
        $instance->expirationTime = $item->expirationTime;
        return $instance;
    }
    
    public function Serialize() {
        $item = new \stdClass();
        $item->value = $this->value;
        $item->expirationTime = $this->expirationTime;
        return $item;
        
    }

    public function hasExpired() {
        if ($this->expirationTime) {
            return ($this->expirationTime < new \DateTime());
    }
        return false;
    }

    public function getValue() {
        return $this->value;
    }

}