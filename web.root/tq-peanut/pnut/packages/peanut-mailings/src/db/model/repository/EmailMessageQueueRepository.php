<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2017-11-15 13:11:28
 */ 
namespace Peanut\PeanutMailings\db\model\repository;


use \PDO;
use PDOStatement;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;

class EmailMessageQueueRepository extends \Tops\db\TEntityRepository
{
    protected function getTableName() {
        return 'qnut_email_message_recipients';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\PeanutMailings\db\model\entity\EmailMessageRecipient';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'mailMessageId'=>PDO::PARAM_INT,
        'personId'=>PDO::PARAM_STR);
    }
}