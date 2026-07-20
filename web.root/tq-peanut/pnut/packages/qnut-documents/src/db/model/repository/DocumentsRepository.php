<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2018-06-19 22:31:43
 */ 
namespace Peanut\QnutDocuments\db\model\repository;


use \PDO;
use Peanut\QnutDocuments\DocumentListFilter;
use Peanut\QnutDocuments\PdfTextParser;
use Tops\db\EntityProperties;
use Tops\db\model\repository\EntityPropertyDefinitionsRepository;
use Tops\db\TEntitySearch;
use Tops\sys\TStringTokenizer;

class DocumentsRepository extends \Tops\db\TEntityRepository
{
    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\QnutDocuments\db\model\entity\Document';
    }

    protected function getFieldDefinitionList()
    {
        return array(
            'id'=>PDO::PARAM_INT,
            'title'=>PDO::PARAM_STR,
            'filename'=>PDO::PARAM_STR,
            'folder'=>PDO::PARAM_STR,
            'abstract'=>PDO::PARAM_STR,
            'protected' =>PDO::PARAM_INT,
            'publicationDate'=>PDO::PARAM_STR,
            'addendumType' =>PDO::PARAM_INT,
            'addendumDate'=>PDO::PARAM_STR,
            'addendumComment'=>PDO::PARAM_STR,
            'createdby'=>PDO::PARAM_STR,
            'createdon'=>PDO::PARAM_STR,
            'changedby'=>PDO::PARAM_STR,
            'changedon'=>PDO::PARAM_STR,
            'active'=>PDO::PARAM_STR
        );
    }

    /**
     * @param $fileName
     * @param $folder
     * @param $protected
     * @param int $excludeId
     * @return \Peanut\QnutDocuments\db\model\entity\Document[]
     */
    public function findDuplicates($fileName, $folder, $protected, $excludeId = 0)
    {
        $protected = empty($protected) || $protected === '0' ? 0 : 1;
        $sql = 'SELECT * FROM '.$this->getTableName().
            ' WHERE filename = ? AND folder = ? AND protected = ? AND id <> ?';
        $stmt = $this->executeStatement($sql, [$fileName,$folder,$protected,$excludeId]);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Peanut\QnutDocuments\db\model\entity\Document');

        /**
         *  @var $result \Peanut\QnutDocuments\db\model\entity\Document[]
         */
        $result = $stmt->fetchAll();
        return $result;
    }

    private $entityProperties;
    public function getEntityProperties() {
        if (!isset($this->entityProperties)) {
            $this->entityProperties = new EntityProperties(self::EntityCode);
        }
        return $this->entityProperties;
    }

    /*private $valuesRepository;
    private function getValuesRepository() {
        if (!isset($this->valuesRepository)) {
            $this->valuesRepository = new EntityPropertyValuesRepository();
        }
        return $this->valuesRepository;
    }*/

    const EntityCode = 'document';
    protected function getTableName() {
        return 'qnut_documents';
    }

    public function getByName($filename, $folder = null)
    {
        $params = [$filename];
        $where = 'filename = ?';
        if ($folder) {
            $where .= ' and folder = ?';
            $params[] = $folder;
        }
        return $this->getSingleEntity($where,$params);
    }

    private function getNewsLetterQuery() {
        return
            'FROM qnut_documents d '.
            'JOIN tops_entity_property_values v ON v.instanceId = d.id '.
            'JOIN tops_entity_properties p ON p.id = v.entityPropertyId '.
            'JOIN qnut_document_types t ON CAST(v.value AS UNSIGNED) = t.id '.
            "WHERE p.key = 'doctype' AND t.code = 'newsletter' ";

    }
    public function getNewsletterCount()
    {
        $sql = 'SELECT COUNT(*) '.$this->getNewsLetterQuery();
        $stmt = $this->executeStatement($sql);
        $count = $stmt->fetchColumn();
        return $count;
    }

    public function getLastNewsletterDate() {
        $sql = 'SELECT MAX(publicationDate) FROM qnut_documents d '.
            'JOIN tops_entity_property_values v ON v.instanceId = d.id  '.
            'JOIN tops_entity_properties p ON p.id = v.entityPropertyId  '.
            'JOIN qnut_document_types t ON CAST(v.value AS UNSIGNED) = t.id '.
            "WHERE p.key = 'doctype' AND t.code = 'newsletter'";
        $stmt = $this->executeStatement($sql);
        $date = $stmt->fetchColumn();
        return $date;
    }

    public function getNewsletterList($pageNo, $itemsPerPage, $uri,$editUrl, $addendaUri)
    {
        $offset = ($pageNo - 1) * $itemsPerPage;
        $sql = "SELECT DISTINCT d.id,title,publicationDate, ".
            '(SELECT COUNT(*) FROM `qnut_documents` WHERE addendumType = 1 AND `addendumDate` = d.publicationDate) AS addenda,'.
            " CONCAT('$uri',d.id) AS uri ,".
            " CONCAT('$editUrl?id=',d.id) AS editUrl, ".
            " CONCAT('$addendaUri','?id=',d.id) AS addendaUri ".
            $this->getNewsLetterQuery().
            sprintf(" ORDER BY publicationDate LIMIT %d, %d",$offset,$itemsPerPage);// 'ORDER BY publicationDate'; // DESC ';
        $stmt = $this->executeStatement($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getNewsletterByDate(string $date)
    {
        $sql = 'SELECT d.* FROM qnut_documents d '.
            'JOIN tops_entity_property_values v ON v.instanceId = d.id  '.
            'JOIN tops_entity_properties p ON p.id = v.entityPropertyId  '.
            'JOIN qnut_document_types t ON CAST(v.value AS UNSIGNED) = t.id '.
            "WHERE p.key = 'doctype' AND t.code = 'newsletter'" .
            'AND d.publicationDate = ?';

        $stmt = $this->executeStatement($sql,[$date]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, $this->getClassName());
        return $stmt->fetch();
    }

    public function protectDocs($folder)
    {
        $sql = 'UPDATE '.$this->getTableName().' SET protected=1 WHERE folder = ?';
        $this->executeStatement($sql,[$folder]);
    }

    public function publishDoc($id)
    {
        $sql = 'UPDATE '.$this->getTableName().' SET protected=0 WHERE id = ?';
        $this->executeStatement($sql,[$id]);
    }

    /**
     * @param array $typeCode
     * @param $publicationDate
     * @param $getDocumentsUri
     * @param $getFormPage
     * @return array of
     *     interface IDocumentReference {
     *         id: any,
     *         title: string,
     *         publicationDate: any,
     *         documentType: string,
     *         uri: string,
     *         editUrl: string
     *     }
     */
    public function getAddendaList($typeCode, $publicationDate, $uri, $docPage)
    {
        $sql =
            'SELECT doc.id, title, publicationDate, addendumComment as description, '.
            "UPPER( SUBSTRING_INDEX(doc.filename,'.',-1)) AS documentType, ".
            "CONCAT('$uri',doc.id) AS viewUrl ,".
            "CONCAT('$uri',doc.id,'/download') AS downloadUrl ,".
            "CONCAT('$docPage','?id=',doc.id) AS editUrl ".
            'FROM `qnut_documents` doc ' .
            'JOIN `qnut_document_addendum_types` nt ON doc.`addendumType` = nt.id '.
            'WHERE nt.code = ? AND addendumDate = ?';

        $stmt = $this->executeStatement($sql,[$typeCode,$publicationDate]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @param $id
     * @return \Peanut\QnutDocuments\db\model\entity\ExtendedDocument
     */
    public function getDocument($id) {
        $sql = 'SELECT * FROM '.$this->getTableName().' WHERE id = ?';
        $stmt = $this->executeStatement($sql, [$id]);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Peanut\QnutDocuments\db\model\entity\ExtendedDocument');

        /**
         *  @var $document \Peanut\QnutDocuments\db\model\entity\ExtendedDocument
         */
        $document = $stmt->fetch();
        if ($document) {
            $document->properties = $this->getEntityProperties()->getValues($id);
            $document->committees = $this->getDocumentCommitteeValues($id);
        }

        return $document;
    }

    /**
     * @param $searchRequest
     *     interface IDocumentSearchRequest {
     *         title: string,
     *          fileType: string,
     *         keywords: string,
     *         fulltext: boolean,
     *         dateSearchMode: any,
     *         firstDate: any,
     *         secondDate: any,
     *         properties: INameValuePair[]
     *         literal: boolean
     *         sortOrder: any,
     *         sortDescending: boolean,
     *         pageNumber: any,
     *         itemsPerPage: any
     *         recordCount: any
     *       }
     */
    public function searchDocuments($request,$uri,$docPage) {
        $docPage .= '?id=';
        // $itemLimit = empty($request->itemsPerPage) ? 0 : $request->itemsPerPage;
        // $itemsPerPage = empty($request->recordCount) ? 0 : $itemLimit;
        // $pageNo = empty($request->pageNumber) ? 1 : $request->pageNumber;
        // $offset =  $itemsPerPage > 0 ?  (($pageNo - 1) * $itemsPerPage) : 0;
        // $fullText = isset($request->searchType) && $request->searchType == 'text';
        $whereStatements = array();
        $publicOnly = (!empty($request->publicOnly));
        $wideSearch = (!empty($request->wideSearch));

        $text = isset($request->searchText) ? trim($request->searchText) : '';
        $words = array();
        if (!empty($text)) {
            $text = str_replace('%','&percnt;',$text);

            $words = PdfTextParser::GetIndexWords($text);

            // eliminate words that are too common and will produce voluminous results
            $words = array_diff($words,
                ['a','an','and','at','for','i','in','he','her','his','hers','is',
                    'me','of','on','or','she','the','them','they','those','you','your']);
        }

        $parameters = array();
        switch($request->searchType) {
            case 'text' :
                if (empty($text)) {
                    return false;
                }
                $sqlHeader = "SELECT DISTINCT id,title,publicationDate, uri,editUrl, 'PDF' as documentType ";
                $queryHeader = "SELECT DISTINCT doc.id,title,publicationDate, CONCAT('$uri',doc.id) AS uri ,CONCAT('$docPage',doc.id) AS editUrl ";

                $sql = ' FROM ( ';

                // literal match
                $join = ' FROM `qnut_document_text_index` idx JOIN qnut_documents doc ON doc.id = idx.documentId ';
                if ($publicOnly) {
                    $join .= ' AND doc.protected <> 1 ';
                }
                $likeText = " REPLACE(idx.text,'%','&percnt;') LIKE ? ";
                // $sql .= $queryHeader.', -1 AS score '.$join;
                $sql .= "$queryHeader $join";
                $sql .= 'WHERE '.$likeText;
                $parameters[] = "%$text%";

                // all keywords match
                // $sql .= " UNION $queryHeader, -3 AS score $join WHERE ";
                $sql .= " UNION $queryHeader $join WHERE ";
                $words = PdfTextParser::GetIndexWords($text);
                $op = '';
                foreach ($words as $word) {
                    $sql .= $op.$likeText;
                    $parameters[] = "%$word%";
                    $op = ' AND ';
                }

                if ($wideSearch) {
                    // one keyword matches
                    // $sql .= " UNION $queryHeader, -3 AS score $join WHERE ";
                    $sql .= " UNION $queryHeader $join WHERE ";
                    if ($publicOnly) {
                        // $sql .= '(doc.protected <> 1) ';
                    }
                    $op = '';
                    foreach ($words as $word) {
                        $sql .= $op . $likeText;
                        $parameters[] = "%$word%";
                        $op = 'OR ';
                    }

                }
                $sql .= ') AS found_docs ';
                $sortBy = $this->getSortBy($request);
                    break;
            case 'info' :
                $sqlHeader = 'SELECT DISTINCT doc.id,title,publicationDate, '.
                    "CONCAT('$uri',doc.id) AS uri , CONCAT('$docPage',doc.id) AS editUrl ,UPPER( SUBSTRING_INDEX(filename,'.',-1)) AS documentType ";
                if ($publicOnly) {
                    $whereStatements[] = "(protected <> 1)";
                }

                $sql =  ' FROM qnut_documents doc ';
                $sortBy = $this->getSortBy($request);

                $filterProperties = is_array($request->properties) ? $request->properties : array();
                if (!empty($filterProperties)) {
                    $sql .= 'JOIN  tops_entity_property_values pv ON doc.id = pv.instanceId '.
                        'JOIN tops_entity_properties props ON pv.`entityPropertyId` = props.id ';

                    foreach ($filterProperties as $property) {
                        $whereStatements[] = "(props.key = ? AND pv.value = ?)";
                        $parameters[] = $property->Key;
                        $parameters[] = $property->Value;
                    }
                }

                if (!empty($request->committeeId)) {
                    $sql .= 'JOIN qnut_document_committees cmtes ON doc.id = cmtes.documentId ';
                    $whereStatements[] = ' (cmtes.committeeId = ?) ';
                    $parameters[] =  $request->committeeId;

                }

                if (!empty($request->title)) {
                    $whereStatements[] = "(doc.title like ?)";
                    $parameters[] =  '%'.$request->title.'%';
                }

                if (!empty($request->fileType)) {
                    $whereStatements[] = ' (doc.filename like ?) ';
                    $parameters[] =  '%'.$request->fileType;
                }

                if (!empty($text)) {

                    if ($request->literal) {
                        $whereStatements[] = "REPLACE(abstract,'%','&percnt;') like ?";
                        $parameters[] = '%'.$text.'%';
                    }
                    else {
                        // $words = TStringTokenizer::extractKeywords($request->text);
                        if (!empty($words)) {
                            $allWords = '%' . implode('%', $words) . '%';
                            $whereStatements[] = "REPLACE(abstract,'%','&percnt;') like ?";
                            $parameters[] = $allWords;
                            $statement = '';
                            foreach ($words as $word) {
                                if (!empty($statement)) {
                                    $statement .= ' OR ';
                                }
                                $statement .= "REPLACE(abstract,'%','&percnt;') like ?";
                                $parameters[] = '%' . $word . '%';
                            }
                            if (!empty($statement)) {
                                $whereStatements[] = "($statement)";
                            }
                        }
                    }

                    if (!empty($whereStatements)) {
                        $sql .= ' WHERE ' . implode(' OR ',$whereStatements);
                    }

                }
                else if (!empty($whereStatements)) {
                    $sql .= ' WHERE ' . implode(' AND ',$whereStatements);
                }

                break;
            case 'lookup':
                $sqlHeader = 'SELECT DISTINCT doc.id,title,publicationDate, '.
                    "CONCAT('$uri',doc.id) AS uri , CONCAT('$docPage',doc.id) AS editUrl ,UPPER( SUBSTRING_INDEX(filename,'.',-1)) AS documentType ";
                $sql = ' FROM qnut_documents doc WHERE (doc.filename = ? OR doc.id = ?)';
                if ($publicOnly) {
                    $sql .= ' AND (doc.protected <> 1) ';
                }
                $parameters = [@$request->filename,@$request->documentId];
                break;
            default:
                return false;
        }

        $response = new \stdClass();
        if (!empty($sortBy)) {
            $sql .= " ORDER BY $sortBy ";
        }

        $test = $sqlHeader.$sql;
        $sql = $sqlHeader.$sql;
        $stmt = $this->executeStatement($sql,$parameters);
        $searchResults = $stmt->fetchAll(PDO::FETCH_OBJ);
        $response->searchResults = $this->filterSearchResults($searchResults);
        return $response;
    }

    private function getSortBy($request) {
        // $sortOrder = empty($request->sortOrder) ? 1 : $request->sortOrder;
        $sortOrder = $request->sortOrder ?? 1;
        switch($sortOrder) {
            case 0 : $sortBy = ''; break;
            case 1 :
            case 2: $sortBy = 'publicationDate'; break;
            case 3 : $sortBy = 'title'; break;
            default : $sortBy = 'id';
        }

        if (!empty($request->sortDescending)) {
            $sortBy .= ' DESC ';
        }
        return $sortBy;
    }

    /**
     * @param $searchResults
     * @return array
     */
    public function filterSearchResults($searchResults) {
        if (empty($searchResults)) {
            return [];
        }
        $filtered = [];
        $ids = [];
        foreach ($searchResults as $doc) {
            $id = $doc->id;
            if (!in_array($id,$ids)) {
                $filtered[] = $doc;
            }
            array_push($ids,$id);
        }

        return $filtered;
    }

    public function getUnindexedDocuments() {
        $where = "filename LIKE ? AND id NOT IN (SELECT documentId FROM qnut_document_text_index)";
        return $this->getEntityCollection($where,['%.pdf'],true);
    }

    public function getDocumentList(DocumentListFilter $filter,$pageNumber,$uri,$docPage) {
        $docPage .= '?id=';
        $offset =  $filter->itemsPerPage > 0 ?  (($pageNumber - 1) * $filter->itemsPerPage) : 0;
        $parameters = array();

        $sqlHeader = 'SELECT doc.id,title,publicationDate, '.
            "CONCAT('$uri',doc.id) AS uri , CONCAT('$docPage',doc.id) AS editUrl ,UPPER( SUBSTRING_INDEX(filename,'.',-1)) AS documentType ".
            'FROM qnut_documents doc ';

        if ($filter->item === 'all') {
            $sql = '';
        }
        if ($filter->item == 'committee') {
            $sql =
                'JOIN  qnut_document_committees cmte ON doc.id = cmte.documentId '.
                " WHERE cmte.committeeId =? ";
            $parameters = [$filter->value];
        }
        else if ($filter->item == 'group') {
            $sql =
                'JOIN  qnut_usergroup_documents ug ON doc.id = ug.documentId '.
                " WHERE ug.groupid =? ";
            $parameters = [$filter->value];
        }
        else {
            $sql =
                'JOIN  tops_entity_property_values pv ON doc.id = pv.instanceId '.
                'JOIN tops_entity_properties props ON pv.`entityPropertyId` = props.id '.
                " WHERE props.key = ? and  pv.value =? ";

            $parameters = [$filter->item,$filter->value];
        }

        if ($filter->publicOnly) {
            $sql .= $filter->item === 'all' ? ' WHERE ' : ' AND ';
            $sql .= ' doc.protected <> 1 ';
        }

        if (!empty($filter->sortOrder)) {
            $sql .= " ORDER BY $filter->sortOrder ";
        }

        if (!empty($filter->itemsPerPage)) {
            $sql .= " LIMIT $offset, $filter->itemsPerPage";
        }

        $stmt = $this->executeStatement($sqlHeader.$sql,$parameters);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getDocumentListCount(DocumentListFilter $filter) {
        $parameters = array();
        $sqlHeader = 'SELECT count(*) FROM qnut_documents doc ';
        if ($filter->item === 'all') {
            $sql = '';
        }
        if ($filter->item == 'committee') {
            $sql =
                'JOIN  qnut_document_committees cmte ON doc.id = cmte.documentId '.
                " WHERE cmte.committeeId =? ";
            $parameters = [$filter->value];
        }
        else {
            $sql =
                'JOIN  tops_entity_property_values pv ON doc.id = pv.instanceId '.
                'JOIN tops_entity_properties props ON pv.`entityPropertyId` = props.id '.
                " WHERE props.key = ? and  pv.value =? ";


            $parameters = [$filter->item,$filter->value];
        }

        if ($filter->publicOnly) {
            $sql .= $filter->item === 'all' ? ' WHERE ' : ' AND ';
            $sql .= ' doc.protected <> 1 ';
        }

        $stmt = $this->executeStatement($sqlHeader.$sql,$parameters);
        $count = $stmt->fetchColumn();
        return $count;

    }

    public function getFilterLookupId($key,$value) {
        switch($key) {
            case 'committee' :
                return $this->getCommitteeFilterLookupId($value);
            case 'group' :
                return $this->getGroupFilterLookupId($value);
            default:
                return $this->getPropertyFilterLookupId($key,$value);
        }
    }

    public function getPropertyFilterLookupId($key,$value) {
        $result = [];
        $sql = "SELECT p.id, lookup  FROM tops_entity_properties p WHERE p.entityCode = 'document' AND p.`key`=?";
        $stmt = $this->executeStatement($sql,[$key]);
        $property = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$property) {
            return false;
        }

        $sql = "SELECT id FROM $property->lookup WHERE code = ?";
        $stmt = $this->executeStatement($sql,[$value]);
        $valueId = $stmt->fetchColumn();
        return $valueId;
    }

    public function getCommitteeFilterLookupId($value) {
        $sql = "SELECT id FROM qnut_committees WHERE code = ?";
        $stmt = $this->executeStatement($sql,[$value]);
        $valueId = $stmt->fetchColumn();
        return $valueId;
    }

    private function getGroupFilterLookupId($value)
    {
        $sql = "SELECT id FROM qnut_usergroups WHERE code = ?";
        $stmt = $this->executeStatement($sql,[$value]);
        $valueId = $stmt->fetchColumn();
        return $valueId;
    }


    private function getDocumentCommitteeValues($id)
    {

    }


}