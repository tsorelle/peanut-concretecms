<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2018-04-18 11:37:49
 */ 
namespace Peanut\QnutCalendar\db\model\repository;


use \PDO;
use PDOStatement;
use Tops\db\TDatabase;
use \Tops\db\TNamedEntitiesRepository;

class NotificationTypesRepository extends \Tops\db\TNamedEntitiesRepository
{
    protected function getTableName() {
        return 'qnut_notification_types';
    }

    protected function getDatabaseId() {
        return null;
    }

}