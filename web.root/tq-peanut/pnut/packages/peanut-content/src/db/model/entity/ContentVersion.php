<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2026-04-29 21:12:13
 */ 
namespace Peanut\content\db\model\entity;

class ContentVersion  extends \Tops\db\TimeStampedEntity 
{ 
    public $id;
    public $contentId;
    public $content;
   // public $posted;
    public $active = 1;
}
