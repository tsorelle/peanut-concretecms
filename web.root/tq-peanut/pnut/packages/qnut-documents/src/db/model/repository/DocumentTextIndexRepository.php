<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2018-12-03 17:03:44
 */ 
namespace Peanut\QnutDocuments\db\model\repository;


use \PDO;
use PDOStatement;
use Peanut\QnutDocuments\db\model\entity\DocumentIndexEntry;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;

class DocumentTextIndexRepository extends \Tops\db\TEntityRepository
{
    protected function getTableName() {
        return 'qnut_document_text_index';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\QnutDocuments\db\model\entity\DocumentIndexEntry';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'documentId'=>PDO::PARAM_INT,
        'text'=>PDO::PARAM_STR,
        'author'=>PDO::PARAM_STR,
        'creationDate'=>PDO::PARAM_STR,
        'modificationDate'=>PDO::PARAM_STR,
        'pageCount'=>PDO::PARAM_INT,
        'processedDate'=>PDO::PARAM_STR,
        'statusMessage'=>PDO::PARAM_STR);
    }

    public function getByDocumentId($documentId) {
        return $this->getSingleEntity("documentId = ?", [$documentId],true);
    }

}