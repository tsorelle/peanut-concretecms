<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/15/2019
 * Time: 4:08 AM
 */

namespace Tops\db;


use PDO;
use PDOStatement;

/**
 * Class TQuery
 * @package Tops\db
 *
 * Use this class for ad hoc queries.  Prefer repositories when possible
 */
class TQuery
{
    /**
     * @var PDO
     */
    private $connection = null;
    private $lastErrorCode;

    public function getLastErrorCode() {
        return isset($this->lastErrorCode) ? $this->lastErrorCode : PDO::ERR_NONE;
    }

    public function __construct($databaseId=null)
    {
        $this->connection = TDatabase::getConnection($databaseId);
    }

    /**
     * @param $sql
     * @param array $params
     * @return PDOStatement
     */
    public function executeStatement($sql, $params = array())
    {
        unset($this->lastErrorCode);
        /**
         * @var PDOStatement
         */
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $this->lastErrorCode = $stmt->errorCode();
        return $stmt;
    }

    public function get($sql, $params = array()) {
        $stmt = $this->executeStatement($sql,$params);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return empty($result) ? false : $result;
    }

    public function getAll($sql, $params = array()) {
        $stmt = $this->executeStatement($sql,$params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getValue($sql, $params = array()) {
        $stmt = $this->executeStatement($sql,$params);
        return $stmt->fetch(PDO::FETCH_COLUMN);
    }


    public function getAllValues($sql, $params = array()) {
        $stmt = $this->executeStatement($sql,$params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function execute($sql, $params = array()) {
        $stmt = $this->executeStatement($sql,$params);
        return $stmt->rowCount();
    }

    public function insert($sql, $params = array()) {
        $stmt = $this->executeStatement($sql,$params);
        if ($this->lastErrorCode == PDO::ERR_NONE) {
            return $this->connection->lastInsertId();
        }
        return false;
    }


}