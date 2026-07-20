<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/2/2018
 * Time: 11:19 AM
 */

namespace Peanut\QnutCalendar\db\model\repository;


use Tops\db\TAssociationRepository;

class CalendarCommitteeAssociation  extends TAssociationRepository
{
    public function __construct()
    {
        parent::__construct(
            'qnut_calendar_event_committees',
            'qnut_calendar_events',
            'qnut_committees',
            'eventId',
            'committeeId',
            'Peanut\QnutCalendar\db\model\entity\CalendarEvent',
            '\Tops\db\NamedEntity');
    }

}