<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/19/2019
 * Time: 5:22 AM
 */

namespace Peanut\QnutDocuments\services;


use Peanut\QnutDocuments\DocumentManager;
use Tops\services\TServiceCommand;
use Tops\sys\TLanguage;
use Tops\sys\TWebSite;

class GetDocumentAddendaCommand extends TServiceCommand
{
    /**
     *
     * Service contract:
     * Response
     *
     *     interface IDocumentAddendaInitReaponse {
     *         document: IDocumentReference;
     *         addenda: IDocumentReference[];
     *         pageTitle: string;
     *         translations: any[];
     *         documentDescription: string;
     *         downloadHref: string;
     *         viewPdfHref: string;
     *      }
     *
     *     export interface IDocumentReference {
     *         id: any,
     *         title: string,
     *         publicationDate: any,
     *         documentType: string,
     *         viewUrl: string,
     *         downloadUrl : string,
     *         editUrl: string
     *         description: string
     *     }
     *
     */
    protected function run()
    {
        $response = new \stdClass();
        $manager = new DocumentManager();
        $response->canEdit = $this->getUser()->isAuthorized(DocumentManager::manageLibraryPermission);
        $documentId = $this->getRequest();
        // $response->searchPage = DocumentManager::getSearchPage();

        if (empty($documentId)) {
            $this->addErrorMessage('No document id.'); // todo: translate
            return;
        }

        $document = $manager->getDocument($documentId);
        if (empty($document)) {
            $this->addErrorMessage('document-open-error-no-file');
            return;
        }

        $response->document = new \stdClass();
        $response->document->id				  = $document->id;
        $response->document->title            = $document->title;
        $response->document->publicationDate  = $document->publicationDate;
        $response->document->documentType = strtoupper(pathinfo(@$document->filename, PATHINFO_EXTENSION));
        $href = DocumentManager::getDocumentsUri().$documentId;
        $response->document->viewUrl = $response->document->documentType == 'PDF' ? $href : '';
        $response->document->downloadUrl = $href.'/download';
        $formPage = DocumentManager::getFormPage();
        $response->document->editUrl = TWebSite::AppendRequestParams($formPage,['id' => $documentId]);
        $properties = $manager->getDocumentPropertyValues($documentId);
        $typeCode = $manager->getDocumentTypeCode(@$properties['doctype']);
        $response->document->description = $typeCode === 'newsletter' ? '' : $document->abstract;

        $response->addenda = $manager->getAddenda(
            $typeCode,
            $response->document->publicationDate);

        $date =  new \DateTime($response->document->publicationDate);

        if ($typeCode == 'newsletter') {
            $response->pageTitle = "Newsletter Addenda ".$date->format('F Y');
            // todo: translate
            $response->pageIntro = "Newsletter addenda may include documents or handouts from Business Meeting, as well as other related documents that were too lengthy to include in the printed newsletter.";
        }
        else {
            // todo: support committee minutes addenda
            $response->pageIntro = null;
            $response->pageTitle = null;
        }

        $response->translations = TLanguage::getTranslations([
            'document-entity',
            'document-doc-type',
            'document-access-error',
            'document-icon-label-view',
            'document-icon-label-download',
            'document-icon-label-edit',
            'document-icon-label-add',
            'document-icon-label-search',
            'label-publication-date',
            'label-doc-type',
            'label-publication-date',
            'label-title',
            'label-description',
        ]);


        $this->setReturnValue($response);


    }

}