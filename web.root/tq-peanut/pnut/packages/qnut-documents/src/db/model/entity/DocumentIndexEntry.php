<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2018-12-03 17:03:44
 */ 

namespace Peanut\QnutDocuments\db\model\entity;

class DocumentIndexEntry  extends \Tops\db\TAbstractEntity
{ 
    public $id;
    public $documentId;
    public $text;
    public $author;
    public $creationDate;
    public $modificationDate;
    public $pageCount;
    public $processedDate;
    public $statusMessage;

    public function getDtoDataTypes()
    {
        $types = parent::getDtoDataTypes();
        $types['processedDate'] = \Tops\sys\TDataTransfer::dataTypeDateTime;
        return $types;
    }
}
