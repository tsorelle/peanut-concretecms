<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2018-04-18 11:37:49
 */ 
namespace Peanut\QnutCalendar\db\model\repository;


use \PDO;
use PDOStatement;
use Peanut\QnutCalendar\db\model\entity\NotificationSubscription;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;

class NotificationSubscriptionsRepository extends \Tops\db\TEntityRepository
{
    const notificationTypesTable = 'qnut_notification_types';

    protected function getTableName() {
        return 'qnut_notification_subscriptions';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\QnutCalendar\db\model\entity\NotificationSubscription';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'notificationTypeId'=>PDO::PARAM_INT,
        'itemId'=>PDO::PARAM_INT,
        'personId'=>PDO::PARAM_INT,
        'leadDays'=>PDO::PARAM_INT);
    }

    /**
     * @param $typeCode
     * @param $itemId
     * @param $personId
     * @return NotificationSubscription
     */
    public function getSubscription($typeCode,$itemId,$personId) {
        $sql = 'SELECT n.* FROM '.$this->getTableName().' n '.
            'JOIN '.self::notificationTypesTable.' t ON n.notificationTypeId = t.id '.
            'WHERE t.code = ? AND n.itemId = ? AND n.personId = ?';
        $stmt = $this->executeStatement($sql,[$typeCode,$itemId,$personId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, $this->getClassName());
        $result = $stmt->fetch();
        return $result;
    }


    private static $notificationTypes = [];
    public function getSubscriptionTypeId($typeCode='') {
        if (!isset(self::$notificationTypes[$typeCode])) {
            $sql = 'SELECT id FROM '.self::notificationTypesTable.' WHERE code = ?';
            $stmt = $this->executeStatement($sql,[$typeCode]);
            $id = self::$notificationTypes[$typeCode] = $stmt->fetch(PDO::FETCH_COLUMN);
            if (empty($id)) {
                return false;
            }
            self::$notificationTypes[$typeCode] = $id;
        }
        return self::$notificationTypes[$typeCode];
    }

    public function deleteSubscriptions($typeCode,$itemId,$personId=0) {
        $typeId = $this->getSubscriptionTypeId($typeCode);
        $args = [$typeId,$itemId];
        $sql =    'DELETE FROM '.$this->getTableName();
        $sql .=  ' WHERE notificationTypeId = ? AND itemId = ?';
        if ($personId) {
            $sql .= ' AND personId = ?';
            $args[] = $personId;
        }
        $stmt = $this->executeStatement($sql,$args);
    }

    /**
     * @param $id
     * @param $leadDays
     * @return int[]
     */
    public function getEventNotificationRecipients($id, $leadDays,$typeCode='calendar')
    {
        $typeId = $this->getSubscriptionTypeId('calendar');
        $sql = 'SELECT personId FROM qnut_notification_subscriptions WHERE notificationTypeId = ? AND itemId = ? AND leadDays = ?';
        $stmt = $this->executeStatement($sql,[$typeId,$id,$leadDays]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }


}