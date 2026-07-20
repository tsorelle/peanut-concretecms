<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/23/2019
 * Time: 6:03 PM
 */

namespace Peanut\QnutDocuments\services;


use Peanut\QnutDocuments\DocumentManager;
use Tops\services\TServiceCommand;
use Tops\sys\TConfiguration;
use Tops\sys\TLanguage;
use Tops\sys\TNameValuePair;

/**
 * Class GetNewsletterListCommand
 * @package Peanut\QnutDocuments\services
 *
 * Service contract:
 *      Request:
 *      {
 *          page: number,
 *          itemsPerPage: number
 *      }
 *
 *      Response:
 *        interface IGetNewslettersResponse {
 *            documents : IDocumentSearchResult[];
 *				 export interface IDocumentSearchResult {
 *					id: any,
 *					title: string,
 *					publicationDate: string,
 *					uri: string,
 *					editUrl: string,
 *					documentType: string
 *				}
 *        }
 *
 *        interface INewslettersInitResponse extends  IGetNewslettersResponse{
 *            translations : any[];
 *            recordCount: any;
 *            newDocumentHref: string;
 *            canSend: any;
 *            monthNames: string[];
 *            nextIssue : any
 *            queueLink: string;
 *        }
 *
 */
class GetNewsletterListCommand extends TServiceCommand
{

    private function getMonthNames()
    {
        $months = explode(',',TLanguage::text('calendar-months-of-year'));
        if (count($months) != 12) {
            $months = ['January','February','March','April','May','June','July',
                'August','September','October','November','December'];
        }
        return $months;
    }

    protected function run()
    {
        $request = $this->getRequest();
        $pageNumber = empty($request->page) ? 0 : $request->page;
        $itemsPerPage = empty($request->itemsPerPage) ? 12 : $request->itemsPerPage;
        $response = new \stdClass();
        $manager = new DocumentManager();
        if ($pageNumber < 1) {
            // get initializations
            $recordCount = $manager->getNewsletterCount();
            $pageNumber = ceil($recordCount / $itemsPerPage);
            $response->nextIssue = $manager->getNextNewsletterIssue();
            $response->months     =   $this->getMonthNames();
            $response->recordCount = $recordCount;
            $response->canSend = $this->getUser()->isAuthorized('send-mailings');
            $response->queueLink = TConfiguration::getValue('emailManagement','pages','/tasks/email-management/send-messages').'?tab=queue';
            // $response->newDocumentHref = DocumentManager::getFormPage();
            $response->translations = TLanguage::getTranslations(array(
                'committee-entity',
                'document-search-publication-date',
                'document-search-return',
                'document-icon-label-view',
                'document-icon-label-download',
                'document-icon-label-open',
                'document-label-select-file',
                'label-publication-date',
                'label-date',
                'label-end-date',
                'label-title',
                'newsletter-latest',
                'newsletter-earliest',
                'newsletter-label-list',
                'newsletter-label-send',
                'newsletter-result-message1',
                'newsletter-result-message2',
                'newsletter-result-message3',
                'newsletter-send-error',
                'send-test-message',
                'newsletter-label-message',
                'newsletter-send-mailing',
                'newsletter-result-message-sent'
        ));
        }
        $response->documents = $manager->getNewsletterList($pageNumber,$itemsPerPage);
        $this->setReturnValue($response);

    }
}