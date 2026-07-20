<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/22/2018
 * Time: 10:19 AM
 */

namespace Tops\concrete5;


use Tops\db\TPdoQueryManager;

class Concrete5DataManager extends TPdoQueryManager
{

    private static $dbId = null;
    public static function setDatabaseId($value) {
        self::$dbId = $value;
    }

    protected function getDatabaseId()
    {
        return self::$dbId;
    }

    public function getBlockTypeData($blockType,$id) {
        $sanitized = preg_replace("/[^a-zA-Z0-9]+/", "", $blockType);
        if ($sanitized !== $blockType) {
            return false;
        }
        $stmt = $this->executeStatement("select * from bt$blockType where bID = ?",[$id]);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$result) {
            return false;
        }
        return $result;
    }
}