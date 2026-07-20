<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/19/2018
 * Time: 10:50 AM
 */

namespace Peanut\QnutCalendar\services;


use Peanut\QnutCalendar\db\model\CalendarEventManager;
use Peanut\QnutDirectory\db\model\repository\PersonsRepository;
use Peanut\sys\TVmContext;
use Tops\services\TServiceCommand;
use Tops\sys\IUser;
use Tops\sys\TConfiguration;
use Tops\sys\TLanguage;

/**
 * Class GetEventsCommand
 * @package Peanut\QnutCalendar\services
 *
 * Service contract
 * Request may optionally contain:
 * 		year 			year of calendar page - default current year
 * 		month			month of calendar page - default current month
 * 		filter			'resource' or 'committee'
 *      format          'list' or 'calendar'
 * 		code			code value for resource or committee filter
 * 		pageDirection
 * 			'left' - includes dates on before current month
 * 			'right' - includes dates on after current month
 * 			otherwise include full calendar page
 * 		public			Return only public events.  Defaults to true if user authenticated
 *      initialize
 *
 *  Response:
 *
 *     interface IGetCalendarResponse {
 *         year: any;
 *         month: any;
 *         filteredBy: string;
 *         events: ICalendarEvent[];  (FullCalendarEvent[])
 *          Optional if request->initialize
 *              userPermission: string;
 *              canSubmitRequest: any;
 *              types: Peanut.ILookupItem[];
 *              optional if userPermission -- edit
 *                  committees: Peanut.ILookupItem[];
 *                  resources: Peanut.ILookupItem[];
 *      }
 */
class GetEventsCommand extends TServiceCommand
{
    private function getUserPermissionLevel(IUser $user)
    {
        if (!$user->isAuthenticated()) {
            return 'view';
        }
        return $user->isAuthorized(CalendarEventManager::ManageCalendarPermissionName) ? 'edit' : 'filter';
    }

