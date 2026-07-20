<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/15/2019
 * Time: 10:38 AM
 */

namespace Peanut\QnutCalendar\services;


use Peanut\QnutCalendar\db\model\CalendarEventManager;
use Tops\services\TServiceCommand;

class UpdateEventNotificationCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->requireAuthentication();
    }

    protected function run()
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->addErrorMessage('service-no-auth');
            return;
        }
        $request = $this->getRequest();
        if ($request == null) {
            $this->addErrorMessage('service-no-request');
            return;
        }
        if (empty($request->personId)) {
            $this->addErrorMessage('calendar-error-bad-notification-request');
            return;
        }
        if (empty($request->eventId)) {
            $this->addErrorMessage('calendar-error-bad-notification-request');
            return;
        }
        if (!isset($request->notificationDays)) {
            $this->addErrorMessage('calendar-error-bad-notification-request');
            return;
        }

        $manager = new CalendarEventManager();
        $user = $this->getUser();
        if ($request->notificationDays < 0) {
            $manager->clearEventNotification($request->eventId, $request->personId);
            $this->addInfoMessage('notification-message-cancelled');
        }
        else {
            $manager->addEventNotification($request->eventId,$request->personId,$request->notificationDays,$user->getUserName());
            $this->addInfoMessage('notification-message-set');
        }
    }
}