<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2018-06-05 15:23:26
 */ 
namespace Tops\db\model\repository;


use \PDO;
use PDOStatement;
use Tops\db\model\entity\EntityPropertyDefinition;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;
use Tops\sys\TLanguage;

class EntityPropertyDefinitionsRepository extends \Tops\db\TEntityRepository
{
    protected function getTableName() {
        return 'tops_entity_properties';
    }

    protected function getDatabaseId() {
        return 'tops-db';
    }

    protected function getClassName() {
        return 'Tops\db\model\entity\EntityPropertyDefinition';
    }

    protected function getFieldDefinitionList()
    {
        return array(
            'id'=>PDO::PARAM_INT,
            'entityCode'=>PDO::PARAM_STR,
            'key'=>PDO::PARAM_STR,
            'order'=>PDO::PARAM_INT,
            'valueCount'=>PDO::PARAM_INT,
            'lookup'=>PDO::PARAM_STR,
            'required'=>PDO::PARAM_STR,
            'defaultValue'=>PDO::PARAM_STR,
            'datatype'=>PDO::PARAM_STR,
            'label'=>PDO::PARAM_STR
            );
    }

    /**
     * @param $entityCode
     * @return EntityPropertyDefinition[];
     */
    public function getDefinitions($entityCode)
    {
        return $this->getEntityCollection('entityCode=? ORDER BY `order`',[$entityCode]);
    }

    public function getLookupDefinitions($entityCode) {
        $defs = $this->getDefinitions($entityCode);
        $result = [];
        foreach($defs as $def) {
            if ($def->datatype == EntityPropertyDefinition::DataTypeKey) {
                $item = new \stdClass();
                $item->key = $def->key;
                $item->lookup = $def->lookup;
                $item->required = $def->required;
                $item->label = ucfirst(TLanguage::text($def->label));
                $item->defaultValue = $def->defaultValue;
                $result[]=$item;
            }
        }
        return $result;
    }
}