    protected function run()
    {
        $request = $this->getRequest();
        $format = 'calendar';
        // $format = 'list';
        if ($request == null) {
            $request = new \stdClass();
        }
        else if (isset($request->context)) {
            $context = TVmContext::GetContext($request->context);
            if (!empty($context->value)) {
                $format = 'list';
                @list($request->filter,$request->code) = explode('=',$context->value);
                if (empty($request->code)) {
                    $request->code = $request->filter;
                    $request->filter = 'committee';
                }
            }
        }

        $user = $this->getUser();
        if (!isset($request->public)) {
            $request->public = !$user->isAuthenticated();
        }

        $manager = new CalendarEventManager();
        $response = $manager->getCalendarEvents($request);
        $response->filteredBy = empty($request->filter) ? null : $request->filter.':'.$request->code;
        // $response->events = $this->getTestData();
        // $response->eventCount = sizeof($response->events);
        $response->year = empty($request->year) ? date('Y') : $request->year;
        $response->month = empty($request->month) ? date('n') : $request->month;
        if (!empty($request->initialize)) {
            $response->userPermission = $this->getUserPermissionLevel($user);
            $response->canSubmitRequest = $response->userPermission == 'filter' && TConfiguration::getValue('calendarRequests','mail') !== 'not supported';
            $response->types = $manager->getEventTypesList();
            $response->committees = $manager->getCommitteeList();
            $response->resources = $manager->getResourcesList();
            $response->groups = $manager->getUserGroupsList();
            $response->userPersonId = 0;
            if ($user->isAuthenticated()) {
                $person = (new PersonsRepository())->getByAccountId($user->getId());
                $response->userPersonId = $person ? $person->getId() : 0;
            }

            $response->translations = TLanguage::getTranslations([
                'calander-hour',
                'calander-hour-plural',
                'calendar-confirm-delete-header',
                'calendar-confirm-delete-text',
                'calendar-confirm-removerepeat-header',
                'calendar-confirm-removerepeat-text',
                'calendar-date-format',
                'calendar-days-of-week',
                'calendar-days-of-week-plural',
                'calendar-delete-modal-title',
                'calendar-error-no-title',
                'calendar-event-entity',
                'calendar-filter-title-committees',
                'calendar-filter-title-events',
                'calendar-filter-title-groups',
                'calendar-filter-title-resources',
                'calendar-get-details',
                'calendar-label-allday',
                'calendar-label-event-type',
                'calendar-label-new-event',
                'calendar-label-repeating-event',
                'calendar-label-reschedule',
                'calendar-label-reschedule-edit',
                'calendar-label-requestor',
                'calendar-label-return',
                'calendar-label-send-requst',
                'calendar-label-submit-request',
                'calendar-list-next',
                'calendar-list-prev',
                'calendar-months-of-year',
                'calendar-notify-remind',
                'calendar-notify-when',
                'calendar-pattern-header',
                'calendar-paging',
                'calendar-phrase-end-after',
                'calendar-phrase-end-by',
                'calendar-phrase-no-end',
                'calendar-range-header',
                'calendar-repeat-message',
                'calendar-request-committee',
                'calendar-reschedule-repeat-instructions1',
                'calendar-reschedule-repeat-instructions2',
                'calendar-reschedule-repeat-question',
                'calendar-set-custorm',
                'calendar-time-error',
                'calendar-time-format',
                'calendar-update-modal-all',
                'calendar-update-modal-instance',
                'calendar-update-modal-question',
                'calendar-update-modal-title',
                'calendar-weekly-ordinals',
                'calendar-word-after',
                'calendar-word-daily',
                'calendar-word-day',
                'calendar-word-day-plural',
                'calendar-word-each',
                'calendar-word-every',
                'calendar-word-last',
                'calendar-word-month',
                'calendar-word-month-plural',
                'calendar-word-monthly',
                'calendar-word-occurances',
                'calendar-word-repeat',
                'calendar-word-repeating',
                'calendar-word-start',
                'calendar-word-very',
                'calendar-word-week',
                'calendar-word-week-plural',
                'calendar-word-weekday',
                'calendar-word-weekday-plural',
                'calendar-word-weekly',
                'calendar-word-year',
                'calendar-word-year-plural',
                'calendar-word-yearly',
                'calender-label-reschedule-end',
                'calender-label-set-notification',
                'calender-reschedule-question',
                'calender-reschedule-title',
                'calender-time-order-error',
                'committee-entity',
                'committee-entity-plural',
                'conjunction-from',
                'conjunction-in',
                'conjunction-of',
                'conjunction-on',
                'conjunction-since',
                'conjunction-starting',
                'conjunction-the',
                'conjunction-through',
                'conjunction-to',
                'conjunction-until',
                'event-notification-title',
                'group-entity-plural',
                'label-add',
                'label-cancel',
                'label-close',
                'label-comments',
                'label-continue',
                'label-custom',
                'label-date',
                'label-description',
                'label-edit',
                'label-email',
                'label-filter',
                'label-location',
                'label-location',
                'label-new',
                'label-notes',
                'label-remove',
                'label-room',
                'label-save',
                'label-show-all',
                'label-time',
                'label-title',
                'label-title',
                'label-to',
                'label-update',
                'nav-more',
                'resource-entity-plural',
            ]);

            $response->format = $format;
            $response->translations['calendar-word-the'] = trim($response->translations['conjunction-the']);
            $response->translations['calendar-word-on'] = trim($response->translations['conjunction-on']);
            $response->vocabulary = new \stdClass();
            $response->vocabulary->daysOfWeek =       explode(',',TLanguage::text('calendar-days-of-week'));
            $response->vocabulary->daysOfWeekPlural = explode(',',TLanguage::text('calendar-days-of-week-plural'));
            $response->vocabulary->monthNames     =   explode(',',TLanguage::text('calendar-months-of-year'));
            $response->vocabulary->ordinals =         explode(',',TLanguage::text('calendar-weekly-ordinals'));
            array_push($response->vocabulary->ordinals,TLanguage::text('calendar-word-last'));
            $response->vocabulary->ordinalSuffix =    explode(',',TLanguage::text('calendar-ordinals-suffix'));
        }
        $this->setReturnValue($response);
    }

    private function getTestData()
    {
        $ym = '2018-02';
        $result = [];
        $event = new \stdClass();
        $event->title = 'All Day Event';
        $event->start = $ym . '-01';
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Long Event';
        $event->start = $ym . '-07';
        $event->end = $ym . '-10';
        $event->id = 999;
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Repeating Event';
        $event->start = $ym . '-09T16:00:00';
        $event->id = 999;
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Repeating Event';
        $event->start = $ym . '-16T16:00:00';
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Conference';
        $event->start = $ym . '-11';
        $event->end = $ym . '-13';
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Meeting';
        $event->start = $ym . '-12T10:30:00';
        $event->end = $ym . '-12T12:30:00';
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Lunch';
        $event->start = $ym . '-12T12:00:00';
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Meeting';
        $event->start = $ym . '-12T14:30:00';
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Happy Hour';
        $event->start = $ym . '-12T17:30:00';
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Dinner';
        $event->start = $ym . '-12T20:00:00';
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Birthday Party';
        $event->start = $ym . '-13T07:00:00';
        $result[] = $event;
        $event = new \stdClass();
        $event->title = 'Click for Google';
        $event->url = 'http = //google.com/';
        $event->start = $ym . '-28';
        $result[] = $event;
        return $result;
    }
}
