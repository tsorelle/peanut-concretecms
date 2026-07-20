<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 5/16/2017
 * Time: 5:06 AM
 */

namespace Tops\services;


use Tops\sys\TStrings;

abstract class ServiceRequestInputHandler
{
    const securityTokenKey = 'topsSecurityToken';

    /**
     * @return 'POST' | 'GET'
     */
    protected abstract function getMethod();


    /**
     * @return mixed
     */
    public abstract function get($key);

    public abstract function getValues($exclude = []);

    public abstract function getSecurityToken();

    /**
     * Strips tags and optionally reduces string to specified length.
     *
     * @param string $string
     *
     * @return string
     */
    public function sanitize($string)
    {
        $text = trim(strip_tags($string));
        if ($text == null) {
            return ""; // we need to explicitly return a string otherwise some DB functions might insert this as a ZERO.
        }
        return $text;
    }

    public function getServiceId() {

        if ($this->getMethod() == 'POST') {
            $serviceId = $this->get('serviceCode');
        } else {
            $serviceId = $this->get('sid');
            $serviceId = $this->sanitize($serviceId);
        }
        return $serviceId;
    }

    public function getInput() {
        if ($this->getMethod() == 'POST') {
            $input = $this->get('request');
            $input = stripslashes($input);  // wordpress and others may add slashes.
            $input = json_decode($input);
        } else {
            $input = $this->get('arg');
            $input = $this->sanitize($input);
        }
        return $input;
    }

    public function getServiceNamespace($key)
    {
        $namespace = TStrings::formatNamespace($key);
        return $namespace."\\services";
    }



}