<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2017-12-15 18:44:53
 */ 
namespace Tops\db\model\repository;


use \PDO;
use PDOStatement;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;

class ProcessesRepository extends \Tops\db\TEntityRepository
{
    protected function getTableName() {
        return 'tops_processes';
    }

    protected function getDatabaseId() {
        return 'tops-db';
    }

    protected function getClassName() {
        return 'Tops\db\model\entity\Process';
    }

    protected function getLookupField()
    {
        return 'code';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'code'=>PDO::PARAM_STR,
        'name'=>PDO::PARAM_STR,
        'description'=>PDO::PARAM_STR,
        'paused'=>PDO::PARAM_STR,
        'enabled'=>PDO::PARAM_STR);
    }
}