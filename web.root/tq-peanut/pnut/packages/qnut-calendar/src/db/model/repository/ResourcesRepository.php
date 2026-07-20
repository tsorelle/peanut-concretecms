<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2018-02-02 17:09:06
 */ 
namespace Peanut\QnutCalendar\db\model\repository;


use \PDO;
use PDOStatement;
use Tops\db\TDatabase;
use \Tops\db\TNamedEntitiesRepository;

class ResourcesRepository extends \Tops\db\TNamedEntitiesRepository
{
    protected function getTableName() {
        return 'qnut_resources';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\QnutDirectory\db\model\entity\CalendarResource';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'code'=>PDO::PARAM_STR,
        'name'=>PDO::PARAM_STR,
        'description'=>PDO::PARAM_STR,
        'resourceTypeId'=>PDO::PARAM_INT,
        'createdby'=>PDO::PARAM_STR,
        'createdon'=>PDO::PARAM_STR,
        'changedby'=>PDO::PARAM_STR,
        'changedon'=>PDO::PARAM_STR,
        'active'=>PDO::PARAM_STR);
    }
}