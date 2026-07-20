<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/23/2015
 * Time: 5:58 AM
 */

namespace Tops\mail;


class TEmailAddress {
    private $name;
    private $address;

    public function __construct($address, $name='') {
        $this->address = $address;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    public function __toString() {
        return $this->toString(false);
    }

    public function toString($noQuotes=true)
    {
        if (empty($this->address)) {
            return '';
        }
        if (empty($this->name)) {
            return $this->address;
        }
        return  sprintf($noQuotes ?  '%s <%s>' : '"%s" <%s>',
            $this->name,$this->address);
    }

    public function toArray() {
        $list = array();
        $name = $this->name;
        if (empty($name)) {
            array_push($list,$this->address);
        }
        else {
            $list[$this->address] = $name;
        }
        return $list;
    }

    public static function FromString($emailAddress) {
        $emailAddress = trim($emailAddress);
        if (substr($emailAddress,-1) == '>') {
            $parts = explode('<',$emailAddress);
            if (sizeof($parts) == 2) {
                return new TEmailAddress(
                    trim(str_replace('>','',$parts[1])),
                    trim(str_replace('"','',$parts[0]))
                );
            }
            return null;
        }

        return new TEmailAddress($emailAddress);
    }

    public static function Create($emailAddress,$name = null) {
        if ($name !== null) {
            return new TEmailAddress($emailAddress,$name);
        }
        if (is_string($emailAddress)) {
            return self::FromString($emailAddress);
        }
        return $emailAddress;
    }
}