<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2017-11-03 21:46:24
 */ 

namespace Tops\db\model\entity;

class Translation  extends \Tops\db\TEntity
{
    public $id;
    public $language;
    public $code;
    public $text;
    public $active;

}