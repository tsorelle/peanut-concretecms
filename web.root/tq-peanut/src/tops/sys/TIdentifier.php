<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/6/2018
 * Time: 6:52 AM
 */

namespace Tops\sys;


/**
 * Class TIdentifier
 * @package Tops\sys
 *
 * Generates and validates random UUID version 4 identifiers
 *
 */
class TIdentifier
{
    /**
     * Validates UUID version 4
     *
     * @param $uuid
     * @return bool
     */
    public static function IsValid($uuid,$acceptBlank = false) {
        if ($acceptBlank && ($uuid===null || $uuid === '')) {
            return true;
        }
        preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/',$uuid,$matches);
        $match = array_shift($matches);
        if ($match === $uuid) {
            return ctype_xdigit(str_replace('-','',$uuid));
        };
        return false;
    }

    /**
     * In theory these are neither garanteed unique nor cryptographically secure.
     * They do have an extremely high probablility of uniqueness so should be reasonably safe to use in low risk environments.
     * In a database application uniqueness over the table should be ensured using a unique index.
     *
     * @return string
     */
    public static function NewId() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}