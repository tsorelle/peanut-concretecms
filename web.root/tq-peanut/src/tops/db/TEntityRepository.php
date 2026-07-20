<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 8/16/2017
 * Time: 6:42 AM
 */

namespace Tops\db;

use \PDO;
use PDOStatement;
use Tops\sys\TIdentifier;

abstract class TEntityRepository extends TPdoQueryManager implements IEntityRepository
{

    private $fieldDefinitions;

    /**
     * Subclasses return a list that defines the data structure.
     * You can use "ModelBuilder" to generate this function for a given table.
     */
    protected abstract function getFieldDefinitionList();

    /**
     * return full class name for associated entity class.  Used to instatiate objects returned
     * by queries. You can use "ModelBuilder" to generate this function for a sub-class.
     */
    protected abstract function getClassName();

    /**
     * return name of associated table
     * You can use "ModelBuilder" to generate this function for a sub-class.
     */
    protected abstract function getTableName();

    private function getFieldDefinitions()
    {
        if (!isset($this->fieldDefinitions)) {
            $this->fieldDefinitions = $this->getFieldDefinitionList();
        }
        return $this->fieldDefinitions;
    }

    /**
     * Name of the primary key of other unique lookup field.
     * Almost always 'id' but can be overridden
     */
    protected function getLookupField()
    {
        return 'id';
    }

    private $lastErrorCode = PDO::ERR_NONE;
    public function getLastErrorCode() {
        return $this->lastErrorCode;
    }

    /**
     * @param $id
     * Return single instance of the associated Entity class selected by id
     */
    public function get($id)
    {
        return $this->getSingleEntity('id = ?', [$id], true);
    }


    /**
     * @param $where
     * @param $params
     * @param $includeInactive
     * @param $orderAndLimit
     * @return PDOStatement
     *
     * Execute a select query returning a collection of instances of the associated entity class.
     * The "fetch" instruction to retrieve the result must be invoked by the calling method
     */
    protected function executeEntityQuery($where, $params, $includeInactive = false,$orderAndLimit = '')
    {
        $sql = $this->addSqlConditionals(
            'SELECT * ' . 'FROM ' . $this->getTableName(),
            $includeInactive,
            $where
        );
        if ($orderAndLimit) {
            $sql .= ' '.$orderAndLimit;
        }
        $stmt = $this->executeStatement($sql, $params);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $stmt->setFetchMode(PDO::FETCH_CLASS, $this->getClassName());
        return $stmt;
    }

    /**
     * @param array $ids
     * @param $condition
     * @param $delete
     * @return PDOStatement|void
     *
     * Remove records per the SQL condition.
     */
    public function filterRecordSet(array $ids,$condition='',$delete=false) {
        $count = @count($ids);
        if ($count > 0) {
            $set = '';
            for ($i=0;$i<$count;$i++)
                foreach ($ids as $id) {
                    if ($i>0) {
                        $set .= ',';
                    }
                    $set .= $id;
                }
            if ($condition) {
                $condition = " AND ($condition)";
            }

            $sql = $delete ?
                sprintf("DELETE FROM %s WHERE (id NOT IN (%s)) ",$this->getTableName(),$set).$condition :
                sprintf('UPDATE %s SET active=0 WHERE (id NOT IN (%s)) ',$this->getTableName(),$set).$condition;
            return $this->executeStatement($sql);
        }
    }

    /**
     * @param array $entities
     * @param $condition
     * @param $delete
     * @return PDOStatement|null
     *
     * Perform filterRecordset based on an entity collection
     */
    public function filterEntities(array $entities,$condition='',$delete=false) {
        $ids = [];
        foreach ($entities as $entity) {
            $ids[] = $entity->id;
        }
        return $this->filterRecordSet($ids,$condition,$delete);
    }

    /**
     * @param $where - a conditional like id = ? and date > current_date()
     * @param $params - array of replaceable parameters
     * @param $clauses - clauses to be appended to the query like 'ORDER BY postdate DEC'
     * @param $includeInactive - true to include where active = 1
     * return single entity instance
     *
     * This replaces getSingleEntity, when clauses are to be included.
     * Did not want to mess with getSingleEntity for backward compatibility reasons
     */
    public function getSingleInstance($where, $params, $clauses='', $includeInactive = false)
    {
        $sql = $this->addSqlConditionals(
            'SELECT * ' . 'FROM ' . $this->getTableName(),
            $includeInactive,
            $where, $clauses
        );
        $stmt = $this->executeStatement($sql, $params);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $stmt->setFetchMode(PDO::FETCH_CLASS, $this->getClassName());
        return $stmt->fetch();
    }


