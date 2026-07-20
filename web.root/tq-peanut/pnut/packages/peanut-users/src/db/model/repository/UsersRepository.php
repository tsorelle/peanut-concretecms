<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2022-05-09 12:38:20
 */ 

namespace Peanut\users\db\model\repository;

use \PDO;
use PDOStatement;
use Peanut\users\db\model\entity\User;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;
use Tops\sys\TConfiguration;

class UsersRepository extends \Tops\db\TEntityRepository
{
    public function setAdminAccount($password) {
        /**
         * @var $current User
         */
        $current = $this->get(1);
        if ($current) {
            $current->password = $password;
            $this->update($current);
        }
        else {
            $sql =
                // "INSERT INTO pnut_users (`id`,`username`,`password`,`active`,`createdby`,createdon`,`changedby`,`changedon`) ".
                "INSERT INTO pnut_users (`id`,`username`,`password`,`active`,`createdby`,`changedby`) ".
                " VALUES (1,'admin',?,1,'system','system')";

            $this->executeStatement($sql,[$password]);
        }
    }

    protected function getTableName() {
        return 'pnut_users';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
       return 'Peanut\users\db\model\entity\User';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'username'=>PDO::PARAM_STR,
        'password'=>PDO::PARAM_STR,
        'active'=>PDO::PARAM_STR,
        'createdby'=>PDO::PARAM_STR,
        'createdon'=>PDO::PARAM_STR,
        'changedby'=>PDO::PARAM_STR,
        'changedon'=>PDO::PARAM_STR);
    }

    /**
     * @param $username
     * @return bool|User
     */
    public function getUserByUsername($username) {
        return $this->getSingleEntity('username = ?',$username);
    }
}