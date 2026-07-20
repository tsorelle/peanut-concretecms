<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2018-06-19 22:31:43
 */ 

namespace Peanut\QnutDocuments\db\model\entity;

use Tops\db\TEntity;

class Document  extends TEntity
{
    public $id;
    public $title;
    public $filename;
    public $folder;
    public $abstract;
    public $protected;
    public $publicationDate;
    public $addendumType;
    public $addendumDate;
    public $addendumComment;
}
