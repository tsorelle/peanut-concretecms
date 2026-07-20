<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/3/2018
 * Time: 10:15 AM
 */

namespace Peanut\QnutCalendar\db\model\entity;

/**
 * Class FullCalendarEvent
 * @package Peanut\QnutCalendar\db\model\entity
 *
 * Matches FullCalendar event object
 *  See:   https://fullcalendar.io/docs/event_data/Event_Object/
 *
 * Additional Properties to handle repeating events. Ignored by FullCalendar:
 *    $repeatPattern
 *     Semicolon seperated list = pattern;start-date{;end-date}
 *     See: Tops\sys\TDateRepeater for descriptoin of pattern format
 *
 * Data retrieval example in Peanut\QnutCalendar\db\model\repository::getFilteredEvents
 */
class FullCalendarEvent extends CalendarSearchEvent
{

    public $url;
    public $eventType;
    public $backgroundColor;
    public $borderColor;
    public $textColor;
    public $recurInstance;
    public $active;

}