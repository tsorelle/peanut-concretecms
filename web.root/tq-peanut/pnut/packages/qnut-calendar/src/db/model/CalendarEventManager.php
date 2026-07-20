<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/2/2018
 * Time: 11:40 AM
 */

namespace Peanut\QnutCalendar\db\model;

use Peanut\QnutCalendar\db\model\entity\CalendarEvent;
use Peanut\QnutCalendar\db\model\entity\CalendarSearchEvent;
use Peanut\QnutCalendar\db\model\entity\FullCalendarEvent;
use Peanut\QnutCalendar\db\model\entity\NotificationSubscription;
use Peanut\QnutCalendar\db\model\repository\CalendarCommitteeAssociation;
use Peanut\QnutCalendar\db\model\repository\CalendarEventsRepository;
use Peanut\QnutCalendar\db\model\repository\CalendarResourceAssociation;
use Peanut\QnutCalendar\db\model\repository\CalendarUsergroupsAssociation;
use Peanut\QnutCalendar\db\model\repository\NotificationSubscriptionsRepository;
use Peanut\QnutCalendar\db\model\repository\NotificationTypesRepository;
use Peanut\QnutCommittees\CommitteeManager;
use Peanut\QnutCommittees\CommitteeTaskManager;
use Peanut\PeanutMailings\db\model\entity\EmailList;
use Peanut\PeanutMailings\db\model\repository\EmailListsRepository;
use Peanut\QnutUsergroups\UsergroupManager;
use Tops\db\model\repository\LookupTableRepository;
use Tops\db\NamedEntity;
use Tops\sys\IUser;
use Tops\sys\TCalendarPage;
use Tops\sys\TDateRepeater;
use Tops\sys\TDates;

class CalendarEventManager
{
    const ManageCalendarPermissionName = 'Manage calendar';
    /**
     * @var CalendarEventsRepository
     */
    private $eventsRepository;
    private function getEventsRepository() {
        if (!isset($this->eventsRepository)) {
            $this->eventsRepository = new CalendarEventsRepository();
        }
        return $this->eventsRepository;
    }

    /**
     * @var EmailListsRepository $emailListsRepository
     */
    private $emailListsRepository;

    /**
     * @return EmailListsRepository
     */
    private function getEmailListsRepository() {
        if (!isset($this->emailListsRepository)) {
            $this->emailListsRepository = new EmailListsRepository();
        }
        return $this->emailListsRepository;
    }

    /**
     * @var CalendarCommitteeAssociation
     */
    private $committeesAssociation;
    private function getCommitteesAssociation() {
        if (!isset($this->committeesAssociation)) {
            $this->committeesAssociation = new CalendarCommitteeAssociation();
        }
        return $this->committeesAssociation;
    }

    /**
     * @var NotificationSubscriptionsRepository
     */
    private $notificationsRepository;
    private function getNotificationsRepository() {
        if (!isset($this->notificationsRepository)) {
            $this->notificationsRepository = new NotificationSubscriptionsRepository();
        }
        return $this->notificationsRepository;
    }

    /**
     * @var UsergroupManager
     */
    private $userGroupManager;
    private function getUsergroupManager() {
        if (!isset($this->userGroupManager)) {
            $this->userGroupManager = new UsergroupManager();
        }
        return $this->userGroupManager;
    }

    /**
     * @var CommitteeManager
     */
    private $committeeManager;
    private function getCommitteeManager() {
        if (!isset($this->committeeManager)) {
            $this->committeeManager = new CommitteeManager();
        }
        return $this->committeeManager;
    }



    /**
     * @var CalendarResourceAssociation;
     */
    private $resourcesAssociation;
    private function getResourcesAssociation() {
        if (!isset($this->resourcesAssociation)) {
            $this->resourcesAssociation = new CalendarResourceAssociation();
        }
        return $this->resourcesAssociation;
    }

    /**
     * @var
     */
    private $groupsAssociation;
    private function getGroupsAssociation() {
        if (!isset($this->groupsAssociation)) {
            $this->groupsAssociation = new CalendarUsergroupsAssociation();
        }
        return $this->groupsAssociation;
    }

