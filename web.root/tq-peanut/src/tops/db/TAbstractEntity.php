<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/3/2018
 * Time: 5:33 AM
 */

namespace Tops\db;


use Tops\sys\TDataTransfer;

/**
 * Class TAbstractEntity
 * @package Tops\db
 *
 *  An entity object is intended to hold data in a structure that matches a database table structure.
 *  Entity properties corresponding to the database table columns must be public and must match the
 *  spelling and case of the column names.  They can then be used by PDO operations to return query results.
 *
 *  Typically, entity objects are used by a database repository class to convey data to and from the database.
 *  The repository class must implement the getClassName() method returning the full class name of
 *  the corresponding entity class.
 *
 *  This base class provides the assignFromObject method which extracts, validates and performs conversions on data
 *  from another object. The typical use is in a service class (TSericeCommand) where
 *  an abstract data transfer object (stdClass) is recieved as part of the json request.
 *
 *  This is a convenience only. An entity class doe not have to derive from this base class as long as the public
 *  properties match the database schema.
 *
 *  See db/model/entity/Process.php, db/model/repository/ProcessesRepository.php and tests/ProcessTest.php for a simple usage example.
 */
abstract class TAbstractEntity
{

    public function getClassName() {
        return get_class($this);
    }

    /**
     * @param object $dto   An object, usually stdClass, containing matching properties
     * @param string $username
     * @return array of error messages
     */
    public function assignFromObject($dto, $username = 'admin')
    {
        $datatypes = $this->getDtoDataTypes();
        $dt = new TDataTransfer($dto, $this, $datatypes);
        $dt->assignAll();
        $defaults = $this->getDtoDefaults($username);
        $dt->assignDefaultValues($defaults);
        $dt->checkRequiredValues(
            $this->getRequiredFields()
        );
        return $dt->getErrors();
    }

    /**
     *  Optionally override in sub-class, return an array of property names that are required values.
     *  Example:
     *    return ['code','name'];
     *
     * @return array
     */
    protected function getRequiredFields() {
        return [];
    }

    /**
     * Optionally override in sub-class, return an array of property names => datatypes
     * Data type constants are defined in sys/TDataTransfer.php. Fields that evaluate as strings or numbers
     * need not be included.
     *
     * @return array
     *
     * Example:
     *    [
     *     'dateField'=> TDataTransfer::dataTypeDate, // converts any valid date string to mysql date format
     *     'dateTimeField'=> TDataTransfer::dataTypeDateTime, // converts any valid date-time string to mysql date-time format
     *     'flagField'=> TDataTransfer::dataTypeFlag,  // flag evaluates from boolean to 1 or zero
     *     'timeStampField'=> TDataTransfer::dataTypeNow  // value updated if supplied, timestamped otherwise.
     *    ]
     */
    protected function getDtoDataTypes()
    {
        return [];
    }

    /**
     * @return array
     *
     * Optionally override in sub-class. Return an array of property-name => data-type | constant
     * Indicating values that should be posted to the entity object if not supplied.
     *
     * Example:
     *     return [
     *         'startdate' => TDataTransfer::dataTypeToday,
     *         'endtime' => TDataTransfer::dataTypeNow,
     *         'enabled' => 1,
     *         'unit' => '`date`' // in the case that the value matches a data type constant, surround with back quotes/
     *      ];
     *
     * @param string $username
     * @return array
     */
    protected function getDtoDefaults($username = 'admin')
    {
        return [];
    }

}