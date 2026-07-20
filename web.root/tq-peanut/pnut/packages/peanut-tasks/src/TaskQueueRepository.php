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

class TaskQueueRepository extends \Tops\db\TEntityRepository
{
    /**
     * @return TaskQueueEntry[]
     */
    public function getCurrent() {

        $collection = $this->getEntityCollection(
            '(startdate is NULL OR startdate <= CURRENT_DATE) AND (enddate IS NULL OR enddate >= CURRENT_DATE)',[]
        );
        return empty($collection) ? [] : $collection;

    }

    protected function getTableName() {
        return 'tops_taskqueue';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\PeanutTasks\TaskQueueEntry';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'frequency'=>PDO::PARAM_STR,
        'intervalType' => PDO::PARAM_INT,
        'taskname'=>PDO::PARAM_STR,
        'namespace'=>PDO::PARAM_STR,
        'startdate'=>PDO::PARAM_STR,
        'enddate'=>PDO::PARAM_STR,
        'inputs'=>PDO::PARAM_STR,
        'comments'=>PDO::PARAM_STR,
        'active'=>PDO::PARAM_STR);
    }

    public function getTaskQueueList() {
        $sql =
            "SELECT CASE".
            "	WHEN intervalType = 1 THEN 'On demand'".
            "	WHEN intervalType = 2 THEN 'Every'    ".
            "	WHEN intervalType = 3 THEN 'Weeky'    ".
            "	WHEN intervalType = 4 THEN 'Daily'    ".
            "	WHEN intervalType = 5 THEN 'Time'     ".
            "	ELSE '' ".
            "END AS intervalName, q.* FROM `tops_taskqueue` q ".
            'ORDER BY taskname';
        $stmt = $this->executeStatement($sql);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}