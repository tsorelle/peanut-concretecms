<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 4/28/2019
 * Time: 5:35 AM
 */

namespace Peanut\QnutCalendar\services;


use Concrete\Core\Calendar\Calendar\PermissionsManager;
use Peanut\QnutCalendar\db\model\CalendarEventManager;
use Peanut\QnutCalendar\db\model\entity\CalendarEvent;
use Peanut\QnutCalendar\db\model\entity\CalendarNotification;
use Peanut\QnutCommittees\CommitteeTaskManager;
use Peanut\QnutCommittees\db\model\repository\CommitteeMembersRepository;
use Peanut\PeanutMailings\sys\MailTemplateManager;
use Tops\mail\TEMailMessage;
use Tops\mail\TPostOffice;
use Tops\services\TServiceCommand;
use Tops\sys\TConfiguration;
use Tops\sys\TDates;
use Tops\sys\TLanguage;
use Tops\sys\TPermissionsManager;
use Tops\sys\TTemplateManager;
use Tops\sys\TWebSite;

class SendCommitteeNotificationsCommand extends TServiceCommand
{

    private $dateRange;

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
        $defaultDow = 'saturday';
        if (empty($datearg)) {
            $startDate = TDates::GetNextDayOfWeek($defaultDow);
        } else {
            $rundate = TDates::getValidDate($datearg);
            if ($rundate === false) {
                $this->addErrorMessage("Invalid date argument: $datearg");
                return;
            }
            $startDate = TDates::CreateDateTimeObject($rundate);
        }

        $this->addInfoMessage('Committee notifications started: ' . $startDate->format('Y-m-d h:i'));
        $manager = new CalendarEventManager();


        /**
         * Returns array[committeeCode] of {
         *      committeeId : int;
         *      name : string;
         *      code : string;
         *      mailbox : string;
         *      events : CalendarNotification[]
         */
        $notifications = $manager->getCommitteeNotifications($startDate->format('Y-m-d'));
        if (empty($notifications)) {
            $this->addInfoMessage('No committee notifications were scheduled.');
        }

        $taskManager = new CommitteeTaskManager();

        $endDate = clone $startDate;
        TDates::IncrementDate($endDate,6,'days');
        $this->dateRange =  sprintf(
            '%s through %s',
            $startDate->format('F j, Y'),
            $endDate->format('F j, Y')
        );


        /**
         * returns
         * {
         *      committees: array of {
         *          committeeId : int;
         *          name : string;
         *          code : string;
         *          mailbox : string;
         *      }
         *      assignments:
         *          array of {
         *             committeeCode : string;
         *             taskdate    : string ('Y-m-d');
         *             taskId        : int;
         *             taskName    : string;
         *             assignee    : string;
         *      }  ordered by committeeCode,taskDate,taskId
         *  }
         */
        $tasks = $taskManager->getWeeklyTasks($startDate,$endDate);
        foreach ($tasks->committees as $taskCommittee) {
            if (!isset($notifications[$taskCommittee->code])) {
                $notifications[$taskCommittee->code] = $taskCommittee;
            }
        }

        $cmte = '';
        $date = '';
        $taskId = 0;

        foreach ($tasks->assignments as $assignment) {
            if ($cmte != $assignment->committeeCode) {
                if (!empty($schedule)) {
                    $notifications[$cmte]->schedule = $schedule;
                }
                $schedule = [];
                $cmte = $assignment->committeeCode;
            }

            if ($assignment->taskdate != $date) {
                if (!empty($taskDay)) {
                    if (!empty($task)) {
                        $taskDay->tasks[] = $task;
                        $task = null;
                    }
                    $schedule[] = $taskDay;
                }
                $taskDay = new \stdClass();
                $taskDay->date = TDates::reformatDateTime($assignment->taskdate, 'l jS \of F', 'Y-m-d');
                $taskDay->tasks = [];
                $date = $assignment->taskdate;
            }

            if ($assignment->taskId != $taskId) {
                if (!empty($task)) {
                    $taskDay->tasks[] = $task;
                }
                $task = new \stdClass();
                $task->name = $assignment->taskName;
                $task->assignees = [];
                $taskId = $assignment->taskId;
            }

            $task->assignees[] = $assignment->assignee;
        }
        if (count($tasks->assignments)) {
            $taskDay->tasks[] = $task;
            $schedule[] = $taskDay;
            $notifications[$cmte]->schedule = $schedule;
        }

        $messageCount = 0;
        $committeeMembersRepository = new CommitteeMembersRepository();

        $templates = $this->getMessageTemplates();
        foreach ($notifications as $code => $notification) {
            $message = $this->createMessage($notification);
            if ($message) {
                $committeeContent = $this->getCommitteeTemplates($templates,$notification);
                $recipients = $committeeMembersRepository->getNotificationList($code);
                foreach ($recipients as $recipient) {
                    $content = $this->getRecipientContent($committeeContent,$recipient->uid);
                    $message->setMessageBody(
                        $content['html'],
                        $content['txt']
                    );
                    $message->setRecipient($recipient->email, $recipient->fullname);
                    $result = TPostOffice::Send($message);
                    if ($result === false) {
                        $this->addErrorMessage("Cannot sent message; $recipient->email");
                    }

                    $messageCount++;
                }
            }
        }

