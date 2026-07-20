<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/17/2018
 * Time: 6:04 AM
 */

namespace Peanut\QnutDocuments;

use mysql_xdevapi\Exception;
use Peanut\QnutCommittees\CommitteeManager;
use Peanut\QnutCommittees\db\model\repository\CommitteesRepository;
use Peanut\QnutDocuments\db\model\entity\Document;
use Peanut\QnutDocuments\db\model\entity\DocumentIndexEntry;
use Peanut\QnutDocuments\db\model\repository\DocumentCommitteeAssociation;
use Peanut\QnutDocuments\db\model\repository\DocumentsRepository;
use Peanut\QnutDocuments\db\model\repository\DocumentTextIndexRepository;
use Peanut\QnutDocuments\db\model\repository\DocumentUsergroupsAssociation;
use Peanut\QnutUsergroups\db\model\repository\UsergroupsRepository;
use Peanut\sys\TVmContext;
use Peanut\sys\ViewModelManager;
use Tops\db\model\repository\LookupTableRepository;
use Tops\services\IMessageContainer;
use Tops\sys\TConfiguration;
use Tops\sys\TDates;
use Tops\sys\TLanguage;
use Tops\sys\TNameValuePair;
use Tops\sys\TPath;
use Tops\sys\TStringTokenizer;
use Tops\sys\TUser;
use Zend\I18n\Validator\DateTime;

class DocumentManager
{

    const manageLibraryPermission='manage-document-library';
    const defaultDocumentsUri = '/documents/';
    const defaultSearchPage = '/document-search/';
    const defaultFormPage = '/document/';
    const defaultAddendaUri = '/document/addenda';

    private static $documentsUri;
    public static function getDocumentsUri() {
        if (!isset(self::$documentsUri)) {
            self::$documentsUri = TConfiguration::getValue('uri','documents',self::defaultDocumentsUri);
            if (substr(self::$documentsUri,strlen(self::$documentsUri) - 1) !== '/') {
                self::$documentsUri .= '/';
            }
        }
        return self::$documentsUri;
    }

    private static $addendaUri;
    public static function getAddendaUri() {
        if (!isset(self::$addendaUri)) {
            self::$addendaUri = TConfiguration::getValue('documentAddenda','pages',self::defaultAddendaUri);
        }
        return self::$addendaUri;
    }

    private static $searchUri;
    public static function getSearchPage() {
        if (!isset(self::$searchUri)) {
            self::$searchUri = TConfiguration::getValue('documentSearch','pages',self::defaultSearchPage);
            if (substr(self::$searchUri,strlen(self::$searchUri) - 1) !== '/') {
                self::$searchUri .= '/';
            }
        }
        return self::$searchUri;
    }

    private static $formPage;


    /**
     * @param $contextId
     * @return DocumentListFilter
     *
     * ContextId received from ViewModel
     *
     * Translate context value to request.
     *      Context->value format: {[committe-code] | [item]=[value]};[items per page];{[sort]};{[back months]}
     *      examples:
     *          cmte=are;10;title;12    Adult Religious documents last 12 months order by title asc 10 per page
     *          worship                 All Worship and Ministry Documents defaults order by publicationDate desc 15 per page (default)
     *          all;;;24                All documents in last 24 months order by  order by publicationDate desc 15 per page (default)
     *          all;10;title            All documents order by title ten per page
     *          all                     All documents order  by  order by publicationDate desc
     */
    public function buildFilterFromContext($contextId)
    {
        $context = TVmContext::GetContext($contextId);
        if (empty($context->value)) {
            return null;
        }

        $filter = new DocumentListFilter();
        $filterParts = explode(';',$context->value);
        $count = count($filterParts);
        if ($count===0) {
            return null;
        }
        $item = $filterParts[0];
        if ($item==='all'){
            $filter->item = 'all';
        }
        else {
            $itemParts = explode('=',$item);
            switch (count($itemParts)) {
                case 1 :
                    $filter->item = 'committee';
                    $filter->value = $itemParts[0];
                    break;
                case 2:
                    $filter->item = $itemParts[0];
                    $filter->value = $itemParts[1];
                    break;
                default:
                    return null;
            }
        }

        if ($count > 1) {
            if (!empty($filterParts[1])) {
                $filter->itemsPerPage = $filterParts[1];
            }
            if ($count > 2) {
                $sort = $filterParts[2];
                if (!empty($sort)) {
                    $filter->sortOrder = $sort;
                }
            }
            if ($count > 3) {
                $months = '-' . $filterParts[3];
                try {
                    $today = new \DateTime();
                    TDates::IncrementDate($today, $months, 'months');
                    $filter->publcationDate = $today->format('Y-m-d');
                } catch (\Exception $ex) {
                    return null;
                }
            }
        }
        if ($filter->item !== 'all' && (!is_numeric($filter->value))) {
            $lookupId = $this->getDocumentsRepository()->getFilterLookupId($filter->item,$filter->value);
            if (empty($lookupId)) {
                return null;
            }
            $filter->value = $lookupId;
        }
        return $filter;
    }

