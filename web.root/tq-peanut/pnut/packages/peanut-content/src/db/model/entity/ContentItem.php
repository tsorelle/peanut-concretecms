<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2022-05-31 17:51:59
 */ 

namespace Peanut\content\db\model\entity;

class ContentItem  extends \Tops\db\TimeStampedEntity
{ 
    public $id;
    public $title;
    public $authorId;
    public $context;
    public $description;
    public $shared = false;
    public $active;

    public function getDtoDataTypes()
    {
        $types = parent::getDtoDataTypes();
        $types['shared'] = \Tops\sys\TDataTransfer::dataTypeFlag;
        return $types;
    }

}
