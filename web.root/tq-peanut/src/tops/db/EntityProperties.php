<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/28/2018
 * Time: 6:18 AM
 */

namespace Tops\db;


use Tops\db\model\entity\EntityPropertyDefinition;
use Tops\db\model\entity\EntityPropertyValue;
use Tops\db\model\repository\EntityPropertyDefinitionsRepository;
use Tops\db\model\repository\EntityPropertyValuesRepository;
use Tops\db\model\repository\LookupTableRepository;
use Tops\sys\TLanguage;

class EntityProperties
{
    const DataTypeString = 's';
    const DataTypeNumber = 'n';
    const DataTypeKey = 'k';
    const DataTypeCurrency = '$';
    const DataTypeDate = 'd';
    const DataTypeDateTime = 'dt';

    private $entityCode;
    /**
     * @var $definitions EntityPropertyDefinition[]
     */
    private $definitions = null;

    /**
     * @var $lookups
     */
    private $lookups = null;

    public function __construct($entityCode)
    {
        $this->entityCode = $entityCode;
    }

    public function getDefinitions() {
        if ($this->definitions === null) {
            $repository = new EntityPropertyDefinitionsRepository();
            $definitions = $repository->getDefinitions($this->entityCode);
            $this->definitions = [];
            if ($definitions) {
            foreach ($definitions as $definition) {
                $this->definitions[$definition->key] = $definition;
            }
        }
        }
        return $this->definitions;
    }

    private $lookupDefinitions = null;

    public function getLookupDefinitions() {
        if ($this->lookupDefinitions === null) {
            $repository = new EntityPropertyDefinitionsRepository();
            $this->lookupDefinitions = $repository->getLookupDefinitions($this->entityCode);
        }
        return $this->lookupDefinitions;
    }

    public function getLookups() {
        if ($this->lookups === null) {
            $this->lookups = [];
            $definitions = $this->getDefinitions();
            foreach ($definitions as $definition) {
                if ($definition->lookup && (!array_key_exists($definition->lookup,$this->lookups))) {
                    $repository = new LookupTableRepository($definition->lookup);
                    $this->lookups[$definition->lookup] = $repository->getLookupList();
                }
            }
        }
        return $this->lookups;
    }

    public function getEmptyValues()
    {
        $result = [];
        $definitions = $this->getDefinitions();
        foreach ($definitions as $definition) {
            if ($definition->valueCount > 1) {
                $result[$definition->key] = [];
                $result[$definition->key] = [];
            }
            else {
                $result[$definition->key] = $definition->defaultValue;
            }
        }
        return $result;
    }

    public function getValues($instanceId = null) {
        $result = $this->getEmptyValues();
        if ($instanceId === null) {
            return $result;
        }
        $repository = new EntityPropertyValuesRepository();
        $values = $repository->getValues($instanceId);
        foreach ($values as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }

    public function validate(array $values) {
        $errors = [];

        $definitions = $this->getDefinitions();
        foreach ($values as $key => $value) {
            if (array_key_exists($key,$definitions)) {
                $def = $definitions[$key];
                if ($def->required  && empty($value)) {
                    $errors[$key] = TLanguage::text('property-err-required');
                    continue;
                }
                $this->checkValues($key,is_array($value) ? $value : [$value],$def,$errors);
            }
            else {
                $errors[$key] = TLanguage::text('property-err-not-defined');
            }
        }
        return empty($errors) ? true : $errors;
    }

    private function checkValues($key, array $values,EntityPropertyDefinition $def,array &$errors) {
        foreach ($values as $value) {
            switch ($def->datatype) {
                case self::DataTypeString:
                    break;
                case self::DataTypeKey :
                    if (preg_match ("/[^0-9]/", $value)) {
                        $errors[$key] = TLanguage::text('property-err-invalid-key');
                    }
                    break;
                case self::DataTypeNumber :
                    if (!is_numeric($value)) {
                        $errors[$key] = TLanguage::text('property-err-not-number');
                    }
                    break;
                    /*
                case self::DataTypeCurrency :
                    break;
                case self::DataTypeDate :
                    break;
                case self::DataTypeDateTime :
                    break;
                    */
                default:
                    $errors[$key] = TLanguage::text('property-err-type-not-supported');
                    break;
            }
        }
    }

    public function setValues($instanceId, array $items) {
        $repository = new EntityPropertyValuesRepository();
        $definitions = $this->getDefinitions();
        $repository->clearValues($instanceId);
        foreach ($items as $key => $value) {
            $definition = $definitions[$key];
            if (is_array($value)) {
                foreach ($value as $element) {
                    $repository->addValue($instanceId,$definition->id,$element);
                }
            }
            else {
                $repository->addValue($instanceId,$definition->id,$value);
            }
        }
    }

    public function dropValues($instanceId) {
        $repository = new EntityPropertyValuesRepository();
        $repository->clearValues($instanceId);
    }
}