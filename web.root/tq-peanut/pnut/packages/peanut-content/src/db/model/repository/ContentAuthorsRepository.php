<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2026-04-30 11:20:49
 */
namespace Peanut\content\db\model\repository;


use PDO;
use Peanut\content\db\model\entity\ContentAuthor;
use Tops\db\TQuery;

class ContentAuthorsRepository extends \Tops\db\TEntityRepository
{
    protected function getTableName() {
        return 'pnut_content_authors';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
         return 'Peanut\content\db\model\entity\ContentAuthor';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'fullName'=>PDO::PARAM_STR,
        'accountId'=>PDO::PARAM_STR,
        'createdby'=>PDO::PARAM_STR,
        'createdon'=>PDO::PARAM_STR,
        'changedby'=>PDO::PARAM_STR,
        'changedon'=>PDO::PARAM_STR,
        'active'=>PDO::PARAM_STR);
    }

    public function getAuthorByAccountId($id) {
        $author = $this->getSingleEntity('accountId = ?',[$id]);
        return $author;
    }
    public function addAuthor($fullname, $accountId  = null) {
        $author = new ContentAuthor();
        $author->fullName = $fullname;
        $author->accountId = $accountId;
        return $this->insert($author);
    }
}