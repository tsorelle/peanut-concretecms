<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2017-11-15 13:11:28
 */ 
namespace Peanut\PeanutMailings\db\model\repository;


use \PDO;
use PDOStatement;
use Tops\db\TDatabase;
use \Tops\db\TNamedEntitiesRepository;

class EmailListsRepository extends \Tops\db\TNamedEntitiesRepository
{
    protected function getTableName() {
        return 'qnut_email_lists';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\PeanutMailings\db\model\entity\EmailList';
    }

    public function getEmailList(string $code) {
        $sql = 'SELECT e.id,e.code,e.name,e.description FROM qnut_email_lists e WHERE  `code` = ?';
        $stmt = $this->executeStatement($sql,[$code]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    public function getSubscriptionListLookup($includeAdminOnly = false,$activeOnly = true) {
        $sql =
            'SELECT e.id,e.`code`,e.`name`,e.description,e.mailBox,m.displaytext AS mailboxName,e.cansubscribe, e.adminonly, e.active '.
            'FROM qnut_email_lists e JOIN tops_mailboxes m ON e.mailBox = m.mailboxcode '.
            ' WHERE (cansubscribe <> 0';
        if ($includeAdminOnly) {
            $sql .= ' OR adminonly = 1 ';
        }
        $sql .= ')';
        if ($activeOnly) {
            $sql .= ' AND e.active=1';
        }
        $sql .= ' ORDER BY e.`name`';

        $stmt = $this->executeStatement($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    protected function getFieldDefinitionList()
    {
        return array(
            'id' => PDO::PARAM_INT,
            'code' => PDO::PARAM_STR,
            'name' => PDO::PARAM_STR,
            'description' => PDO::PARAM_STR,
            'mailBox' => PDO::PARAM_STR,
            'cansubscribe' => PDO::PARAM_INT,
            // todo: introduce cansend when calendar notifications enabled
            // 'cansend' => PDO::PARAM_INT,
            'adminonly' => PDO::PARAM_INT,
            'createdby' => PDO::PARAM_STR,
            'createdon' => PDO::PARAM_STR,
            'changedby' => PDO::PARAM_STR,
            'changedon' => PDO::PARAM_STR,
            'active' => PDO::PARAM_STR);
    }


}