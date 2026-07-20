<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2022-05-19 11:46:17
 */ 

namespace Peanut\users\db\model\entity;

class Authentication  extends \Tops\db\TAbstractEntity 
{ 
    public $id;
    public $ip;
    public $last;
    public $attempts;
    public $success;

    public function getDtoDataTypes()
    {
        $types = parent::getDtoDataTypes();
        $types['last'] = \Tops\sys\TDataTransfer::dataTypeDateTime;
        $types['success'] = \Tops\sys\TDataTransfer::dataTypeDateTime;
        return $types;
    }
}
