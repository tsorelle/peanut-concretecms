<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 4/27/2019
 * Time: 6:34 AM
 */

namespace Peanut\QnutCalendar\services;


use Peanut\QnutCalendar\db\model\CalendarEventManager;
use Peanut\QnutCalendar\db\model\entity\CalendarNotification;
use Peanut\PeanutMailings\db\model\entity\EmailList;
use Peanut\PeanutMailings\db\model\entity\EmailMessage;
use Peanut\PeanutMailings\sys\MailTemplateManager;
use Soundasleep\Html2Text;
use Tops\mail\TEMailMessage;
use Tops\mail\TPostOffice;
use Tops\services\TServiceCommand;
use Tops\sys\TConfiguration;
use Tops\sys\TDates;
use Tops\sys\TLanguage;
use Tops\sys\TPermissionsManager;
use Tops\sys\TTemplateManager;
use Tops\sys\TWebSite;

class SendUserGroupNotificationsCommand extends TServiceCommand
{

    private $templates;

    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::appAdminPermissionName);
    }


    /**
     * @throws \Exception
     */
    protected function run()
    {
        $datearg = $this->getRequest();
        if (empty($datearg)) {
            $rundate = date('Y-m-d');
        } else {
            $rundate = TDates::getValidDate($datearg);
            if ($rundate === false) {
                $this->addErrorMessage("Invalid date argument: $datearg");
                return;
            }
        }
        $this->addInfoMessage('User group notifications started: ' . $rundate);

        $manager = new CalendarEventManager();
        $notifications = $manager->getUserGroupNotifications($rundate);
        if (empty($notifications)) {
            $this->addInfoMessage('No group notifications were scheduled.');
            return;
        }

        $messageCount = 0;
        foreach ($notifications->events as $eventItem) {
            $group = $notifications->groups[$eventItem->groupCode];
            $message = $this->createMessage($eventItem->event, $group);
            foreach ($group->recipients as $recipient) {
                $message->setRecipient($recipient->email,$recipient->fullname);
                $result = TPostOffice::Send($message);
            }
            $messageCount++;
        }
        $this->addInfoMessage('Group notifications completed. ' . $messageCount . ' messages queued.');
    }

    /**
     * @param CalendarNotification $event
     * @param $recipientCount
     * @param string $format
     * @return TEmailMessage
     * @throws \Exception
     */
    private function createMessage(CalendarNotification $event, $group)
    {
        $message = new TEmailMessage();
        $today = new \DateTime();
        $message->setSender($group->email,$group->name);
        $message->setSubject($event->title);
        $htmlPart = $this->buildMessageText($event,$group,'html');
        $textPart = $this->buildMessageText($event,$group,'txt');
        $message->setMessageBody($htmlPart,$textPart);
        return $message;
    }

    /**
     * @param CalendarNotification $event
     * @param $format
     * @throws \Exception
     */
    private function buildMessageText(CalendarNotification $event, $group, $format) {
        $messageText  = $event->getMessageText($format);
        $templates = $this->getMessageTemplates();
        return TTemplateManager::ReplaceContentTokens($templates[$format], [
            'content' => $messageText,
            'group-name' => $group->name
        ]);
    }

    private function getMessageTemplates() {
        if (!isset($this->templates)) {
            $this->templates = [];
            $templateManager = new MailTemplateManager();
            $this->templates['html'] = $this->getMessageTemplate($templateManager,'html');
            $this->templates['txt']  = $this->getMessageTemplate($templateManager,'txt');
        }

        return $this->templates;
    }

    private function getMessageTemplate(MailTemplateManager $templateManager,$contentType) {
        $siteUrl = TWebSite::GetSiteUrl();
        $groupsPage = TConfiguration::getValue('groups-page','pages','/community/groups');

        $footerTemplate = $templateManager->getTemplateContent('footer.'.$contentType);
        $messageFooter = TTemplateManager::ReplaceContentTokens($footerTemplate, ['siteUrl' => $siteUrl]);
        $mainTemplate = $templateManager->getTemplateContent('GroupNotifications.'.$contentType);
        return TTemplateManager::ReplaceContentTokens($mainTemplate, [
            'siteUrl' => $siteUrl,
            'group-page' => $groupsPage,
            'footer' => $messageFooter
        ]);
    }

}