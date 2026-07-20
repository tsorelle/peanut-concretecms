<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/28/2017
 * Time: 7:02 AM
 */

namespace Tops\sys;


class TDataTransfer
{
    private $data;
    private $object;
    private $dataTypes;
    private $errors = [];

    const dataTypeDate = 'date' ;
    const dataTypeDateTime = 'datetime' ;
    const dataTypeFlag = 'flag' ;
    const dataTypeToday = 'today';
    const dataTypeNow = 'now' ;
    const dataTypeTime = 'time' ;
    const dataTypeDefault = 'any' ;
    const validationCodeInvalidDate = 'validation-invalid-date';
    const validationCodeRequiredValue = 'validation-field-req';

    // todo: support time data type

    public function __construct($data,$object,$dataTypes=[])
    {
        $this->data = $data;
        $this->object = $object;
        $this->dataTypes = $dataTypes;
    }

    private function getDataType($propertyName)
    {
        if (array_key_exists($propertyName,$this->dataTypes)) {
            return $this->dataTypes[$propertyName];
        }
        return $propertyName == 'active' ? self::dataTypeFlag : self::dataTypeDefault;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function assignProperty($propertyName) {
        $dataType = $this->getDataType($propertyName);
        switch ($dataType) {
            case self::dataTypeToday :
            case self::dataTypeDate :
                return $this->assignDate($propertyName);
            case self::dataTypeNow:
            case self::dataTypeDateTime :
                return $this->assignDate($propertyName,true);
            case self::dataTypeFlag :
                return $this->assignFlag($propertyName);
            case 'time' :
                // todo: support time data type
                return false;
            default :
                return $this->assignValue($propertyName);
        }
    }

    public function assignAll() {
        $this->errors = [];
        foreach ($this->data as $key => $value) {
            $this->assignProperty($key);
        }

        // assign automatic date stamps override existing value
        foreach ($this->dataTypes as $propertyName => $dataType) {
            if (!isset($this->data->$propertyName)) {
                switch ($dataType) {
                    case TDataTransfer::dataTypeNow :
                        $this->assignCurrentDate($propertyName,true);
                        break;
                    case TDataTransfer::dataTypeToday :
                        $this->assignCurrentDate($propertyName);
                        break;
                }
            }
        }
        return $this->errors;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function assignValue($propertyName)
    {
        if (property_exists($this->data, $propertyName) && property_exists($this->object, $propertyName)) {
            $this->object->$propertyName = $this->data->$propertyName;
            return true;
        }
        return false;
    }

    public function assignDate($propertyName, $includeTime = false)
    {
        if (property_exists($this->data, $propertyName) && property_exists($this->object, $propertyName)) {
            $dateValue = TDates::formatMySqlDate($this->data->$propertyName, $includeTime);
            if ($dateValue === false) {
                $this->errors[$propertyName] = TLanguage::formatText(self::validationCodeInvalidDate,[$this->data->$propertyName]);
            }
            else {
                $this->object->$propertyName = $dateValue;
                return true;
            }
        }
        return false;
    }

    public function assignCurrentDate( $propertyName,$includeTime=false)
    {
        if (property_exists( $this->object, $propertyName)) {
            $today = Date(
                $includeTime ? TDates::MySqlDateTimeFormat : TDates::MySqlDateFormat
            );
            $this->object->$propertyName = $today;
            return true;
        }
        return false;
    }

    public function assignCurrentDateTime( $propertyName)
    {
        return $this->assignCurrentDate($propertyName,true);
    }

    public function assignActive()
    {
        return $this->assignFlag('active');
    }

    public function assignFlag($propertyName)
    {
        if (property_exists($this->data, $propertyName) && property_exists($this->object, $propertyName)) {
            $this->object->$propertyName = empty($this->data->$propertyName) ? 0 : 1;
            return true;
        }
        return false;
    }

    /**
     * @param array $defaults
     *
     * update default values in object if not already set
     */
    public function assignDefaultValues($defaults = []) {
        foreach ($defaults as $propertyName => $value) {
            if (!isset($this->object->$propertyName)) {
                switch ($value) {
                    case self::dataTypeNow :
                        $this->assignCurrentDateTime($propertyName);
                        break;
                    case self::dataTypeToday:
                        $this->assignCurrentDate($propertyName);
                        break;
                    default:
                        $value = str_replace('`','',$value);
                        $this->object->$propertyName = $value;
                        break;
                }
            }
        }
    }

    public function checkRequiredValues($properties = []) {
        foreach ($properties as $propertyName) {
            if (empty($this->object->$propertyName)) {
                if (!array_key_exists($propertyName,$this->errors)) {
                    $this->errors[$propertyName] = TLanguage::formatText(self::validationCodeRequiredValue,[$propertyName]);
                }
            }
        }
        return $this->errors;
    }
}