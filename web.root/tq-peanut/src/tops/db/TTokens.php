<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/25/2019
 * Time: 6:35 AM
 */

namespace Tops\db;


use Tops\sys\TIdentifier;

class TTokens
{
    const tokenTable = 'tops_tokens';
    /**
     * @var TQuery
     */
    private static $query;
    private static function getQuery()
    {
        if(!isset(self::$query)) {
            self::$query = new TQuery();
        }
        return self::$query;
    }

    public static function Create($prefix=null) {
        if (!$prefix) {
            $prefix = TIdentifier::NewId();
        }
        $value = $prefix.TIdentifier::NewId();
        self::getQuery()->execute('INSERT into '.self::tokenTable.'  (`value`) VALUES(?)',[$value]);
        return $value;
    }

    public static function Get($tokenValue,$expiration='1 DAY') {
        $sql = "SELECT `value` FROM `tops_tokens` WHERE value = ? AND CURRENT_TIMESTAMP() <= DATE_ADD(posted, INTERVAL $expiration)";
        return self::getQuery()->getValue($sql,[$tokenValue]);
    }

    public static function Check($tokenValue,$expiration='1 DAY') {
        $value = self::Get($tokenValue,$expiration);
        return !empty($value);
    }

}