<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/19/2015
 * Time: 6:59 AM
 */

namespace Tops\sys;


class TSession
{
    /**
     * @var string
     */


    // equiv to rand, mt_rand but more secure
    // returns int in *closed* interval [$min,$max]
    private static function devurandom_rand($min = 0, $max = 0x7FFFFFFF) {
        $diff = $max - $min;
        if ($diff < 0 || $diff > 0x7FFFFFFF) {
            throw new \RuntimeException("Bad range");
        }
        // todo: deprecated in 7.2 replace with random_bytes and test
        // $bytes = mcrypt_create_iv(4, MCRYPT_DEV_URANDOM);
        $bytes = random_bytes(4);
        if ($bytes === false || strlen($bytes) != 4) {
            throw new \RuntimeException("Unable to get 4 bytes");
        }
        $ary = unpack("Nint", $bytes);
        $val = $ary['int'] & 0x7FFFFFFF;   // 32-bit safe
        $fp = (float) $val / 2147483647.0; // convert to [0,1]
        return round($fp * $diff) + $min;
    }

    public static function Initialize()
    {
        // assume session started by another initialization routine, e.g. in Drupal
        if (!isset($_SESSION['tops'])) {
            $_SESSION['tops'] = array();
        }
        if (!isset($_SESSION['tops']['security-token'])) {
            $_SESSION['tops']['security-token'] = self::createToken();
        }

        try {
            $serverName = $_SERVER['SERVER_NAME'] ?? 'local.host';
            $options = str_starts_with($serverName, 'local.') ? [] : [
                'secure' => true,
                // do not set, 'httponly' => true, javascript need access.
               'samesite' => 'Strict',
            ];
            // $options = [];
            setcookie('peanutSecurity', $_SESSION['tops']['security-token'],$options);
        } catch (\Exception $ex) {
            // probably in test environment.
            $_COOKIE['peanutSecurity'] = $_SESSION['tops']['security-token'];
        }
    }
    private static function createToken($length = 64)
    {
        $length = $length + self::devurandom_rand(0,15);
        $result = '';
        for ($i=0;$i<$length;$i++) {
            $r = self::devurandom_rand(0,9);
            $result .= $r;
        }
        return uniqid($result);
    }

    public static function Set($key, $value)
    {
        if (!isset($_SESSION['tops'])) {
            self::Initialize();
        }
        $_SESSION['tops'][$key] = $value;
    }

    public static function GetSecurityToken() {
        if (!isset($_SESSION['tops'])) {
            self::Initialize();
        }
        return self::Get('security-token');
    }

    public static function Get($key) {
        if (isset($_SESSION['tops']) && array_key_exists($key, $_SESSION['tops'])) {
            return $_SESSION['tops'][$key];
        }
        return null;
    }

    public static function AuthenitcateSecurityToken($token)
    {
        if ($token===false) {
            // no authentication required
            return true;
        }
        $currentToken = self::Get('security-token');

        if (empty($currentToken)) {
            // no stored token means no authentication required.
            return true;
        }

        // todo: resolve sporatic token issue in chrome for multiple vms
        // if tokens have value compare them
        return ($token === $currentToken);
       // return true; // return true until session issues resolved
    }


}