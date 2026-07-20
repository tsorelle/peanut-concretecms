<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 6/25/2018
 * Time: 9:58 AM
 */

namespace Tops\db;


use PDO;
// note: currently not used. needs testing
class TEntitySearch extends TPdoQueryManager
{
    private $dbId = null;
    private $entityCode;
    private $tableName;
    private $columns = [];
    private $joins = [
        'JOIN tops_entity_property_values v ON e.id = v.instanceId',
        'JOIN tops_entity_properties p ON v.entityPropertyId = p.id'
    ];
    private $filters = [];
    private $where = '';
    private $whereValues = [];
    private $order = '';
    private $sql = null;
    private $responseClass = null;

    public function __construct($entityCode, $tableName=null, $dbId = null)
    {
        $this->entityCode = $entityCode;
        $this->tableName = $tableName;
        $this->dbId = $dbId;
    }

    protected function getDatabaseId()
    {
        return $this->dbId;
    }

    public function addFilters(array $filters) {
        foreach ($filters as $key => $value) {
            $this->filters[$key] = $value;
        }
    }

    public function setFilterValue($key,$value) {
        $this->filters[$key] = $value;
    }

    public function setWhereClause($clause,array $values=[]) {
        $this->where = $clause;
        $this->whereValues;
    }
    public function setOrderClause($clause) {
        $this->order = $clause;
    }
    public function addColumn($column) {
        $this->columns[] = $column;
    }
    public function addJoin($clause) {
        $this->joins[] = $clause;
    }

    private function buildSql() {
        $this->sql = 'SELECT DISTINCT v.instanceId as id';
        if (!empty($this->columns)) {
            $this->sql .= ','.implode(',',$this->columns);
        }
        $this->sql .= "\nFROM $this->tableName e\n";
        if (!empty($this->joins)) {
            $this->sql .=  implode("\n",$this->joins)."\n";
        }
        $this->sql .=  "WHERE p.entityCode = ?\n";
        if (!empty($this->filters)) {
            $filterConditions = [];
            foreach (array_keys($this->filters) as $key) {
                $filterConditions[] = '(p.key = ? AND v.value = ?) ';
            }
            $this->sql .= "AND (\n".implode("OR \n",$filterConditions).")\n";
        }
        if (!empty($this->where)) {
            $this->sql .= "AND ($this->where)\n";
        }
        if (!empty($this->order)) {
            $this->sql .= "ORDER BY $this->order\n";
        }
    }


    public function execute($responseClass = null) {
        $parameters = [$this->entityCode];
        foreach ($this->filters as $key => $value) {
            $parameters[] = $key;
            $parameters[] = $value;
        }
        $parameters = array_merge($parameters,$this->whereValues);

        if (empty($this->sql)) {
            $this->buildSql();
        }

        $stmt = $this->executeStatement($this->sql, $parameters);
        if (empty($responseClass)) {
            $stmt->setFetchMode(PDO::FETCH_OBJ);
        }
        else {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $stmt->setFetchMode(PDO::FETCH_CLASS, $responseClass);
        }
        return $stmt->fetchAll();
    }
}