    public function getRecordCount($condition,$params, $includeInactive=false) {
        $sql = 'SELECT COUNT(*) FROM '.$this->getTableName().' WHERE '.$condition;
        if (!$includeInactive) {
            $sql .= ' AND active = 1';
        }
        $stmt = $this->executeStatement($sql,$params);
        $result = $stmt->fetch(PDO::FETCH_COLUMN);
        return ($result ?? 0);
    }

    // todo: add clauses to this call and test
    public function getCount($includeInactive=false, $where='', $clauses='') {
        $sql = $this->addSqlConditionals(
            'SELECT COUNT(*) FROM '.$this->getTableName(),
            $includeInactive,
            $where
        );
        $stmt = $this->executeStatement($sql);
        $result = $stmt->fetch();
        return (empty($result) ?  0 : $result[0]);
    }

    public function addSqlConditionals($sql, $includeInactive, $where, $clauses='')
    {
        $activeOnly = (
            (!$includeInactive)
            && array_key_exists('active', $this->getFieldDefinitionList())
            && strpos($where, 'active=') === false
        );

        if ($activeOnly) {
            $sql .= ' WHERE active=1 ';
        }

        if (!empty($where)) {
            $sql .= $activeOnly ? ' AND (' . $where . ')' : ' WHERE ' . $where;
        }

        if (!empty($clauses)) {
            $sql = "$sql $clauses";
        }
        return $sql;
    }

    /**
     * @param $where
     * @param $params
     * @param $includeInactive
     * @return mixed
     *
     * Return a single instance of associated entity class based on SQL conditionals and parameters
     */
    public function getSingleEntity($where, $params, $includeInactive = false)
    {
        $stmt = $this->executeEntityQuery($where, $params, $includeInactive);
        return $stmt->fetch();
    }

    /**
     * @param $where
     * @param $params
     * @param $includeInactive
     * @return array|false
     * Return a collection of instances of the associated entity class based on SQL conditionals and parameters
     */
    public function getEntityCollection($where, $params, $includeInactive = false, $clauses='')
    {
        $stmt = $this->executeEntityQuery($where, $params, $includeInactive,$clauses);
        $result = $stmt->fetchAll();
        if (empty($result)) {
            return false;
        }
        return $result;
    }