    public static function getFormPage() {
        if (!isset(self::$formPage)) {
            self::$formPage = TConfiguration::getValue('documentForm','pages',self::defaultFormPage);
            if (!self::$formPage) {
                self::$formPage = ViewModelManager::getVmUrl('Document','qnut-documents');
            }
        }
        return self::$formPage;
    }

    /**
     * @var DocumentsRepository
     */
    private $documentsRepository;

    /**
     * @var DocumentTextIndexRepository
     */
    private $documentIndexRepository;

    /**
     * @var \Tops\db\EntityProperties EntityProperties
     */
    private $properties;

    /**
     * @var DocumentCommitteeAssociation
     */
    private $committeesAssociation;
    private function getCommitteesAssociation() {
        if (!isset($this->committeesAssociation)) {
            $this->committeesAssociation = new DocumentCommitteeAssociation();
        }
        return $this->committeesAssociation;
    }

    /**
     * @var DocumentUsergroupsAssociation
     */
    private $groupsAssociation;
    private function getGroupsAssociation() {
        if (!isset($this->groupsAssociation)) {
            $this->groupsAssociation = new DocumentUsergroupsAssociation();
        }
        return $this->groupsAssociation;
    }


    const defaultDocumentLocation = 'application/documents';

    public function __construct()
    {
        $this->documentsRepository = new DocumentsRepository();
        $this->properties = $this->documentsRepository->getEntityProperties();
    }

    /**
     * @var DocumentManager
     */
    private static $instance;

    /**
     * @return DocumentManager
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DocumentManager();
        }
        return self::$instance;
    }

    private static $documentDir;

    private function outputContent($args) {
        $argc = count($args);
        $download = false;
        if ($argc > 1 && strtolower($args[$argc - 1]) === 'download') {
            $download = true;
            array_pop($args);
            $argc--;
        }
        if ($argc == 0) {
            self::exitNotFound();
        }
        if (is_numeric($args[0])) {
            $document = self::getInstance()->getDocument($args[0]);
            if (empty($document)) {
                self::exitNotFound();
            }
        } else {
            $filename = array_pop($args);
            $t = strpos($filename,'.');
            if (strpos($filename,'.') === false) {
                $filename .= '.pdf';
            }
            if (empty($args)) {
                $folder = null;
            }
            else {
                $folder = implode('/', $args);
                $folder = str_replace('+','/',$folder);
            }
            $document = self::getInstance()->getDocumentByName($filename,$folder);
        }

        if (empty($document)) {
            self::exitNotFound();
        }

        self::openDocument($document,$download);
    }

    /**
     * @param $args
     */
    public static function outputDocumentContent($args)
    {
        self::getInstance()->outputContent($args);
    }

    public static function getDocumentDir($folder='',$fileName='') {
        if (!isset(self::$documentDir)) {
            $location = TConfiguration::getValue('location','documents',self::defaultDocumentLocation);
            self::$documentDir = TPath::fromFileRoot($location);
            if (!is_dir(self::$documentDir)) {
                mkdir(self::$documentDir, 0664, true);
            }
        }
        // $result =  TPath::joinPath(self::$documentDir, ($private ? 'private' : 'public'));
        $result =  self::$documentDir;
        if ($folder) {
            $result = TPath::joinPath($result,$folder);
        }
        if ($fileName) {
            $result = TPath::joinPath($result,$fileName);
        }
        return $result;
    }

    public function getDocumentPath($document) {
        return self::getDocumentDir($document->folder,$document->filename);
    }

