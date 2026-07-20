<?php
namespace Peanut\content\db\model\repository;

use \PDO;
use PDOStatement;
use Peanut\content\db\model\entity\ContentVersion;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;

class ContentVersionsRepository extends \Tops\db\TEntityRepository
{
    protected function getTableName() {
        return 'pnut_content_versions';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
       return 'Peanut\content\db\model\entity\ContentVersion';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'contentId'=>PDO::PARAM_INT,
        'content'=>PDO::PARAM_STR,
        'createdby'=>PDO::PARAM_STR,
        'createdon'=>PDO::PARAM_STR,
        'changedby'=>PDO::PARAM_STR,
        'changedon'=>PDO::PARAM_STR,
        'active'=>PDO::PARAM_STR);
    }

    public function getVersionsByContentId($contentId) {
        $versions = $this->getEntityCollection('contentId = ?',[$contentId],false,'ORDER BY posted DESC');
        return $versions;
    }

    public function getVersionList($contentId) {
        $sql = 'SELECT id, createdon as `dateCreated` FROM '.$this->getTableName().' WHERE active=1 AND contentId = ? ORDER BY posted DESC';
        $stmt = $this->executeStatement($sql,[$contentId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function addVersion($contentId, $content, $isFinal = false )  {
        if ($isFinal)  {
            $sql = 'DELETE FROM '.$this->getTableName().' WHERE contentId = ?';
            $this->executeStatement($sql,[$contentId]);
        }
        $version = new ContentVersion();
        $version->contentId = $contentId;
        $version->content = $content;
/*        $version->posted = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->format('Y-m-d H:i:s.u');*/
        $id =  $this->insert($version);
        $version->id = $id;
        return $version;
    }

    public function getLatestVersion($contentId)
    {
        $where = 'contentId = ?';
        $clauses = 'ORDER BY posted DESC LIMIT 1';
        return $this->getSingleInstance($where,[$contentId],$clauses);
    }

    public function removeVersions(int $contentId,$permanet=false) : void
    {
        $sql = $permanet ? 'DELETE FROM '.$this->getTableName().' WHERE contentId = ?' :
            'UPDATE '.$this->getTableName().' SET active = 0 WHERE contentId = ?';
        $this->executeStatement($sql,[$contentId]);
    }
}