<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/15/2018
 * Time: 8:50 AM
 */

namespace Tops\db;


use PDO;
use PDOStatement;

abstract class TPdoQueryManager
{
    protected abstract function getDatabaseId();

    /**
     * @var PDO
     */
    private $connection = null;

    protected function getConnection()
    {
        if ($this->connection != null) {
            return $this->connection;
        }
        return TDatabase::getConnection($this->getDatabaseId());
    }

    /**
     * Prepare and execute a PDO statement
     * @param $sql
     * @param array $params
     * @return PDOStatement
     */
    protected function executeStatement($sql, $params = array())
    {
        $dbh = $this->getConnection();
        /**
         * @var PDOStatement
         */
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Execute a PDO statememt to return a single numeric value
     * @param $sql
     * @param $params
     * @return false|mixed
     */
    public function getValue($sql, $params = array()) {
        $stmt = $this->executeStatement($sql,$params);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        return empty($result) ? false : $result[0];
    }

    /**
     * Begin a transaction
     * @return void
     */
    public function startTransaction()
    {
        $this->connection = TDatabase::getPersistentConnection($this->getDatabaseId());
        $this->connection->beginTransaction();
    }

    /**
     * End a transaction
     * @return void
     */
    public function commitTransaction()
    {
        if ($this->connection != null) {
            $this->connection->commit();
            $this->connection = null;
        }
    }

    /**
     * Roll back a transaction
     * @return void
     */
    public function rollbackTransaction()
    {
        if ($this->connection != null) {
            $this->connection->rollBack();
            $this->connection = null;
        }
    }



}