<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2022-05-09 12:39:20
 */ 

namespace Peanut\users\db\model\entity;

class Usersession  extends \Tops\db\TAbstractEntity
{
    public $id;
    public $sessionid;
    public $userId;
    public $signedin;
}