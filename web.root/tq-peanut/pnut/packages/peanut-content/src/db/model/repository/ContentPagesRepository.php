<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2026-04-30 11:46:46
 */
namespace Peanut\content\db\model\repository;


use PDO;

class ContentPagesRepository extends \Tops\db\TEntityRepository
{
    protected function getTableName() {
        return 'pnut_content_pages';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
         return 'Peanut\content\db\model\entity\ContentPage';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'contentId'=>PDO::PARAM_STR,
        'pageName'=>PDO::PARAM_STR,
        'pageTitle'=>PDO::PARAM_STR,
        'uri'=>PDO::PARAM_STR,
        'createdby'=>PDO::PARAM_STR,
        'createdon'=>PDO::PARAM_STR,
        'changedby'=>PDO::PARAM_STR,
        'changedon'=>PDO::PARAM_STR,
        'active'=>PDO::PARAM_STR);
    }
}