<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 4/14/2019
 * Time: 7:20 AM
 */

namespace Peanut\QnutCalendar\db\model\repository;


use Tops\db\TAssociationRepository;

class CalendarUsergroupsAssociation extends TAssociationRepository
{
    public function __construct()
    {
        parent::__construct(
            'qnut_usergroup_events',
            'qnut_calendar_events',
            'qnut_usergroups',
            'eventid',
            'groupid',
            'Peanut\QnutCalendar\db\model\entity\CalendarEvent',
            'Peanut\QnutUsergroups\db\model\entity\Usergroup'
        );
    }


}