<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/5/2017
 * Time: 6:01 AM
 */

namespace Tops\mail;


use Tops\sys\TConfiguration;

/**
 * NOT USED?
 */
class TMailConfiguration
{
    public static function GetSettings()
    {
        $result = new TMailSettings();
        $result->sendmail = TConfiguration::getValue('sendmail','mail',1);
        $result->debug = TConfiguration::getValue('debug', 'mail', 0);

        if ($result->sendmail == 'smtp') {
            //Set the hostname of the mail server
            $result->host = TConfiguration::getValue('host', 'mail', '127.0.0.1');
            //Set the SMTP port number - likely to be 25, 465 or 587
            $result->port = TConfiguration::getValue('port', 'mail', 25);
            //Whether to use SMTP authentication
            $auth = TConfiguration::getBoolean('auth', 'mail');
            if ($auth) {
                $result->auth = true;
                //Username to use for SMTP authentication
                $result->username = TConfiguration::getValue('username', 'mail', '');
                //Password to use for SMTP authentication
                $result->password = TConfiguration::getValue('password', 'mail', '');
            }
        }
        return $result;

    }

    public static function GetIniEmailValues($key, $sectionKey)
    {
        $result = array();
        $keys = TConfiguration::getValue($key, $sectionKey);
        if ($keys !== false) {
            $keys = explode(',', $keys);
            foreach ($keys as $key) {
                $key = trim($key);
                if (strstr($key, '@')) {
                    $email = $key;
                } else {
                    $email = TConfiguration::getValue($key, 'email');
                }
                if (!empty($email)) {
                    $result[] = $email;
                }
            }
        }
        return $result;
    }

}