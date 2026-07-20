<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2026-04-30 11:20:49
 */ 

namespace Peanut\content\db\model\entity;

class ContentAuthor  extends \Tops\db\TEntity 
{ 
    public $fullName;
    public $accountId;
    public $active = 1;
}
