<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 8/15/2017
 * Time: 10:12 AM
 */

namespace Tops\concrete5;


use Tops\db\TConnectionManager;

class Concrete5ConnectionManager extends TConnectionManager
{

    public function getNativeConfiguration()
    {
        $configfile = \Tops\sys\TPath::getFileRoot().'application/config/database.php';
        $settings = include($configfile);
        $result = new \stdClass();
        $result->default = $settings['default-connection'];
        $result->connections = array();
        foreach ($settings['connections'] as $key => $properties) {
            $params = $this->makeParameterObject(
                $properties['database'],
                $properties['username'],
                $properties['password'],
                $properties['server']
            );

            $result->connections[$key] = $params;
        }
        return $result;
    }
}