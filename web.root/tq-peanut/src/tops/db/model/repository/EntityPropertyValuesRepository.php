<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2018-06-05 19:10:41
 */ 
namespace Tops\db\model\repository;


use \PDO;
use PDOStatement;
use Tops\db\model\entity\EntityPropertyValue;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;

class EntityPropertyValuesRepository extends \Tops\db\TEntityRepository
{
    protected function getTableName() {
        return 'tops_entity_property_values';
    }

    protected function getDatabaseId() {
        return 'tops-db';
    }

    protected function getClassName() {
        return 'Tops\db\model\entity\EntityPropertyValue';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'instanceId'=>PDO::PARAM_INT,
        'entityPropertyId'=>PDO::PARAM_INT,
        'value'=>PDO::PARAM_STR);
    }


    public function getValues($instanceId) {
        $sql = 'SELECT p.key, v.`value`,p.valueCount '.
            'FROM tops_entity_property_values v '.
            'JOIN tops_entity_properties p ON p.`id` = v.`entityPropertyId` '.
            'WHERE instanceId = ? '.
            'ORDER BY p.`order`, v.entityPropertyId, v.value ';

        $stmt = $this->executeStatement($sql,[$instanceId]);

        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $result = [];
        foreach ($items as $item) {
            if ($item->valueCount > 1) {
                if (array_key_exists($item->key,$result)) {
                    $result[$item->key][] = $item->value;
                }
                else {
                    $result[$item->key] = [$item->value];
                }
            }
            else {
                $result[$item->key] = $item->value;
            }
        }
        return $result;
    }

    public function clearValues($instanceId) {
        $sql = 'DELETE FROM tops_entity_property_values WHERE instanceId = ? ';
        $this->executeStatement($sql,[$instanceId]);
    }

    public function addValue($instanceId, $propertyId, $value) {
        $item = new EntityPropertyValue();
        $item->instanceId = $instanceId;
        $item->entityPropertyId = $propertyId;
        $item->value = $value;
        $this->insert($item);
    }

}