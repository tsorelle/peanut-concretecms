<?php

namespace Tops\db;

use Tops\db\model\IEntity;

class EntityManager
{
    private $dbCLassPath;
    private static $repositories = [];

    public function __construct($path)
    {
        $this->dbCLassPath = $path;
    }

    private function createObject($entityName,$type) {
        $entityName = ucwords(strtolower($entityName));
        $suffix = $type == 'repository' ? 'sRepository' : '';
        $className = sprintf("\\%s\\model\\%s\\%s%s",$this->dbCLassPath,$type,$entityName,$suffix);
        if (class_exists($className)) {
            $result = new $className();
            $result->id = 0;
            if (!isset($result->active)) {
                $result->active = 1;
            }
            return $result;
        }
        return false;
    }

    /**
     * @param $entityName
     * @return TEntityRepository
     * @throws \Exception
     */
    private function getRepository($entityName)
    {
        if (!isset(self::$repositories[$entityName])) {
            $repo = $this->createObject($entityName,'repository');
            if ($repo === false) {
                throw new \Exception("Repository for '$entityName' not found");
            }
            $repositories[$entityName] = $repo;
            return $repo;
        }
        return self::$repositories[$entityName];
    }

    private function updateObject($entityName,$data,$parentKeyName=null,$parentKeyValue=null)
    {
        $repo = $this->getRepository($entityName);
        /**
         * @var $object TEntity
         */
        $object = (@$data->id) ?
            $repo->get($data->id) :
            $object = $this->createObject($entityName, 'entity');

        if ($object) {
            if($parentKeyName) {
                $data->$parentKeyName = $parentKeyValue;
            }
            $object->assignFromObject($data);
            if ($object->id == 0) {
                return $repo->insert($object);
            }
            else {
                $repo->update($object);
                return $object->id;
            }
        }
        return false;
    }

    public function updateObjects(array $graph,$parentKeyName = null, $parentKeyValue=null) {
        foreach ($graph as $objects) {
            foreach ($objects as $className => $dto) {
                $relatedObjects = @$dto->_relatedobjects ?? null;
                if ($relatedObjects) {
                    unset($dto->_relatedobjects);
                    $foreignKeyName = @$dto->_foreignkey;
                    if ($foreignKeyName) {
                        unset($dto->_foreignkey);
                    }
                }
                $id = $this->updateObject($className, $dto, $parentKeyName, $parentKeyValue);

                if ($relatedObjects) {
                    foreach ($relatedObjects as $related) {
                        $this->updateObjects($related, $foreignKeyName, $id);
                    }
                }
            }
        }
    }

    public function recursiveDelete(IEntity $entity,$repo) {
        $associations = $entity->getAssociations();
        foreach ($associations as $association) {
            $this->recursiveDelete($association,$repo);
        }
        $repo->delete($entity->getId());
    }

    public function remove(IEntity $entity) {
        $entityName = $entity->getEntityName();
        $repo = $this->getRepository($entityName);
        $this->recursiveDelete($entity,$repo);
    }

    public function removeEntities(array $entities)
    {
        foreach ($entities as $entity) {
            $this->remove($entity);
        }
    }

    public function deleteEntity($entityName,$id,array $associations=[],$foreignKey=null) {
        $foreignKey = $foreignKey ?? strtolower($entityName).'Id';
        foreach ($associations as $name) {
            $repo = $this->getRepository($name);
            $repo->deleteByForeignKey($foreignKey,$id);
        }
        $repo =$this->getRepository($entityName);
        $repo->delete($id);
    }

    public function deleteSingleEntity($entityName,$id) {
        $repo = $this->getRepository($entityName);
        $repo->delete($id);
    }

    public function removeRelatedEntities($entityName,$id,$relatedEntities) {
        // $foreignKey =
    }

    public function clear($foreignKey,$foreignId,$relatedEntities = [],$filterCondition = null) {
        $foreignKey .= 'Id';
        foreach ($relatedEntities as $entityName) {
            $repo = $this->getRepository($entityName);
            $repo->deleteByForeignKey($foreignKey,$foreignId,$filterCondition);
        }
    }


    /**
     * @return mixed
     */
    public function getDbCLassPath()
    {
        return $this->dbCLassPath;
    }

    public function flush() {
        // dummy for now may implement later
    }

    public function getObjectCollections(array $entityNames,$foreignKeyName,$foreignKeyValue) {
        $result = [];
        foreach ($entityNames as $entityName) {
            /**
             * @var $repo TEntityRepository
             */
            $repo = $this->getRepository($entityName);
            $items = $repo->getEntityCollection("$foreignKeyName =?",[$foreignKeyValue]);
            $result[$entityName] = empty($items) ? [] : $items;
        }
        return $result;
    }

    public function getEntity($entityName, $entityId, $relatedOneMany = [], $relatededOneOne = []) {
        $repo = $this->getRepository($entityName);
        $entity = $repo->get($entityId);
        if (!$entity) {
            return false;
        }
        if (count($relatededOneOne) + count($relatedOneMany) == 0) {
            return $entity;
        }
        $result = [];
        $result[$entityName] = $entity;
        foreach ($relatedOneMany as $name) {
            $collections = $this->getObjectCollections($relatedOneMany,$entityName.'Id',$entityName);
            if (count($collections)) {
                $result = array_merge($result,$collections);
            }
        }
        foreach ($relatededOneOne as $name) {
            $repo = $this->getRepository($name);
            $object = $repo->getSingleEntity($entityName.'Id = ?',[$entityId]);
            if ($object) {
                $result[$name] = $object;
            }
        }
        return $result;
    }

    public function getFieldValue($entityName, $fieldName, $id)
    {
        $repo = $this->getRepository($entityName);
        return $repo->getFieldValue($fieldName,$id);
    }

    public function getEntityCollection($entityName,$condition,$params=[]) {
        $repo = $this->getRepository($entityName);
        return $repo->getEntityCollection($condition,$params);
    }

    public function getCount($entityName,$condition,$params=[])
    {
        $repo = $this->getRepository($entityName);
        return $repo->getRecordCount($condition,$params=[]);
    }
}