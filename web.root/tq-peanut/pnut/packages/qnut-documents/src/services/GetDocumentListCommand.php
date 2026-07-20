<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/30/2019
 * Time: 6:59 AM
 */

namespace Peanut\QnutDocuments\services;


use Peanut\QnutDocuments\DocumentListFilter;
use Peanut\QnutDocuments\DocumentManager;
use Peanut\sys\TVmContext;
use Tops\services\TServiceCommand;
use Tops\sys\TDates;
use Tops\sys\TLanguage;

class GetDocumentListCommand extends TServiceCommand
{
    /**
     * Service contract
     *	 Request
     *     interface IGetDocumentListRequest {
     *       pageNumber: number | not assigned;
     *       filter: IDocumentListFilter (see Peanut\QnutDocuments\DocumentListFilter)
     *     }
     *
     *     or anonymous {
     *          context:
     *      }
     *
     * 	Response
     *     interface IGetDocumentListResponse {
     *     	  documentList : IDocumentSearchResult[];
     *     }
     *
     *     interface IInitDocumentListResponse extends {
     *     	    documentList : IDocumentSearchResult[];
     *     	    filter: IDocumentListFilter (see Peanut\QnutDocuments\DocumentListFilter)
     *          recordCount: number;
     *     	    translations : any[];
     *     }
     */
    protected function run()
    {
        $manager = DocumentManager::getInstance();

        $request = $this->getRequest();
        $response = new \stdClass();

        /**
         * @var DocumentListFilter
         */
        $filter = null;
        $initializing = isset($request->context);
        if ($initializing) {
            $filter = $manager->buildFilterFromContext($request->context);
            if ($filter !== null) {
                $filter->publicOnly = !$this->getUser()->isAuthenticated();
                $response->recordCount = $manager->getDocumentListCount($filter);
                $response->translations = TLanguage::getTranslations([
                    'committee-entity',
                    'document-doc-type',
                    'document-file-type',
                    'document-search-found',
                    'document-search-not-found',
                    'document-status-type',
                    'document-icon-label-view',
                    'document-icon-label-open-doc',
                    'document-icon-label-download',
                    'document-icon-label-info',
                    'label-clear-form',
                    'label-published',
                    'label-doc-type',
                    'label-date',
                    'label-end-date',
                    'label-title',
                    'document-search-hidden'
                ]);
            }
        }
        else {
            $filter = $this->getFilterFromRequest($request);
        }

        if ($filter == null) {
            $this->setError('document-error-no-filter');
            return;
        }
        $response->filter = $filter;
        $pageNumber = empty($request->pageNumber) ? 1 : $request->pageNumber;
        $response->documentList = $manager->getDocumentList($filter, $pageNumber);
        $this->setReturnValue($response);
    }

    private function getFilterFromRequest($request) {
        $dto = @$request->filter;
        if (!$dto) {
            $this->addErrorMessage('No filter data in request.');
            return false;
        }
        $filter = new DocumentListFilter();
        foreach($dto as $key => $value) {
            if (property_exists($filter, $key)) {
                $filter->$key = $value;
            }
        }
        return $filter;

    }

}