<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/6/2017
 * Time: 8:49 AM
 */

namespace Tops\db;

use Tops\sys\TDataTransfer;

class TimeStampedEntity extends TAbstractEntity
{
    public $createdby;
    public $createdon;
    public $changedby;
    public $changedon;

    public function setCreateTime($userName = 'admin')
    {
        $today = new \DateTime();
        $date = $today->format('Y-m-d H:i:s');
        $this->createdby = $userName;
        $this->createdon = $date;
        $this->changedby = $userName;
        $this->changedon = $date;
    }

    public function setUpdateTime($userName = 'admin')
    {
        $today = new \DateTime();
        $date = $today->format('Y-m-d H:i:s');
        $this->changedby = $userName;
        $this->changedon = $date;
    }


    public function getDtoDataTypes()
    {
        return [
            'createdon' => TDataTransfer::dataTypeDateTime,
            'changedon' => TDataTransfer::dataTypeDateTime
        ];
    }

    /**
     * @var bool
     * Timestamps are usually assigned by the database. Use this switch on the rare occasion where you
     * need to preassign them in the DTO
     */
    private $timestampDefaults = false;
    public function useTimeStampDefaults($on=false) {
        $this->timeStampDefaults = $on;
    }

    public function getDtoDefaults($username = 'system')
    {
        return $this->timestampDefaults ?
            [
                'id' => 0,
                'createdby' => $username,
                'changedby' => $username,
                'createdon' => TDataTransfer::dataTypeNow,
                'changedon' => TDataTransfer::dataTypeNow
            ] : ['id' => 0];
    }
}