    private static $instance;
    public static function GetInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new CalendarEventManager();
        }
    }

    /**
     * @param $id
     * @return bool|CalendarEvent
     */
    public function getEvent($id) {
        return $this->getEventsRepository()->get($id);
    }

    public function addEvent(CalendarEvent $event, $username = 'system') {
        return $this->getEventsRepository()->insert($event,$username);
    }

    public function cloneEventAssociations($originalId,$eventId) {

    }

    public function updateEventAssociations($eventId,array $committees = null, array $resources = null, array $groups= null) {
        if ($committees !== null) {
            $this->getCommitteesAssociation()->updateRightValues($eventId,$committees);
        }
        if ($resources !== null) {
            $this->getResourcesAssociation()->updateRightValues($eventId,$resources);
        }
        if ($groups !== null) {
            $this->getGroupsAssociation()->updateRightValues($eventId,$groups);
        }
    }

    public function updateEvent(CalendarEvent $event, $username = 'system') {
        $this->getEventsRepository()->update($event,$username);
    }

    /**
     * @param $startDate
     * @return array
     * @throws \Exception
     */
    public function getCalendarNotifications($startDate) {
        $results = [];
        $events = $this->getCalendarNotificationEvents($startDate);
        $subscriptions = $this->getNotificationsRepository();
        $start = new \DateTime(substr($startDate,0,10));
        foreach ($events as $event) {
             $id = $event->id;
            $eventDate = new \DateTime(substr($event->start, 0,10));
            $leadDays = $start->diff($eventDate)->d;
            $recipients =  $subscriptions->getEventNotificationRecipients($event->id,$leadDays);
            if ($recipients) {
                $result = new \stdClass();
                $result->event = $event;
                $result->recipients = $recipients;
                $results[] = $result;
            }
        }
        return $results;
    }

    /**
     * @param $startDate
     * @return \stdClass
     * @throws \Exception
     */
    public function getUserGroupNotifications($startDate) {
        $manager = $this->getUsergroupManager();
        $response = new \stdClass();
        $response->groups = [];
        $response->events = [];
        $events = $this->getCalendarNotificationEvents($startDate);
        $start = new \DateTime(substr($startDate,0,10));
        foreach ($events as $event) {
            $id = $event->id;
            $eventDate = new \DateTime(substr($event->start, 0,10));
            $leadDays = $start->diff($eventDate)->d;
            if ($leadDays > 2) {
                continue;
            }
            $eventGroups = $manager->getEventGroups($id);
            foreach ($eventGroups as $eventGroup) {
                if (!isset($response->groups[$eventGroup->code])) {
                    $eventGroup->recipients = $manager->getGroupSubscribers($eventGroup->groupId);
                    $response->groups[$eventGroup->code] = $eventGroup;
                }
                $eventItem = new \stdClass();
                $eventItem->event = $event;
                $eventItem->groupCode = $eventGroup->code;
                $response->events[] = $eventItem;
            }
        }
        return $response;
    }



    /**
     * @param $startDate
     * @return FullCalendarEvent[]
     */


    public function getCalendarNotificationEvents($startDate) {
        $start = new \DateTime($startDate);
        $end = (clone $start)->modify('+ 8 days');

        $startMonth = $start->format('m');
        $startYear =  $start->format('Y');
        $calendars = [];
        $calendar = TCalendarPage::Create($startYear,$startMonth);
        $calendars[] = $calendar;
        $calendarEndMonth = $calendar->end->format('m');
        if ($end > $calendar->end) {
            $endMonth = $end->format('m');
            $endYear =  $end->format('Y');
            $calendars[] = TCalendarPage::Create($endYear,$endMonth,'right');
        }
        $endDate = $end->format('Y-m-d');
        $eventsRepository = $this->getEventsRepository();
        $eventResults = $eventsRepository->getCalendarNotificationEvents($startDate,$endDate);

        return $this->mergeRepeatingEvents($startDate, $endDate, $eventResults, $calendars);
    }

    /**
     * @param $request
     * @return \stdClass
     */
    public function getCalendarEvents($request)
    {
        $year = empty($request->year) ? date('Y') : $request->year;
        $month = empty($request->month) ? date('n') : $request->month;
        $filter= empty($request->filter) ? '' : $request->filter;
        $code=empty($request->code) ? '' : $request->code; 
        $pageDirection=empty($request->pageDirection) ? '' : $request->pageDirection;
        $publicOnly = (!empty($request->public));

        $calendarPage = TCalendarPage::Create($year, $month, $pageDirection);
        $startDate = (clone $calendarPage->start)->format('Y-m-d');
        $endDate = (clone $calendarPage->end)->format('Y-m-d');

        $eventsRepository = $this->getEventsRepository();
        $eventResults = $eventsRepository->getFilteredEvents($startDate,$endDate,$filter,$code,$publicOnly);

        /**
         * @var $repeats FullCalendarEvent[]
         */
        $results = $this->mergeRepeatingEvents($startDate,$endDate,$eventResults,[$calendarPage]);


        $response = new \stdClass();
        $response->events = [];
        // reaults might be an object, convert to array for json
        foreach ($results as $result) {
            $response->events[] = $result;
        }
        $response->startDate = $calendarPage->start->format('Y-m-d');
        $response->endDate = $calendarPage->end->format('Y-m-d');
        return $response;
    }


    public function getEventTypesList()
    {
        $repository = new LookupTableRepository('qnut_calendar_event_types');
        $repository->setLookupInfoColumns(['backgroundColor as `color`']);
        return $repository->getLookupList();
    }

    public function getResourcesList()
    {
        $repository = new LookupTableRepository('qnut_resources');
        return $repository->getLookupList();
    }

    public function getCommitteeList()
    {
        $repository = new LookupTableRepository('qnut_committees');
        return $repository->getLookupList(false); // no translate.
    }

    public function getUserGroupsList() {
        $repository = new LookupTableRepository('qnut_usergroups');
        return $repository->getLookupList(false); // no translate.
    }

    private $subscriptionTypeId;
    private function getSubscriptionTypeId()
    {
        if (!isset($this->subscriptionTypeId)) {
            /**
             * @var $type NamedEntity
             */
            $type = (new NotificationTypesRepository())->getEntityByCode('calendar');
            $this->subscriptionTypeId = empty($type) ? 1 : $type->getId();
        }
        return $this->subscriptionTypeId;
    }

    public function addEventNotification($eventId,$personId,$leadDays,$username = 'system') {
        /**
         * @var $subscription NotificationSubscription
         */
        $subscription = $this->getNotificationsRepository()->getSubscription('calendar',$eventId,$personId);
        if (empty($subscription)) {
            $subscription = new NotificationSubscription;
            $subscription->personId = $personId;
            $subscription->notificationTypeId = $this->getSubscriptionTypeId();
            $subscription->itemId = $eventId;
            $subscription->leadDays = $leadDays;
            $this->getNotificationsRepository()->insert($subscription,$username);
        }
        else if ($subscription->leadDays != $leadDays) {
            $subscription->leadDays = $leadDays;
            $this->getNotificationsRepository()->update($subscription, $username);
        }
    }

    public function clearEventNotification($eventId,$personId) {
        /**
         * @var $subscription NotificationSubscription
         */
        $subscription = $this->getNotificationsRepository()->getSubscription('calendar',$eventId,$personId);
        if (!empty($subscription)) {
           $this->getNotificationsRepository()->delete($subscription->id);
        }
    }

    public function deleteEvent($eventId) {
        $this->getNotificationsRepository()->deleteSubscriptions('calendar',$eventId);
        $this->getCommitteesAssociation()->removeAllLeft($eventId);
        $this->getResourcesAssociation()->removeAllLeft($eventId);
        $this->getGroupsAssociation()->removeAllLeft($eventId);
        $this->getEventsRepository()->delete($eventId);
    }

    public function deleteRepeatingEvent($eventId)
    {
        $this->getEventsRepository()->deleteRepeatInstances($eventId);
        $this->deleteEvent($eventId);
    }

    public function deleteRepeatingInstances($eventId)
    {
        $this->getEventsRepository()->deleteRepeatInstances($eventId);
    }

    public function truncateRepeatInstances($eventId,$date) {
        if (!empty($date)) {
            $this->getEventsRepository()->truncateRepeatInstances($eventId,$date);
        }

    }

    /**
     * @param $event CalendarEvent
     * @param $instanceDate
     * @param string $username
     *
     * Insert a "cloned" event with active=0.  This supresses the generation of a "virtual" repeating event for that date.
     */
    public function deleteRepeatInstance($eventId,$instanceDate,$username='system')
    {
        $event = $this->getEventsRepository()->get($eventId);
        if ($event) {
            $event->title = $event->title.' - removed';
            $event->recurId = $eventId;
            $event->id = 0;
            $event->recurPattern = null;
            $event->recurEnd = null;
            $event->active = 0;
            $event->start = $instanceDate;
            $event->recurInstance = $instanceDate;
            $this->getEventsRepository()->insert($event, $username);
        }
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $eventResults
     * @param $calendars
     * @return array
     */
    private function mergeRepeatingEvents($startDate, $endDate, $eventResults, $calendars)
    {
        $eventsRepository = $this->getEventsRepository();

        /**
         * @var $repeats CalendarSearchEvent[]
         */
        $results = $eventResults->events;

        /**
         * @var $repeats CalendarSearchEvent[]
         */
        $repeats = $eventResults->repeats;

        // get repeating dates
        $repeater = new TDateRepeater();

        foreach ($repeats as $event) {
            $occurance = 0;
            foreach ($calendars as $calendarPage) {
                if (!empty($event->repeatPattern)) {
                    $dates = $repeater->getRepeatingDates($calendarPage, $event->repeatPattern);
                    if (is_array($dates)) {
                        $replacements = $eventsRepository->getRepeatReplacementDates($event->id);
                        foreach ($dates as $date) {
                            if ($date >= $startDate && $date < $endDate) {
                                // Check if a replacement event found for this instance
                                if (!in_array($date, $replacements)) {
                                    $repeat = $this->cloneEvent($event, ++$occurance, $date);
                                    $results[] = $repeat;
                                }
                            }
                        }
                    }
                }
            }
        }


        // sort by start time
        uasort($results, function ($eventA, $eventB) {
            $a = new \DateTime($eventA->start);
            $b = new \DateTime($eventB->start);
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        return $results;
    }



     /**
     * @param CalendarSearchEvent $event
     * @param $occurance
     * @param $date
     * @return CalendarSearchEvent
     * @throws \Exception
     */
    private function cloneEvent(CalendarSearchEvent $event, $occurance, $date)
    {
        // clone the event and update start and end dates
        $repeat = clone $event;
        $repeat->occurance = $occurance;
        $repeat->recurInstance = $date;
        $repeat->start = $date;
        @list($datePart, $timePart) = explode('T', $event->start);
        if ($timePart !== null) {
            $repeat->start .= 'T' . $timePart;
        }
        if ($event->end !== null) {
            $utc = new \DateTimeZone('UTC');
            // get interval
            $start = new \DateTime($event->start, $utc);
            $end = new \DateTime($event->end, $utc);
            $interval = $end->diff($start,true);

            // $end = $repeat->start + interval
            $end = new \DateTime($repeat->start, $utc);
            $end->add($interval);

            $repeat->end = str_replace('UTC', 'T', $end->format(TDates::IsoDateTimeFormat));
        }
        return $repeat;
    }

    public function getNotificationListInfo()
    {
        $repository = $this->getEmailListsRepository();
        $result = $repository->getEntityByCode('notify');
        if (empty($result)) {
            $result = new EmailList();
            $result->code = 'notify';
            $result->mailBox = 'calendar';
            $result->id = 1;
        }
        return $result;
    }

    /**
     * @param $rundate
     * @return \stdClass[]
     * @throws \Exception
     */
    public function getCommitteeNotifications($startDate)
    {
        $manager = $this->getCommitteeManager();
        $response = [];
        $events = $this->getCalendarNotificationEvents($startDate);
        $start = new \DateTime(substr($startDate,0,10));
        foreach ($events as $event) {
            $id = $event->id;
            $eventDate = new \DateTime(substr($event->start, 0,10));
            $leadDays = $start->diff($eventDate)->d;
            if ($leadDays > 6) {
                continue;
            }
            $committees = $manager->getEventCommittees($id);
            foreach ($committees as $eventCommittee) {
                if (!isset($response[$eventCommittee->code])) {
                    $eventCommittee->events = [];
                    $response[$eventCommittee->code] = $eventCommittee; // {committeeId,name,code,mailbox}
                }
                $response[$eventCommittee->code]->events[] = $event;
            }

            // todo: get group notifications

        }
        return $response;
    }

}