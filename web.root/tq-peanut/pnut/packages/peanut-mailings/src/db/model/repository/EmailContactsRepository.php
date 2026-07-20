<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2022-10-07 15:48:43
 */ 
namespace Peanut\PeanutMailings\db\model\repository;


use \PDO;
use PDOStatement;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;

class EmailContactsRepository extends \Tops\db\TEntityRepository
{
    public function getEmail($itemId)
    {
        $c = $this->get($itemId);
        if ($c && $c->name && $c->email) {
            return $c->name.' <'.$c->email.'>';
        }
        return false;
    }

    public function getEmailAsArray($itemId)
    {
        $address = $this->getEmail($itemId);
        return $address ? [$address] : [];
    }

    protected function getTableName() {
        return 'qnut_email_contacts';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\PeanutMailings\db\model\entity\EmailContact';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'name'=>PDO::PARAM_STR,
        'email'=>PDO::PARAM_STR,
        'description'=>PDO::PARAM_STR);
    }
}