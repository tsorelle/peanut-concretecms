<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/15/2018
 * Time: 6:41 AM
 */

namespace Peanut\QnutCalendar\services;


use Peanut\QnutCalendar\db\model\CalendarEventManager;
use Peanut\QnutCalendar\db\model\entity\CalendarEvent;
use Tops\services\TServiceCommand;
use Tops\sys\TDateRepeater;
use Tops\sys\TDates;
use Tops\sys\TLanguage;
use Tops\sys\TPermissionsManager;

/**
 * Class UpdateEventCommand
 * @package Peanut\QnutCalendar\services
 *
 * Service Contract:
 *	Request:
 *     interface ICalendarUpdateRequest {
 *          event: ICalendarDto;
 *          year: any;
 *          month: any;
 *          repeatInstance;
 *          filter: string;
 *          code: string;
 *          repeatUpdateMode: string;
 *          notificationDays: any;
 *          resources: any[];
 *          committees: any[];
 *    }
 *
 *    interface ICalendarDto {
 *        id : any;
 *        title : string;
 *        start : string;
 *        end : string;
 *        allDay : number;
 *        location: string;
 *        url : string;
 *        eventTypeId : any;
 *        recurPattern : string;
 *        recurEnd : any;
 *        recurId: any;
 *        notes: string;
 *        description: string;
 *    }
 *
 *	Response:
 *     interface IGetCalendarResponse {
 *         year: any;
 *         month: any;
 *         events: ICalendarEvent[];  (FullCalendarEvent[])
 *          (optional properties omitted)
 *      }
 */
class UpdateEventCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->addAuthorization(CalendarEventManager::ManageCalendarPermissionName);
    }

    protected function run()
    {

        $request = $this->getRequest();
        if ($request == null) {
            $this->addErrorMessage('service-no-request');
            return;
        }

        if (empty($request->event)) {
            $this->addErrorMessage('calendar-error-no-event');
            return;
        }
        if (empty($request->event->start)) {
            $this->addErrorMessage('calendar-error-no-start');
            return;
        }

        $startTime = TDates::stringToTimestamp($request->event->start,TDates::IsoDateTimeFormat);
        if ($startTime === false) {
            $this->addErrorMessage('calendar-error-invalid-start');
            return;
        }

        $manager = new CalendarEventManager();
        $id = $request->event->id;
        $isNew = ($id === 0);
        $original = null;
        if ($isNew) {
            $event = new CalendarEvent();
        }
        else {
            $event = $manager->getEvent($request->event->id);
            $original = clone $event;

        }

        $occurences = is_numeric($request->event->recurEnd) ? $request->event->recurEnd : null;
        $event->assignFromObject($request->event);
        if (empty($event->recurPattern)) {
            $event->recurPattern = null;
        }

        if($original != null && ($original->recurId != null && $event->recurPattern)) {
            // if replacement for repeat instance, start new series
            $event->recurId = null;
            $event->recurInstance = null;
            $request->repeatUpdateMode = 'none';
        }

        if ($isNew || $request->repeatUpdateMode !== 'instance') {
            $this->calculateRecurEnd($event,$occurences);
        }

        if (!$isNew) {
            switch ($request->repeatUpdateMode) {
                case 'all':
                    if($original->recurPattern == $event->recurPattern) {
                        $manager->truncateRepeatInstances($event->id,$event->recurEnd);
                    }
                    else {
                        $manager->deleteRepeatingInstances($event->id);
                    }
                    break;
                case 'instance' :
                    $event->recurId = $event->id;
                    $event->recurInstance = $request->repeatInstance;
                    $event->recurEnd = null;
                    $event->recurPattern = null;
                    $event->id = 0;
                    $isNew = true;
                    break;
                default: // 'none'
                    break;
            }
        }

        $user = $this->getUser();

        if ($isNew) {
            $id = $manager->addEvent($event,$user->getUserName());
        }
        else {
            $manager->updateEvent($event,$user->getUserName());
        }

        $manager->updateEventAssociations($id,
            isset($request->committees) ? $request->committees : null,
            isset($request->resources) ? $request->resources : null,
            isset($request->groups) ? $request->groups : null
        );

        /*
        if (isset($request->notificationDays)) {
            if ($request->notificationDays < 0) {
                if (!$isNew) {
                    $manager->clearEventNotification($id, $user->getId());
                }
            }
            else {
                $manager->addEventNotification($id,$user->getId(),$request->notificationDays,$user->getUserName());
            }
        }
        */
        $eventEntity = TLanguage::text('calendar-event-entity');

        $this->addInfoMessage(
            $isNew ? 'service-added-entity' : 'service-updated-entity',
            [$eventEntity,$event->title]);


        // return events list
        $getEventsRequest = new \stdClass();
        $getEventsRequest->year = $request->year;
        $getEventsRequest->month = $request->month;
        $getEventsRequest->filter = $request->filter;
        $getEventsRequest->code = $request->code;
        if (!isset($request->public)) {
            $getEventsRequest->public = !$user->isAuthenticated();
        }

        $manager = new CalendarEventManager();
        $response = $manager->getCalendarEvents($getEventsRequest);
        $this->setReturnValue($response);
    }

    private function calculateRecurEnd(CalendarEvent $event,$occurences)
    {
        if ((!empty($event->recurPattern)) && !empty($occurences)) {
            $startDate = substr($event->start,0,10);
            $repeater = new TDateRepeater();
            $range = $repeater->getRepeatDateRange($event->recurPattern,$startDate,$occurences);
            $event->recurEnd = sizeof($range > 1) ? $range[1] : null;
        }
    }
}