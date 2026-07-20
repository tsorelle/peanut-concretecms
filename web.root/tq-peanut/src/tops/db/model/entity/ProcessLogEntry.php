<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2017-12-15 18:44:53
 */ 

namespace Tops\db\model\entity;

use Tops\sys\TDataTransfer;
use Tops\sys\TDates;

class  ProcessLogEntry
{ 
    public $id;
    public $processCode;
    public $posted;
    public $event;
    public $message;
    public $messageType;
    public $detail;


    public static function Create(
        $processCode,
        $event,
        $message,
        $messageType,
        $detail)
    {
        $entry = new ProcessLogEntry();
        $entry->processCode= $processCode;
        $entry->event= $event;
        $entry->message= $message;
        $entry->messageType= $messageType;
        $entry->detail= $detail;
        $entry->posted = TDates::now();
        return $entry;
    }

     public function assignFromObject($dto)
     {
         (new TDataTransfer($dto,$this,[
             'posted' => TDataTransfer::dataTypeNow
         ]))->assignAll();
     }
}