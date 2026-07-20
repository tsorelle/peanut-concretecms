<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/2/2018
 * Time: 11:19 AM
 */

namespace Peanut\QnutCalendar\db\model\repository;


use Tops\db\TAssociationRepository;

class CalendarResourceAssociation  extends TAssociationRepository
{
    public function __construct()
    {
        parent::__construct(
            'qnut_calendar_event_resources',
            'qnut_calendar_events',
            'qnut_resources',
            'eventId',
            'resourceId',
            'Peanut\QnutCalendar\db\model\entity\CalendarEvent',
            'Peanut\QnutCalendar\db\model\entity\Resource');
    }
}