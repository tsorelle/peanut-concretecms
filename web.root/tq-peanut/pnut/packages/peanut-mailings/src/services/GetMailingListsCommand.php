<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/11/2017
 * Time: 4:57 AM
 */

namespace Peanut\PeanutMailings\services;

use Peanut\PeanutMailings\db\model\repository\EmailListsRepository;
use Peanut\PeanutMailings\sys\MailTemplateManager;
use Tops\services\TServiceCommand;
use Tops\sys\TConfiguration;
use Tops\sys\TLanguage;

/**
 * Class GetMailingLists
 * @package Peanut\Mailings\services
 *
 * Service contract
 *      Request: none
 *      Response:
 *          interface IGetMailingListsResponse {
 *              emailLists : Peanut.ILookupItem[];
 *              translations : string[string];
 *              templates : string[string][string]
 *          }
 */
class GetMailingListsCommand extends TServiceCommand
{

    protected function run()
    {

        $result = new \stdClass();
        $repository = new EmailListsRepository();
        $isAdmin = $this->getUser()->isAdmin();
        $result->emailLists = $repository->getSubscriptionListLookup($isAdmin,!$isAdmin);
        $result->defaultListCode = TConfiguration::getValue('defaultmailinglist','mail','meeting');
        $result->userEmail = $this->getUser()->getEmail();
        $result->translations = TLanguage::getTranslations([
            'add-new',
            'confirm-caption',
            'dir-label-email-queue',
            'dir-label-please-select',
            'dir-label-posted',
            'dir-label-queue-processing',
            'dir-label-sender',
            'form-error-email-invalid',
            'form-error-name-blank',
            'form-error-email-subject-blank',
            'form-error-email-message-blank',
            'form-error-email-blank',
            'form-error-email-invalid',
            'label-active',
            'label-add',
            'label-address',
            'label-cancel',
            'label-code',
            'label-count',
            'label-delete',
            'label-description',
            'label-edit',
            'label-email',
            'label-mailbox',
            'label-message',
            'label-name',
            'label-new',
            'label-of',
            'label-public',
            'label-refresh',
            'label-remove',
            'label-save',
            'label-save-changes',
            'label-status',
            'label-subject',
            'label-until',
            'label-update',
            'label-yes',
            'mail-header-select',
            'mail-header-send',
            'mailbox-entity',
            'mailbox-entity-plural',
            'mailbox-error-code-blank',
            'mailbox-error-description',
            'mailbox-error-email-name',
            'mailbox-label-add-new',
            'mailbox-label-delete',
            'mailbox-label-public',
            'mailing-confirm-resend',
            'mailing-confirm-send',
            'mailing-control-heading',
            'mailing-heading-lists',
            'mailing-get-history',
            'mailing-history-heading',
            'mailing-label-format',
            'mail-label-cansubscribe',
            'mail-label-adminonly',
            'mailing-label-list',
            'mailing-list-entity',
            'mailing-message-template',
            'mailing-no-template',
            'mailing-send-mailing',
            'mailing-show-html',
            'mailing-show-text',
            'mailing-test-template',
            'process-command-continue',
            'process-command-pause',
            'send-test-message',
            'send-test-message2',
            'status-active',
            'process-status-paused',
            'process-status-ready',
            'mailing-message-entity',
            'mailing-remove-queue',
            'mailing-remove-header',
            'wait-action-send'
        ]);

        // $result->templates = (new MailTemplateManager)->getTemplateFileList();

        $this->setReturnValue($result);
    }
}