    private static function getMimeType($ext) {
        // mime types  https://www.lifewire.com/file-extensions-and-mime-types-3469109

        switch(strtolower($ext)) {
            case 'pdf' :
                return 'application/pdf';
            case 'txt' :
                return 'text/plain';
            case 'rtf' :
                return 'text/rtf';
            case 'doc' :
            case 'docx':
                return 'application/mssord';
            default:
                return 'application/octet-stream';
        }
    }

    /**
     * Stream document file from library
     * Called from routing function, depending on the CMS.
     * @param null $uri
     */
    public static function returnDocumentContent($uri=null) {
        if ($uri === null) {
            global $_SERVER;
            $uri = $_SERVER['REQUEST_URI'];
        }
        $docsUri = DocumentManager::getDocumentsUri();
        $args =  explode('/',substr($uri,strlen($docsUri)));

        self::outputDocumentContent($args);
    }

    public static function exitNotFound()
    {
        header("HTTP/1.0 404 Not Found");
        print TLanguage::text('document-open-error-no-file','Document not found in the library');
        exit;
    }

    public static function exitNotAuthorized() {
        header("HTTP/1.0 401  Unauthorized");
        print TLanguage::text('document-open-error-not-authorized',
            'This document is protected. You must sign in view it.');
        exit;
    }

    public static function openDocument(Document $document,$download=null) {
        if ($document->protected && !TUser::getCurrent()->isAuthenticated()) {
            self::exitNotAuthorized();
        };
        $filepath = self::getDocumentDir($document->folder,$document->filename);
        if (!file_exists($filepath)) {
            self::exitNotFound();
        }

        $ext = strtolower(pathinfo($document->filename, PATHINFO_EXTENSION));
        $mimetype = self::getMimeType($ext);

        if ($download === true || $ext !== 'pdf') {
            header("Content-Disposition: attachment; filename=$document->filename;");
        }

        header("Content-Type: $mimetype");
        header('Content-Length: ' . filesize($filepath));

        $data = file_get_contents($filepath);
        print $data;
        exit;
    }

    public function getDocumentsRepository() {
        return $this->documentsRepository;
    }

    /**
     * @var CommitteesRepository
     */
    private static $committeesRepository;
    private static function getCommitteesRepository() {
        if (!isset(self::$committeesRepository)) {
            self::$committeesRepository = new CommitteesRepository();
        }
        return self::$committeesRepository;
    }

    private static $groupsRepository;
    private static function getGroupsRepository() {
        if (!isset(self::$groupsRepository)) {
            self::$groupsRepository = new UsergroupsRepository();
        }
        return self::$groupsRepository;
    }

    public function getDocumentIndexRepository() {
        if (!isset($this->documentIndexRepository)) {
            $this->documentIndexRepository = new DocumentTextIndexRepository();
        }
        return $this->documentIndexRepository;
    }

    public function getProperties() {
        return $this->properties;
    }

    /**
     * @param $id
     * @return bool|Document
     */
    public function getDocument($id) {
        $document = $this->documentsRepository->get($id);
        return $document;
    }

    /**
     * @param $id
     * @return bool|Document
     */
    public function getDocumentByName($filename,$folder = null) {
        $document = $this->documentsRepository->getByName($filename,$folder);
        return $document;
    }


    public function getDocumentPropertyValues($id) {
        return $this->properties->getValues($id);
    }

    public function validatePropertyValues($propertyValues) {
        return $this->properties->validate($propertyValues);
    }

    /**
     * @param Document $document
     * @param $propertyValues
     * @param $committeeValues
     * @param $userName
     * @return false | Document
     */
    public function updateDocument(Document $document,$propertyValues,
                                   $committeeValues,
                                   $groupValues, $userName) {
        if (empty($document->id)) {
            $documentId = $this->documentsRepository->insert($document, $userName);
        } else {
            $documentId = $document->id;
            $this->documentsRepository->update($document, $userName);
        }

        if ($propertyValues !== null) {
            $this->properties->setValues($documentId,$propertyValues);
        }

        if ($committeeValues !== null) {
            $this->getCommitteesAssociation()->updateRightValues($documentId,$committeeValues);
        }

        if ($groupValues !== null) {
            $this->getGroupsAssociation()->updateRightValues($documentId,$groupValues);
        }

        return $this->documentsRepository->get($documentId);
    }

