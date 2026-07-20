<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/13/2018
 * Time: 7:28 AM
 */

namespace Peanut\QnutDocuments\services;


use Tops\services\TServiceCommand;
use Peanut\QnutDocuments\db\model\entity\Document;
use Peanut\QnutDocuments\DocumentManager;
use Tops\services\TUploadHelper;
use Tops\sys\TConfiguration;
use Tops\sys\TDates;
use Tops\sys\TKeyValuePair;
use Tops\sys\TLanguage;
use Tops\sys\TPath;

/**
 * Class UpdateDocumentCommand
 * @package Peanut\QnutDocuments\services
 *
 * Contract:
 *  Request;
 *     interface IDocumentUpdateRequest {
 *          document: IDocumentRecord;
 *          fileDisposition: string;
 *          propertyValues: KVPair[]
 *     }
 */
class UpdateDocumentCommand extends TServiceCommand
{
    public function __construct() {
        $this->addAuthorization(DocumentManager::manageLibraryPermission);
    }

    /**
     * @throws \Exception
     */
    protected function run()
    {
        // validate request

        $request = $this->getRequest();
        if (!$request) {
            $this->addErrorMessage('service-no-request');
            return;
        }

        if (!isset($request->document)) {
            $this->addErrorMessage('document-error-no-document');
            return;
        }

        if (!isset($request->document->id)) {
            $this->addErrorMessage('error-no-id');
            return;
        }

        if (empty($request->document->title)) {
            $this->addErrorMessage('document-update-error-no-title');
            return;
        }

        if (!empty(@$request->document->addendumType)) {
            if (!TDates::isValidDateString(@$request->document->addendumDate)) {
                $this->addErrorMessage('Invalid addendum date'); // todo: translate
                return;
            }
            if ($request->document->addendumType == 1) {
                // monthly newsletter must be first of month
                $request->document->addendumDate = (new \DateTime(@$request->document->addendumDate))->format('Y-m-01');
            }
        }


        $request->document->folder = isset($request->document->folder) ?
            TPath::normalizeFilePath($request->document->folder) : '';


        $propertyValues = isset($request->propertyValues) ?
            TKeyValuePair::FlattenArray($request->propertyValues) :
            array();

        $documentManager = new DocumentManager();

        if (!empty($propertyValues)) {
            $errors = $documentManager->validatePropertyValues($propertyValues);
            if ($errors !== true) {
                foreach ($errors as $error) {
                    $this->addErrorMessage($error);
                }
                return;
            }
        }

        // get document and current location

        $documentId = $request->document->id;
        if (empty($documentId)) {
            $document = new Document();
            $currentFileLocation = '';
        } else {
            $document = $documentManager->getDocument($documentId);
            if (empty($document)) {
                $documentEntityName = TLanguage::text('document-entity');
                $this->addErrorMessage('error-entity-id-not-found', [$documentEntityName, $documentId]);
                return;
            }
            $currentFileLocation = $documentManager->getDocumentPath($document);
        }
        // get uploaded file name

        $fileNames = TUploadHelper::filesReady($this->getMessages());
        if ($this->hasErrors()) {
            return;
        }
        $fileCount = count($fileNames);
        if ($fileCount) {
            $request->document->filename =  $fileNames[0];
        }

        // get new file location

        $request->document->filename = TPath::normalizeFileName($request->document->filename);
        $newFileLocation = $documentManager->getDocumentPath($request->document);

        // check for existing conflicts

        $duplicates = $documentManager->checkDuplicateFiles($request->document);
        if (!empty($duplicates)) {
            $this->addErrorMessage('document-error-conflicts');
            $response = new \stdClass();
            $response->conflicts = $duplicates;
            $this->setReturnValue($response);
            return;
        }

        // make document dir

        $documentDir = DocumentManager::getDocumentDir($request->document->folder);
        if (!is_dir($documentDir)) {
            if (!@mkdir($documentDir, 0777, true)) {
                    $this->addErrorMessage('document-error-mkdir-failed');
                    return;
            };
        }

        // check for moving target
        $fileMoved = (!empty($currentFileLocation)) && ($currentFileLocation != $newFileLocation) ;

        $newFileAssigned = true;
        // place file in expected location
        switch($request->fileDisposition) {
            case 'replace' :
            case 'upload' :
                if (file_exists($currentFileLocation)) {
                    unlink($currentFileLocation);
                }
                if ($fileCount < 1) {
                    $this->addErrorMessage('document-update-error-no-upload');
                    return;
                }
                $uploadedFiles = TUploadHelper::upload($this->getMessages(), $documentDir);
                if ($this->hasErrors()) {
                    return;
                }
                if (empty($uploadedFiles)) {
                    $this->addErrorMessage('SYSTEM ERROR: Cannot get uploaded file');
                    return;
                }
                break;
            case 'assign' :
                // no action, file should exist in new location
                if ($fileCount) {
                    $this->addWarningMessage('document-warning-unexpected-upload');
                }
                break;
            default : // 'none'
                if ($fileCount) {
                    $this->addWarningMessage('document-warning-unexpected-upload');
                }
                if ($fileMoved && file_exists($currentFileLocation)) {
                    rename($currentFileLocation, $newFileLocation);
                }
                else {
                    $newFileAssigned = false;
                }
                break;
        }

        // confirm that the file is in its expected location.
        if (!file_exists($newFileLocation)) {
            $this->addErrorMessage('document-update-error-no-file');
            return;
        }

        // update and refresh document data
        $document->assignFromObject($request->document);
        $associatedCommittees = is_array($request->document->committees) ? $request->document->committees : null;
        $associatedGroups = is_array($request->document->groups) ? $request->document->groups : null;
        $response = $documentManager->updateDocument($document,$propertyValues,$associatedCommittees,$associatedGroups,$this->getUser()->getUserName());
        if ($response === false ) {
            $this->addErrorMessage('error-update-failed');
            return;
        }
        $documentId = $response->id;
        $properties = $documentManager->getDocumentPropertyValues($documentId);
        $response->properties = TKeyValuePair::ExpandArray($properties);
        $response->committees = $documentManager->getAssociatedCommittees($documentId);
        $response->groups = $documentManager->getAssociatedGroups($documentId);

        // clean up, delete unused file
        if ($fileMoved) {
            @unlink($currentFileLocation);
        }

        if ($newFileAssigned && TConfiguration::getValue('indexing','documents','batch') === 'immediate') {
            $documentManager->indexDocument($response,$this->getMessages(),'warning');
        }

        $this->setReturnValue($response);
    }
}