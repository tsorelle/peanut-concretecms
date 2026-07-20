<?php
/**
 * Created by /tools/create-model.php
 * Time:  2018-02-03 12:35:01
 */

namespace Peanut\QnutCalendar\db\model\entity;

class CalendarEvent  extends \Tops\db\TimeStampedEntity
{
    public $id;
    public $title;
    public $start;
    public $end;
    public $location;
    public $url;
    public $eventTypeId;
    public $notes;
    public $description;
    public $recurPattern;
    public $recurEnd;
    public $recurId;
    public $recurInstance;
    public $allDay;
    public $active;

    public function getDtoDataTypes()
    {
        $types = parent::getDtoDataTypes();
        $types['start'] = \Tops\sys\TDataTransfer::dataTypeDateTime;
        $types['end'] = \Tops\sys\TDataTransfer::dataTypeDateTime;
        $types['recurEnd'] = \Tops\sys\TDataTransfer::dataTypeDate;
        $types['recurInstance'] = \Tops\sys\TDataTransfer::dataTypeDate;
        return $types;
    }

    public function assignFromObject($dto, $username = 'admin')
    {
        $result = parent::assignFromObject($dto, $username);
        if (isset($result->recurPattern) && empty(trim($result->recurPattern))) {
            $result->recurPattern = null;
        }
    }

    public function formatDateRange($startDate,$endDate) {
        return '';
    }
}
