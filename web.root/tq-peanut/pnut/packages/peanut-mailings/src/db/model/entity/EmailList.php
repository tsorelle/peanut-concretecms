<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2017-11-15 13:11:28
 */ 

namespace Peanut\PeanutMailings\db\model\entity;


use Tops\db\NamedEntity;

class EmailList  extends NamedEntity
{
    public $mailBox;
    public $cansubscribe;
    public $adminonly;
}