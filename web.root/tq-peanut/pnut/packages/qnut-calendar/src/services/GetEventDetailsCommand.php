<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/11/2018
 * Time: 9:38 AM
 */

namespace Peanut\QnutCalendar\services;


use Peanut\QnutCalendar\db\model\CalendarEventManager;
use Peanut\QnutCalendar\db\model\repository\CalendarEventsRepository;
use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

class GetEventDetailsCommand extends TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('error-no-id');
            return;
        }
        $repository = new CalendarEventsRepository();
        $response = $repository->getEventDetails($request);
        $user = $this->getUser();
        if (!$user->isAuthorized(CalendarEventManager::ManageCalendarPermissionName)) {
            $response->notes = '';
        }
/*
        if ($user->isAuthenticated()) {
            $personId = $user->getId();
            $response->notification = $repository->getEventNotificationDays($request, $this->getUser()->getId());
        }
        else {
            $response->notification = -1;
        }
*/
        $this->setReturnValue($response);
    }
}