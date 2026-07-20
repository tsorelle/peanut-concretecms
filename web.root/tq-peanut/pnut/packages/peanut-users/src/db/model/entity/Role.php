<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2022-05-09 21:42:28
 */ 

namespace Peanut\users\db\model\entity;

class Role  extends \Tops\db\TimeStampedEntity
{ 
    public $id;
    public $name;
    public $description;
    public $active;
}
