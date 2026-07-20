<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/16/2019
 * Time: 1:19 PM
 */

namespace Peanut\QnutCalendar\services;


use Peanut\QnutCalendar\db\model\repository\CalendarEventsRepository;
use Tops\services\TServiceCommand;

/**
 * Class GetEventNotificationCommand
 * @package Peanut\QnutCalendar\services
 *
 * Service contract
 *      request: {
 *          personId: int,
 *          eventId: int
 *      }
 *      response:
 *          interface ICalendarNotification {
 *          personId: any,
 *          eventId: any,
 *          notificationDays: any;
 *      }
 */
class GetEventNotificationCommand extends TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request->personId)) {
            $this->addErrorMessage('No person id'); // todo: translate
            return;
        }
        if (empty($request->eventId)) {
            $this->addErrorMessage('No event id'); // todo: translate
            return;
        }
        $repository = new CalendarEventsRepository();
        $response = $repository->getEventNotificationDays($request->eventId, $request->personId);
        $this->setReturnValue($response);
    }
}