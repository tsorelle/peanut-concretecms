<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/3/2019
 * Time: 8:08 AM
 */

namespace Tops\concrete5;


use Tops\db\TPdoQueryManager;

class Concrete5Repository extends TPdoQueryManager
{

    protected function getDatabaseId()
    {
        return null;
    }

    public function getKnockoutViewData($id) {
        $sql = 'SELECT viewmodel, inputvalue  FROM btKnockoutView WHERE bID = ?';
        $stmt = $this->executeStatement($sql,[$id]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }
}