<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 4/5/2019
 * Time: 4:27 PM
 */

namespace Tops\sys;


/**
 * Class TEncryption
 * @package Tops\sys
 *
 * Based on example by
 * Nazmul Ahsan <n.mukto@gmail.com>
 * @link http://nazmulahsan.me/simple-two-way-function-encrypt-decrypt-string/
 *
 */
class TEncryption
{
    private $key;
    private $iv;
    private $encryptmethod = "AES-256-CBC";

    public function __construct($key,$iv)
    {
        $this->key = hash('sha256', $key);
        $this->iv = substr(hash('sha256', $iv), 0, 16);
    }

    /**
     * @param $string
     * @return string
     */
    public function encrypt($string) {
        return base64_encode(openssl_encrypt($string, $this->encryptmethod, $this->key, 0, $this->iv));
    }

    /**
     * @param $string
     * @return string
     */
    public function decrypt($string) {
        return openssl_decrypt(base64_decode($string), $this->encryptmethod, $this->key, 0, $this->iv);
    }
}