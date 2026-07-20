<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 6/19/2018
 * Time: 5:14 PM
 */
namespace Peanut\QnutDocuments\services;
use Peanut\QnutDocuments\DocumentManager;
use Peanut\QnutDocuments\PdfTextParser;
use Smalot\PdfParser\Parser;
use Tops\sys\TConfiguration;
use Tops\sys\TLanguage;

/**
 * Class InitDocumentSearchCommand
 * @package Peanut\QnutDocuments\services
 *
 * Contract:
 *    Response:
 *
 *      interface IDocumentSearchInitResponse {
 *          properties : Peanut.IPropertyDefinition[];
 *          propertyLookups: Peanut.ILookupItem[];
 *          fileTypes: ILookupItem[];
 *          fullTextSupported : boolean;
 *          translations : any[];
 *      }
 */
class InitDocumentSearchCommand extends \Tops\services\TServiceCommand
{

    protected function run()
    {
        $manager = new DocumentManager();
        $response = $manager->getMetaData();
        $response->userCanEdit = $this->getUser()->isAuthorized(DocumentManager::manageLibraryPermission);;
        $response->userIsAuthenticated = $this->getUser()->isAuthenticated();;
        $response->fileTypes = $manager->getFileTypesLookup();
        $response->translations = TLanguage::getTranslations([
            'committee-entity',
            'date-search-mode-after',
            'date-search-mode-before',
            'date-search-mode-between',
            'date-search-mode-on',
            'document-doc-type',
            'document-file-type',
            'document-search-button',
            'document-new-button',
            'document-search-found',
            'document-search-not-found',
            'document-search-dropdown-caption',
            'document-search-publication-date',
            'document-search-return',
            'document-search-terms',
            'document-search-text',
            'document-search-full-text',
            'document-search-keyword-option',
            'document-search-literal-option',
            'document-search-type-full-text',
            'document-search-type-info',
            'document-search-type-lookup',
            'document-status-type',
            'document-icon-label-view',
            'document-icon-label-download',
            'document-icon-label-open',
            'document-committee-label',
            'label-clear-form',
            'label-publication-date',
            'label-doc-type',
            'label-date',
            'label-end-date',
            'label-fulltext',
            'label-title',
            'document-label-filename',
            'document-label-documentid'
        ]);
        $response->fullTextSupported = TConfiguration::getValue('indexing','documents','none') !== false;
        $response->newDocumentHref = DocumentManager::getFormPage();
        // $response->fullTextSupported = false;
        $this->setReturnValue($response);
    }
}