    public function checkDuplicateFiles($document) {
        $filename = TPath::normalizeFileName($document->filename);
        $dupes = $this->documentsRepository->findDuplicates($filename,$document->folder,$document->protected,$document->id);
        return $dupes;
    }

    public function documentFileExists(Document $document) {
        $filepath = self::getDocumentDir(trim($document->folder),$document->filename);
        return file_exists($filepath);
    }

    public function getMetaData() {
        $response = new \stdClass();
        $documentsRepository = $this->getDocumentsRepository();
        $properties = $documentsRepository->getEntityProperties();
        $response->properties = $properties->getLookupDefinitions(); // only lookup properties are supported at this time.
        $response->propertyLookups = $properties->getLookups();
        $response->committeeLookup =  self::getCommitteesRepository()->getCommitteeLookupItems();
        $response->groupLookup =  self::getGroupsRepository()->getUserGroupLookupItems();
        $response->addendumTypesLookup = $this->getAddendumTypesLookup();

        return $response;
    }

    public function getFileTypesLookup() {
        return (new LookupTableRepository('qnut_document_file_types'))->getLookupList(LookupTableRepository::noTranslation,null,LookupTableRepository::noSort);
    }

    public function searchDocuments($request) {
        return $this->getDocumentsRepository()->searchDocuments(
            $request,
            self::getDocumentsUri(),
            self::getFormPage());
    }

    public function deleteDocument($id)
    {
        $this->properties->dropValues($id);
        $this->getCommitteesAssociation()->removeAllLeft($id);
        $this->getGroupsAssociation()->removeAllLeft($id);
        $this->documentsRepository->delete($id);
    }


    public function indexDocuments(IMessageContainer $client,$timelimit=null) {
        $count = 0;
        $errors = 0;
        if ($timelimit) {
            $end = (new \DateTime())->modify($timelimit);
        }
        set_time_limit(0); // Allows script to run unlimited time

        $docs = $this->documentsRepository->getUnindexedDocuments();
        if (!empty($docs)) {
            foreach ($docs as $document) {
                $processed = $this->indexDocument($document, $client);
                if ($processed) {
                    $count++;
                } else {
                    $errors++;
                }
                if ($timelimit && (new \DateTime()) > $end) {
                    break;
                }
            }
        }
        $client->AddInfoMessage(
            $count + $errors == 0 ? 'No new documents to index.' : "Indexed $count documents, $errors documents had errors."
        );
    }

    public function indexDocumentById($id, IMessageContainer $client)
    {
        /**
         * @var Document
         */
        $document = $this->documentsRepository->get($id);
        if (empty($document)) {
            return false;
        }
        return $this->indexDocument($document, $client);
    }

    public function addErrorEntry($documentId,$errorMessage) {
        $indexRepository = $this->getDocumentIndexRepository();
        $entry = new DocumentIndexEntry();
        $entry->documentId = $documentId;
        $entry->statusMessage = $errorMessage;
        $entry->text = '';
        $entry->pageCount = 0;
        $entry->processedDate = date('Y-m-d H:i:s');
        $indexRepository->insert($entry);
    }