        $this->addInfoMessage('Committee notifications completed. ' . $messageCount . ' messages queued.');
    }

    /**
     * @param $notification
     *  {
     *      committeeId : int;
     *      name : string;
     *      code : string;
     *      mailbox : string;
     *      events : CalendarNotification[]
     *      schedule: array of {
     *			date: string; (formatted)
     *			tasks: array of {
     *				name: string;
     *				assignees : string[];
     *			}
     *   }
     * @return TEMailMessage
     * @throws \Exception
     */
    private function createMessage($notification)
    {
        $message = new TEMailMessage();
        $senderAddress = TPostOffice::GetSenderAddress($notification->mailbox);
        $message->setFromAddress($senderAddress);
        $bounceAddress = TPostOffice::GetBounceAddress();
        if ($bounceAddress) {
            $message->setReturnAddress($bounceAddress);
        }
        else {
            $message->setReturnAddress($senderAddress);
        }
        $subjectFormat = TLanguage::text(
            'committee-notification-subject',
            'Weekly Reminders for %s committee'
        );

        $message->setSubject(sprintf(
            $subjectFormat,
            $notification->name));
        
        return $message;
    }
    

    /**
     * @param $notification
     * @param string $contentType
     * @return |null
     * @throws \Exception
     */
    private function createMessageContent($notification,$contentType='html') {
        $hasEvents = !empty($notification->events);
        $hasSchedule = !empty($notification->schedule);
        if (!($hasEvents || $hasSchedule)) {
            return null;
        }

        $content = '';
        if ($hasEvents) {
            $eventSection = [
                $contentType == 'html' ?
                    '<div><h2>Events</h2>' :
                    "Events\n\n"
            ];

            /** @var  CalendarNotification $event */
            foreach ($notification->events as $event) {
                $eventSection[] = $event->getMessageText($contentType);
            }
            $eventSection = implode("\n",$eventSection).'</div>';
            $content = $eventSection;

        }
        if ($hasSchedule) {
            $scheduleSection = [
                $contentType == 'html' ? '<div><h2>Task Assignments</h2>' : "\nTask Assignments\n"];
            foreach ($notification->schedule as $item) {
                $scheduleSection[] =
                    sprintf($contentType='html' ? '<h3>%s</h3>' : "\n%s\n",$item->date);
                $scheduleSection[] = $contentType='html' ? '<div>': "\n";
                foreach ($item->tasks as $task) {
                    $scheduleSection[] = sprintf('%s: %s', $task->name, implode(', ',$task->assignees));
                    $scheduleSection[] = $contentType='html' ? '<br>': "\n";
                }
                $scheduleSection[] =  $contentType='html' ? '</div>': "\n";
            }
            $scheduleSection[] = $contentType='html' ? '</div>': "\n";
            $scheduleSection = implode("\n",$scheduleSection);
            $content .= $scheduleSection;
        }
        return $content;

    }

    private function getMessageTemplates()
    {
        $templateManager = new MailTemplateManager();
        $templates = [];
        $templates['html'] = $this->getMessageTemplate($templateManager, 'html');
        $templates['txt'] = $this->getMessageTemplate($templateManager, 'txt');
        return $templates;
    }

    private function getMessageTemplate(MailTemplateManager $templateManager,$contentType) {
        $siteUrl = TWebSite::GetSiteUrl();
        $optoutPage = TConfiguration::getValue('subscriptionsUrl','pages','/subscriptions');

        $footerTemplate = $templateManager->getTemplateContent('footer.'.$contentType);
        $messageFooter = TTemplateManager::ReplaceContentTokens($footerTemplate, ['siteUrl' => $siteUrl]);
        $mainTemplate = $templateManager->getTemplateContent('CommitteeNotifications.'.$contentType);
        return TTemplateManager::ReplaceContentTokens($mainTemplate, [
            'siteUrl' => $siteUrl,
            'optout-page' => $optoutPage,
            'date-range' => $this->dateRange,
            'footer' => $messageFooter
        ]);
    }

    /**
     * @param $notification
     * @throws \Exception
     */
    private function getCommitteeTemplates($templates,$notification) {
        $response = [];
        foreach($templates as $contentType => $template) {
            $mainContent = $this->createMessageContent($notification,$contentType);
            $response[$contentType] = TTemplateManager::ReplaceContentTokens($templates[$contentType],
                [
                    'committee-name' => $notification->name,
                    'content' => $mainContent
                ]);
        }
        return $response;
    }
    
    private function getRecipientContent($templates,$uid) {
        $response = [];
        foreach($templates as $contentType => $template) {
            $response[$contentType] = TTemplateManager::ReplaceContentTokens($templates[$contentType],
                [
                    'uid' => $uid
                ]);
        }
        return $response;
    }
}