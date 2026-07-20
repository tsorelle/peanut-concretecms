<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2017-12-15 18:44:53
 */ 

namespace Tops\db\model\entity;

use Tops\db\TAbstractEntity;
use Tops\sys\TDataTransfer;

class Process extends TAbstractEntity
{ 
    public $id;
    public $code;
    public $name;
    public $description;
    public $paused; // datetime
    public $enabled; // flag

    protected function getRequiredFields()
    {
        return ['code','name'];
    }

    protected function getDtoDataTypes()
    {
        return [
            'paused' => TDataTransfer::dataTypeDateTime,
            'enabled' => TDataTransfer::dataTypeFlag];
    }

    protected function getDtoDefaults($username = 'system')
    {
        return [
            'id' => 0,
            'enabled' => 1
        ];
    }
}