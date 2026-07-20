<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2022-05-19 11:46:17
 */ 
namespace Peanut\users\db\model\repository;

use \PDO;
use PDOStatement;
use Peanut\users\db\model\entity\Authentication;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;

class AuthenticationsRepository extends \Tops\db\TEntityRepository
{
    protected function getTableName() {
        return 'pnut_authentications';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\users\db\model\entity\Authentication';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'ip'=>PDO::PARAM_STR,
        'last'=>PDO::PARAM_STR,
        'attempts'=>PDO::PARAM_INT,
        'success'=>PDO::PARAM_STR);
    }

    /**
     * @param $ip
     * @return Authentication | boolean
     */
    public function getCurrent($ip) {
        $where =
            ' ip = ? AND success IS NULL '.
            ' AND NOW() <  DATE_ADD(`last`, INTERVAL 10 MINUTE)';

        return $this->getSingleEntity($where,[$ip]);
    }


    public function isBlocked($ip,$maxCount) {
       $sql = 'SELECT COUNT(*) FROM '.$this->getTableName().
        ' WHERE ip=? AND attempts >= ?';
       $result = $this->getValue($sql,[$ip,$maxCount * 100]);
       return $result > 0;
    }

    public function clearIpHistory($ip) {
        $sql = 'DELETE FROM '.$this->getTableName().' WHERE ip =?';
        $this->executeStatement($sql,[$ip]);
    }

    public function updateForSuccess($ip) {
        $auth = $this->getCurrent($ip);
        if ($auth) {
            $sql = 'UPDATE '.$this->getTableName().' SET success = NOW() WHERE id = ?';
            $params = [$auth->id];
        }
        else {
            $sql = 'INSERT INTO '.$this->getTableName().' (ip,success) VALUES (?,NOW())';
            $params = [$ip];
        }
        $this->executeStatement($sql,$params);

        // clean up prior attempts
        $this->executeStatement(
            'DELETE FROM '.$this->getTableName().
            ' WHERE ip=? AND success IS NULL',[$ip]);
    }

    public function updateForFailure($ip,$maxAttempts) {
        $auth = $this->getCurrent($ip);
        if ($auth) {
            $attempts = $auth->attempts + 1;
            if ($attempts > $maxAttempts * 100) {
                // looks like an attack, dont over burden the database.
                return false;
            }
            $sql = 'UPDATE '.$this->getTableName().' SET attempts = ? WHERE id = ?';
            $params = [$attempts, $auth->id];
            $this->executeStatement($sql,$params);
            if ($attempts > $maxAttempts) {
                // too many tries
                return false;
            }
        }
        else {
            $sql = 'INSERT INTO '.$this->getTableName().' (ip) VALUES (?)';
            $this->executeStatement($sql,[$ip]);
        }
        return true;
    }
}