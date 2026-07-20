<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2022-05-09 12:38:20
 */ 

namespace Peanut\users\db\model\entity;

class User  extends \Tops\db\TimeStampedEntity
{ 
    public $id;
    public $username;
    public $password;
    public $active;
}
