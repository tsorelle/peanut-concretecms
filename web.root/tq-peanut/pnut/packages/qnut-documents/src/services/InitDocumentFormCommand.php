<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 6/19/2018
 * Time: 5:16 PM
 */

namespace Peanut\QnutDocuments\services;

use Peanut\QnutCommittees\CommitteeManager;
use Peanut\QnutDocuments\DocumentManager;
use Tops\services\TServiceCommand;
use Tops\sys\TKeyValuePair;
use Tops\sys\TLanguage;
use Tops\sys\TNameValuePair;

/**
 * Class InitDocumentFormCommand
 * @package Peanut\QnutDocuments\services
 *
 * Contract:
 *      Request: documentId
 *
 *      Response:
 *     		interface IDocumentInitResponse {
 *     		    properties : Peanut.IPropertyDefinition[];
 *     		    propertyLookups: Peanut.ILookupItem[];
 *              committeeLookup: Peanut.ILookupItem[];
 *     		    translations : any[];
 *     		    canEdit: boolean;
 *     		    maxFileSize: any;
 *     		    documentsUri: string;
 *     		    searchUri: string,
 *     		    document: IDocumentRecord;
 *     		        interface IDocumentRecord {
 *     		            id : any;
 *     		            title : string;
 *     		            filename : string;
 *     		            folder : string;
 *     		            abstract : string;
 *     		            protected : boolean;
 *     		            publicationDate : string;
 *     		            properties : Peanut.IKeyValuePair[];
 *     		            committees : any[]
 *     				}
 *             }
*/
class InitDocumentFormCommand extends TServiceCommand
{
    protected function run()
    {
        $manager = new DocumentManager();
        $response = $manager->getMetaData();
        $response->maxFileSize = ini_get('upload_max_filesize');
        // $response->canEdit = false;
        $response->canEdit = $this->getUser()->isAuthorized(DocumentManager::manageLibraryPermission);
        $documentId = $this->getRequest();
        $response->searchPage = DocumentManager::getSearchPage();
        $response->documentsUri = DocumentManager::getDocumentsUri();
        if (empty($documentId)) {
            $response->document = null;
            $response->fileNeeded = true;
        } else {
            $response->document = $manager->getDocument($documentId);
            if (empty($response->document)) {
                $this->addErrorMessage('document-open-error-no-file');
                return;
            }
            $properties = $manager->getDocumentPropertyValues($documentId);
            $response->document->properties = TKeyValuePair::ExpandArray($properties);
            $response->document->committees = $manager->getAssociatedCommittees($documentId);
            $response->document->groups = $manager->getAssociatedGroups($documentId);
            $response->document->addenda = $manager->getAddenda(
                @$properties['doctype'],
                $response->document->publicationDate);

            $response->fileNeeded = !$manager->documentFileExists($response->document);
            if ($response->fileNeeded) {
                $response->document->filename = '';
                $response->document->folder = '';
            }
        }

        $response->translations = TLanguage::getTranslations([
            'document-entity',
            'committee-entity',
            'document-doc-type',
            'document-access-error',
            'document-error-page',
            'document-file-type',
            'document-new-button',
            'document-status-type',
            'document-search-return',
            'document-icon-label-view',
            'document-icon-label-download',
            'document-icon-label-edit',
            'document-icon-label-add',
            'document-icon-label-search',
            'document-committee-label',
            'document-file-not-assigned',
            'document-confirm-delete',
            'document-conflict',
            'document-search-button',
            'document-edit-return',
            'document-conflicts',
            'document-committees-none',
            'document-groups-none',
            'label-clear-form',
            'label-publication-date',
            'label-doc-type',
            'label-publication-date',
            'label-save-changes',
            'label-title',
            'label-cancel',
            'label-delete',
            'label-remove',
            'label-addendum-type',
            'label-addendum-date',
            'document-label-access',
            'document-label-abstract',
            'document-label-upload',
            'document-label-replace',
            'document-label-folder',
            'document-label-protected',
            'document-label-replace-file',
            'document-file-name',
            'document-label-file-folder',
            'document-label-access',
            'document-access-protected',
            'document-access-public',
            'document-label-abstract',
            'form-error-message',
            'document-label-upload-file',
            'document-label-assign-file',
            'document-label-select-file',
            'document-error-title',
            'document-error-publicationdate',
            'document-error-abstract',
            'document-error-file-select',
            'document-error-filename',
            'wait-action-update',
            'wait-action-delete',
            'wait-action-add',
            'wait-action-remove',
            'document-committees-label',
            'document-groups-label',
            'document-committees-caption',
            'document-groups-caption',
            'label-addendum-comment'
        ]);


        $this->setReturnValue($response);


    }
}