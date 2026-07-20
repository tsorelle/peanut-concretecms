<?php
/**
 * Created by /tools/create-model.php
 * Time:  2017-10-30 16:13:23
 */
namespace Peanut\PeanutTasks;


use \PDO;
use PDOStatement;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;

class TaskLogRepository extends \Tops\db\TEntityRepository
{
    /**
     * @param $logDate
     * @param int $maxentries
     * @return TaskLogEntry[]
     */
    public function getLatest($logDate,$maxentries=0) {
        return [];
    }

    public function truncateEntries($timeFrame = '-2 months') {
        $date = new \DateTime();
        $date->modify($timeFrame);
        $dateStr =  $date->format('Y-m-d H:i:s');
        $sql = "delete from ".$this->getTableName().' WHERE time < ?' ;
        $stmt = $this->executeStatement($sql, [$dateStr]);
        $lastErrorCode = $stmt->errorCode();
        if ($lastErrorCode == PDO::ERR_NONE) {
            return $stmt->rowCount();
        }
        return -1;
    }

    public function getLogEntries($taskname = null, $offset=0,$limit=50) {
        $sql=
            "SELECT id, taskname,`time`, ".
            "    CASE `type`  ".
            "        WHEN 0 THEN 'Message' ".
            "        WHEN 1 THEN 'Error'".
            "        WHEN 2 THEN 'Warning'".
            "        WHEN 100 THEN 'Start'".
            "        WHEN 200 THEN 'Failed'".
            "        WHEN 999 THEN 'Completed'".
            "    ELSE 'unknown' ".
            "    END AS `type`, message ".
            " FROM %s ";
        if ($taskname) {
            $sql .= 'WHERE taskname = ?';
            $params = [$taskname];
        }
        else {
            $params = [];
        }
        $sql .=
            " ORDER BY id DESC  ".
            " LIMIT %d OFFSET %d ";

        $sql= sprintf( $sql,
            $this->getTableName(),
            $limit,
            $offset);

        $stmt = $this->executeStatement($sql,$params);
        return $stmt->fetchAll(PDO::FETCH_CLASS,$this->getClassName());
    }

    /**
     * @param $taskname
     * @return TaskLogEntry
     */
    public function getLastEntry($taskname) {
        return $this->getSingleInstance('taskname = ? ',[$taskname],' ORDER BY id DESC LIMIT 1');
	// todo: check if this change needed
	// return $this->getSingleEntity('taskname = ? ORDER BY id DESC LIMIT 1',[$taskname]);
    }

    protected function getTableName() {
        return 'tops_tasklog';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\PeanutTasks\TaskLogEntry';
    }

    protected function getFieldDefinitionList()
    {
        return array(
            'id'=>PDO::PARAM_INT,
            'time'=>PDO::PARAM_STR,
            'type'=>PDO::PARAM_INT,
            'message'=>PDO::PARAM_STR,
            'taskname'=>PDO::PARAM_STR,
            'active' => PDO::PARAM_STR);
    }
}