    public function updateValues($id, array $fields, $userName = 'admin')
    {
        $dbh = $this->getConnection();
        $sql = array('UPDATE ' . $this->getTableName() . ' SET');
        $names = array_keys($fields);
        $lastField = sizeof($fields) - 1;
        for ($i = 0; $i <= $lastField; $i++) {
            $name = $names[$i];
            $sql[] = "$name = :$name" . ($i == $lastField ? '' : ',');
        }
        $sql[] = " WHERE id = :id";

        $today = new \DateTime();
        $date = $today->format('Y-m-d H:i:s');

        /**
         * @var PDOStatement
         */
        $stmt = $dbh->prepare(join("\n", $sql));
        $fieldDefinitions = $this->getFieldDefinitions();
        foreach ($fields as $name => $value) {
            switch ($name) {
                case 'uid':
                    // ignore
                    break;
                case 'createdon':
                    // ignore
                    break;
                case 'createdby':
                    // ignore
                    break;
                case 'changedby':
                    $stmt->bindValue(":$name", $userName, $fieldDefinitions[$name]);
                    break;
                case 'changedon':
                    $stmt->bindValue(":$name", $date, $fieldDefinitions[$name]);
                    break;
                default:
                    $stmt->bindValue(":$name", $value, $fieldDefinitions[$name]);
                    break;
            }
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        // $count = $stmt->execute();
        $stmt->execute();
        $this->lastErrorCode = $dbh->errorCode();
        $result = $stmt->rowCount();
        return $result;
    }

    public function executeUpdateStatement($sql,$parameters = []) {
        $dbh = $this->getConnection();
        $stmt = $this->executeStatement($sql,$parameters);
        $this->lastErrorCode = $dbh->errorCode();
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * @param $dto
     * @param $userName
     * @return int
     *
     * Update table based on entity object
     */
    public function update($dto, $userName = 'admin')
    {
        $updateValues = array();
        $fieldNames =  array_keys($this->getFieldDefinitions());
        foreach ($dto as $name => $value) {
            if ($name != 'id' && $name != 'uid' && $name != 'createdby' && $name != 'createdon' && in_array($name,$fieldNames)) {
                $updateValues[$name] = $value;
            }
        }
        return $this->updateValues($dto->id, $updateValues, $userName);
    }

    /**
     * @param $dto
     * @param $userName
     * @return false|string
     *
     * Insert values from entity object
     */
    public function insert($dto, $userName = 'admin')
    {
        $dbh = $this->getConnection();
        $sql = array('INSERT ' . 'INTO ' . $this->getTableName() . ' ( ');
        $fieldDefinitions = $this->getFieldDefinitions();
        $fieldNames = array_keys($fieldDefinitions);
        array_shift($fieldNames); //remove id
        $valuesList = array();
        $lastField = sizeof($fieldNames);
        for ($i = 0; $i < $lastField; $i++) {
            $valuesList[] = ':' . $fieldNames[$i];
        }

        $sql = 'INSERT ' . 'INTO ' . $this->getTableName() . '(  '
            . join(',', $fieldNames)
            . ")\n VALUES ( "
            . join(',', $valuesList)
            . ')';

        $today = new \DateTime();
        $date = $today->format('Y-m-d H:i:s');
        $bound = [];

        /**
         * @var PDOStatement
         */
        $stmt = $dbh->prepare($sql);
        foreach ($dto as $name => $value) {
            switch ($name) {
                case 'id':
                    //ignore
                    break;
                case 'uid' :
                    $uid = TIdentifier::NewId();
                    $stmt->bindValue(":$name", $uid, PDO::PARAM_STR);
                    break;
                case 'createdon':
                    $stmt->bindValue(":$name", $date, PDO::PARAM_STR);
                    break;
                case 'createdby':
                    $stmt->bindValue(":$name", $userName, PDO::PARAM_STR);
                    break;
                case 'changedby':
                    $stmt->bindValue(":$name", $userName, $fieldDefinitions[$name]);
                    break;
                case 'changedon':
                    $stmt->bindValue(":$name", $date, $fieldDefinitions[$name]);
                    break;
                default:
                    // ignore dto properties not in field list
                    if (in_array($name, $fieldNames)) {
                        $bound[] = $name;
                        $stmt->bindValue(":$name", $value, $fieldDefinitions[$name]);
                    }
                    break;
            }
        }

        // $count =
        $stmt->execute();
        $this->lastErrorCode = $stmt->errorCode();
        if ($this->lastErrorCode == PDO::ERR_NONE) {
            return $dbh->lastInsertId();
        }
        return false;
    }

    /**
     * @param $includeInactive
     * @param $orderField
     * @return array
     *
     * Return all recordss as instances of the associated entity class
     */
    public function getAll($includeInactive = false,$orderField=false)
    {
        $dbh = $this->getConnection();
        $sql = 'SELECT * FROM ' . $this->getTableName();
        if (!$includeInactive && array_key_exists('active', $this->getFieldDefinitionList())) {
            $sql .= ' WHERE active=1';
        }
        if ($orderField) {
            $sql .= " ORDER BY `$orderField`";
        }
        /**
         * @var PDOStatement
         */
        $stmt = $dbh->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_CLASS, $this->getClassName());
        return $result;
    }
    public function getAllSorted($orderField)
    {
        return $this->getAll(false,$orderField);
    }


    /**
     * @param $id
     * @return false|int
     *
     * Delete record by id
     */
    public function delete($id)
    {
        $dbh = $this->getConnection();
        $sql = 'DELETE FROM ' . $this->getTableName() . ' WHERE id = ?';
        /**
         * @var PDOStatement
         */
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute(array($id));
        $this->lastErrorCode = $stmt->errorCode();
        if ($this->lastErrorCode == PDO::ERR_NONE) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @param $filterCondition
     * @return false|int
     *
     * Delete record(s_ by foreigh key
     */
    public function deleteByForeignKey($key,$value,$filterCondition = null) {
        $dbh = $this->getConnection();
        $sql = "DELETE FROM ".$this->getTableName()." WHERE $key = ?";
        if ($filterCondition) {
            $sql .= " AND ($filterCondition)";
        }
        $stmt = $this->executeStatement($sql,[$value]);
        $this->lastErrorCode = $stmt->errorCode();
        if ($this->lastErrorCode == PDO::ERR_NONE) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * @param $id
     * @return int
     *
     * Set 'active flag' to false
     */
    public function remove($id)
    {
        $dbh = $this->getConnection();
        return $this->updateValues($id, array('active' => 0));
    }

    /**
     * @param $id
     * @return int
     *
     * Change active flag to true
     */
    public function restore($id)
    {
        $dbh = $this->getConnection();
        return $this->updateValues($id, array('active' => 1));
    }

    /**
     * @param $value
     * @param $includeInactive
     * @param $fieldName
     * @return mixed
     *
     * Return single instance of associated entity by id or other key
     */
    public function getEntity($value, $includeInactive = false, $fieldName = null)
    {
        if ($fieldName === null) {
            $fieldName = $this->getLookupField();
        }
        return $this->getSingleEntity("$fieldName = ?", [$value], $includeInactive);
    }

    /**
     * @param $value
     * @param $includeInactive
     * @return false|mixed
     *
     * Return single instance of associated entity by globally unique id
     */
    public function getEntityByUid($value, $includeInactive = false)
    {
        $fieldDefinitions = $this->getFieldDefinitions();
        if (array_key_exists('uid',$fieldDefinitions) && TIdentifier::IsValid($value)) {
            return $this->getSingleEntity("uid = ?", [$value], $includeInactive);
        }
        return false;
    }

    /**
     * @param $value
     * @return false|mixed
     *
     * Get id for unique id field
     */
    public function getIdForUid($value)
    {
        if (TIdentifier::IsValid($value)) {
            return $this->getIdForFieldValue('uid',$value);
        }
        return false;
    }

    /**
     * @param $value
     * @return false|mixed
     *
     * Get id for unique code field
     */
    public function getIdForCode($value)
    {
        return $this->getIdForFieldValue('code', $value);
    }

    public function getIdForFieldValue($fieldName,$value) {
        $fieldDefinitions = $this->getFieldDefinitions();
        if (array_key_exists($fieldName,$fieldDefinitions)) {
            $sql = 'SELECT id FROM '.$this->getTableName()." WHERE $fieldName = ?";
            return $this->getValue($sql, [$value]);
        }
        return false;
    }

    /**
     * @param $fieldName
     * @param $id
     * @return mixed
     *
     * Return value of one field for id
     */
    public function getFieldValue($fieldName,$id) {
        $sql = "SELECT $fieldName FROM ".$this->getTableName().' WHERE id=?';
        $stmt = $this->executeStatement($sql,[$id]);
        return $stmt->fetch(PDO::FETCH_COLUMN);
    }

    public function getEntityClassName() {
        return $this->getClassName();
    }

    public function getColumnValues($columName,$condition=null,$params = [],$activeOnly = true) {
        $sql = "SELECT $columName FROM ".$this->getTableName();

        if ($condition) {
            $sql .= " WHERE ($condition) ";
        }
        if ($activeOnly) {
            $sql .= ($condition ? ' AND ' : ' WHERE ').' active=1';
        }
        $stmt = $this->executeStatement($sql,$params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param $condition
     * @param $params
     * @param $activeOnly
     * @return \stdClass (not entity object)
     */
    public function getData($condition,$params,$activeOnly=true) {
        $sql = "SELECT * FROM ".$this->getTableName();
        if ($condition) {
            $sql .= " WHERE ($condition) ";
        }
        if ($activeOnly) {
            $sql .= ($condition ? ' AND ' : ' WHERE ').' active=1';
        }
        $stmt = $this->executeStatement($sql,$params);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getEmptyDTO() {
        $result = new \stdClass();
        $fields = $this->getFieldDefinitionList();
        foreach ($fields as $name => $type) {
            if ($name == 'id') {
                $value = 0;
            }
            else {
                $value = $type == PDO::PARAM_STR ? '' : null;;
            }
            $result->$name = $type == PDO::PARAM_STR ? '' : null;

        }
        return $result;
    }

    public function newEntity() {
        $className = $this->getClassName();
        return new $className();
    }
}