    public function indexDocument(Document $document, IMessageContainer $client, $errorLevel = 'error') {
        /**
         * @var $document Document
         */
        if (empty($document->filename)) {
            return false;
        }
        $ext = strtolower(pathinfo($document->filename, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return false;
        }
        $indexRepository = $this->getDocumentIndexRepository();
        $existing = $indexRepository->getByDocumentId($document->id);
        if ($existing) {
            $indexRepository->delete($existing->id);
        }
        $filePath = $this->getDocumentPath($document);
        try {
            $pdfResponse = PdfTextParser::ParseFile($filePath);
            if (!empty($pdfResponse->errors)) {
                if ($errorLevel == 'warning') {
                    $client->AddInfoMessage('Document updated but not able to index, Document #'.$document->id);
                }
                foreach ($pdfResponse->errors as $error) {
                    $error .= ", Document #".$document->id;
                    if ($errorLevel === 'warning') {
                        $client->AddInfoMessage($error);
                    }
                    else {
                        $client->AddErrorMessage($error);
                    }
                }
                $this->addErrorEntry($document->id,$error);
                return false;
            }
            if (empty($pdfResponse->document)) {
                $this->addErrorEntry($document->id,'No document returned by parser.');
                return false;
            }
            $text = $pdfResponse->getKeyText();
            if (empty($text)) {
                $this->addErrorEntry($document->id,'No text was extracted.');
                return false;
            }
            $properties = $pdfResponse->document->getDetails();

            $entry = new DocumentIndexEntry();
            $entry->documentId = $document->id;
            $entry->text = $text;
            $entry->author = array_key_exists('Author',$properties) ? $properties['Author'] : '';
            $entry->creationDate = array_key_exists('CreationDate',$properties) ? $properties['CreationDate'] : '';
            $entry->modificationDate = array_key_exists('ModDate',$properties) ? $properties['ModDate'] : '';
            $entry->pageCount = array_key_exists('Pages',$properties) ? $properties['Pages'] : '';
            $entry->processedDate = date('Y-m-d H:i:s');
            $indexRepository->insert($entry);
        }
        catch(\Exception $ex) {
            $error = 'Document indexing failed. '.$ex->getMessage();
            if ($errorLevel === 'warning') {
                $client->AddWarningMessage($error);
            }
            else {
                $client->AddErrorMessage($error);
            }
            $this->addErrorEntry($document->id,$error);
            return false;
        }

        return true;
    }

    /**
     * @param DocumentListFilter $filter
     * @return array
     */
    public function getDocumentList( $filter,$pageNumber=1)
    {

        $respository = $this->getDocumentsRepository();
        return $respository->getDocumentList($filter,$pageNumber,self::getDocumentsUri(),self::getFormPage());

    }
    public function getDocumentListCount(DocumentListFilter $filter,$publicOnly=false) {
        $respository = $this->getDocumentsRepository();
        return $respository->getDocumentListCount($filter);
    }

    public function getAssociatedCommittees($documentId)
    {
        return $this->getCommitteesAssociation()->getRightValues($documentId);
    }

    public function getAssociatedGroups($documentId)
    {
        return $this->getGroupsAssociation()->getRightValues($documentId);
    }

    public function getNewsletterCount() {
        return $this->getDocumentsRepository()->getNewsletterCount();
    }
    public function getNewsletterList($pageNo,$itemCount) {
        $uri = self::getDocumentsUri();
        $addendaUri = self::getAddendaUri();
        $editUrl = self::getFormPage();
        return $this->getDocumentsRepository()->getNewsletterList($pageNo,$itemCount,$uri,$editUrl,$addendaUri);
    }

    public function getNextNewsletterIssue($sequence = 1)
    {
        $result = new \stdClass();
        $current = $this->getDocumentsRepository()->getLastNewsletterDate();
        if ($current) {
            list($year, $month) = explode('-', $current);
            $month++;
            if ($month > 12) {
                $year++;
                $month = 1;
            }
            $result->month = $month;
            $result->year = $year;
        }
        else {
            $result->month = date('m');
            $result->year = date('Y');
        }
        $result->day = $sequence;
        // $result->issueNumber = sprintf('%d%02d%02d',$year,$month,$sequence);

        return $result;
    }

    public function getNewsletter(string $date)
    {
        return $this->getDocumentsRepository()->getNewsletterByDate($date);
    }

    public function publishNewsletter($id)
    {
        $repository = $this->getDocumentsRepository();
        $repository->protectDocs('newsletter');
        $repository->publishDoc($id);
    }

    public function getAddendumTypesLookup()
    {
        return (new LookupTableRepository(
            'qnut_document_addendum_types'))->getLookupList(LookupTableRepository::noTranslation,null,LookupTableRepository::noSort);

    }

    /**
     * @return array|\Tops\db\model\entity\LookupTableEntity[]
     */
    public function getDocumentTypeCode($typeId)
    {
        $types = (new LookupTableRepository(
            'qnut_document_types'))->getLookupList(LookupTableRepository::noTranslation,null,LookupTableRepository::noSort);
        foreach ($types as $type) {
            if ($type->id == $typeId) {
                return $type->code;
            }
        }
        return null;
    }

    public function getAddenda($docType, $publicationDate)
    {
        if (is_numeric($docType)) {
            $docType = $this->getDocumentTypeCode($docType);
        }
        $repository = $this->getDocumentsRepository();
        $uri = self::getDocumentsUri();
        $editPage = self::getFormPage();
        return $repository->getAddendaList(
            $docType,
            $publicationDate,
            $uri,
            $editPage
        );
    }

}