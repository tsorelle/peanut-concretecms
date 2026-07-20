<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/21/2018
 * Time: 7:39 AM
 */

namespace Peanut\QnutDocuments\services;


use Peanut\QnutDocuments\DocumentManager;
use Tops\services\TServiceCommand;
use Tops\sys\TNameValuePair;
use Tops\sys\TUser;

/**
 * Class FindDocumentsCommand
 * @package Peanut\QnutDocuments\services
 *
 * Service Contract:
 *   Request:
 *     interface IDocumentSearchRequest {
 *         title: string,
 *         keywords: string,
 *         fulltext: boolean,
 *         dateSearchMode: any,
 *         firstDate: any,
 *         secondDate: any,
 *         properties: string[]
 *         pageNumber: any,
 *          itemsPerPage: any
 *         }
 *   Response:
 *       Array of interface IDocumentSearchResult {
 *         id: any,
 *         title: string,
 *         publicationDate: string,
 *         uri: string;
 *         editUrl: string,
 *         documentType: string
 *      }
 */

class FindDocumentsCommand extends TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        $request->publicOnly = (!TUser::getCurrent()->isAuthenticated());

        $searchType = $request->searchType ?? null;
        switch($searchType) {
            case 'info' :
                break;
            case 'text' :
                if ( empty($request->searchText)) {
                    $this->addErrorMessage('document-error-no-text');
                    return;
                }
                break;
            case 'lookup':
                if (empty($request->documentId) && empty($request->filename)) {
                    $this->addErrorMessage('You must enter a file name or document id.');
                    return;
                }
                break;
            default:
                $this->addErrorMessage('Invalid search type: '.$searchType);
                return;
        }

        $response = DocumentManager::getInstance()->searchDocuments($request);
        $this->setReturnValue($response);
    }
}