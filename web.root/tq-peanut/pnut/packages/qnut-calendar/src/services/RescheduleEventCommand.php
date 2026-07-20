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
 *     interface IRescheduleEventRequest {
 *        id : any;
 *        start : string;
 *        end : string;
 *
 *          filter: string;
 *          code: string;
 *          year: any;
 *          month: any;
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

class RescheduleEventCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->addAuthorization(CalendarEventManager::ManageCalendarPermissionName);
    }

    protected function run()
    {
        // todo: test for repeat event replacement.
        $request = $this->getRequest();
        if ($request == null) {
            $this->addErrorMessage('service-no-request');
            return;
        }

        if (empty($request->id)) {
            $this->addErrorMessage('calendar-error-no-event');
            return;
        }
        if (empty($request->start)) {
            $this->addErrorMessage('calendar-error-no-start');
            return;
        }

        $endTime = empty($request->end) || $request->start == $request->end ? null : $request->end;

        $manager = new CalendarEventManager();
        $id = $request->id;
        $event = $manager->getEvent($request->id);
        $event->start = $request->start;
        $event->end = $endTime;
        $isNew = !empty($event->recurPattern);
        if ($isNew) {
            $recurId = empty($event->recurId) ? $id : $event->recurId;
            $event->recurInstance = substr($request->start,0,10);
            $event->recurId = $recurId;
            $event->recurEnd = null;
            $event->recurPattern = null;
            $event->id = 0;
        }

        $user = $this->getUser();

        if ($isNew) {
            $newId = $manager->addEvent($event,$user->getUserName());
        }
        else {
            $manager->updateEvent($event,$user->getUserName());
        }

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
}