<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/28/2019
 * Time: 8:18 AM
 */

namespace Peanut\QnutDocuments\services;


use Peanut\PeanutMailings\sys\MailTemplateManager;
use Peanut\QnutDocuments\db\model\entity\Document;
use Peanut\QnutDocuments\DocumentManager;
use Tops\services\TServiceCommand;
use Tops\services\TUploadHelper;
use Tops\sys\TConfiguration;
use Tops\sys\TPath;
use Tops\sys\TPermissionsManager;
use Tops\sys\TWebSite;

/**
 * Class PostNewsletterCommand
 * @package Peanut\QnutDocuments\services
 *
 * Service contract:
 *      Request:
 *        interface IPostNewsletterRequest {
 *           issueDate: IDateStructure {
 *   			month: any;
 *   			year: any;
 *   			day: any;
 *   		}
 *          newfile: any; (boolean)
 *        }
 *		  In http request: File upload
 *
 *      Response:
 *         interface IPostNewsletterResponse {
 *              documentId: number;
 *             messageText: string;
 *         }
 */
class PostNewsletterCommand extends TServiceCommand
{

    public function __construct() {
        $this->addAuthorization(TPermissionsManager::sendMailingsPermissionName);
    }

    private function getFileName($date)
    {
        $fileFormat = TConfiguration::getValue('newsletter-filename', 'documents', 'newsletter-{date}');
        return str_replace('{date}', $date, $fileFormat) . '.pdf';
    }

    private function getTitle($date)
    {
        $titleFormat = TConfiguration::getValue('newsletter-title', 'documents',
            'Newsletter #{condensed-date} - {month} {year}');
        $ts = strtotime($date);
        $month = date('F', $ts);
        $year = date('Y', $ts);
        $condensedDate = date('Ymd', $ts);
        return str_replace('{condensed-date}', $condensedDate,
            str_replace(
                '{month}', $month,
                str_replace('{year}', $year, $titleFormat)
            ));
    }

    private function getAbstract($date)
    {
        $format = TConfiguration::getValue('newsletter-abstract', 'documents',
            "Monthly newsletter {month}");
        $ts = strtotime($date);
        $month = date('F', $ts);
        return str_replace('{month}', $month, $format);
    }

    private function getUploadedFile($rename)
    {
        $fileNames = TUploadHelper::filesReady($this->getMessages());
        if ($this->hasErrors()) {
            return false;
        }
        $fileCount = count($fileNames);
        if ($fileCount) {
            $documentDir = DocumentManager::getDocumentDir('newsletter');
            if (!is_dir($documentDir)) {
                if (!@mkdir($documentDir, 0777, true)) {
                    $this->addErrorMessage('document-error-mkdir-failed');
                    return false;
                };
            }
            $uploadedFiles = TUploadHelper::upload($this->getMessages(), $documentDir,$rename);
            if ($this->hasErrors()) {
                return false;
            }
            if (empty($uploadedFiles)) {
                $this->addErrorMessage('SYSTEM ERROR: Cannot get uploaded file');
                return false;
            }
        }
        return $fileCount;
    }

    protected function run()
    {
        $request = $this->getRequest();
        if (!$request) {
            $this->addErrorMessage('service-no-request');
            return;
        }

        if (empty($request->issueDate) || empty($request->issueDate->month || empty($request->issueDate->year))) {
            $this->addErrorMessage('newsletter-invalid-date');
            return;
        }
        // if (empty($request->issueDate->day)) {
            $request->issueDate->day = 1; // issue date always first of month.  Modify if more than one issue per month.
        // }

        $date = sprintf('%d-%02d-%02d',
            $request->issueDate->year,
            $request->issueDate->month,
            $request->issueDate->day
        );

        $propertyValues = null;
        $manager = new DocumentManager();
        $doc = $manager->getNewsletter($date);
        $newIssue = (empty($doc));
        if ($newIssue) {
            $doc = new Document();
            $doc->id = 0;
            $doc->filename = $this->getFileName($date);
            $doc->title = $this->getTitle($date);
            $doc->folder = 'newsletter';
            $doc->abstract = $this->getAbstract($date);
            $doc->protected = 1;
            $doc->publicationDate = $date;

            // todo: get from database
            $propertyValues = [
                'status' => 4,
                'doctype' => 8
            ];
        }
        $newfile = $newIssue || !empty($request->newFile);
        if ($newfile) {
            $upload = $this->getUploadedFile($doc->filename);
            if ($upload === false) {
                return;
            }
            if ($upload == 0) {
                $this->addErrorMessage('document-update-error-no-upload');
                return;
            }
        }

        $doc = $manager->updateDocument($doc, $propertyValues, null, null, $this->getUser()->getUserName());
        if ($doc === false) {
            $this->addErrorMessage('error-update-failed');
            return;
        }

        if ($newfile && TConfiguration::getValue('indexing','documents','batch') === 'immediate') {
            $manager->indexDocument($doc, $this->getMessages());
        }

        $response = new \stdClass();
        // if (!empty($request->publish)) {
            // $manager->publishNewsletter($doc->id);
            $response->messageText = $this->buildMessage($doc);
            $response->documentId = $doc->id;
        // }

        $this->setReturnValue($response);
    }

    private function buildMessage(Document $doc)
    {
        $documentUrl = DocumentManager::getDocumentsUri();
        $newslettersLink = TConfiguration::getValue('newsletters','pages','/newsletters');
        $ts = strtotime($doc->publicationDate);
        $issueDate = date('F Y', $ts);

        return MailTemplateManager::CreateMessageText('MonthlyNewsletter.html',[
            'issue-date' => $issueDate,
            'document-id' => $doc->id,
            'document-url' => $documentUrl,
            'newsletters-link' => $newslettersLink
        ]);
    }
}