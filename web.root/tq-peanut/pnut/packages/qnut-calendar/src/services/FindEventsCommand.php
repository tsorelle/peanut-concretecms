<?php

namespace Peanut\QnutCalendar\services;

use Peanut\QnutCalendar\db\model\repository\CalendarEventsRepository;
use Tops\services\TServiceCommand;

class FindEventsCommand extends TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('No search value');
            return;
        }
        $repository = new CalendarEventsRepository();
        $response = $repository->findEventList($request);
        $this->setReturnValue($response);
    }
}