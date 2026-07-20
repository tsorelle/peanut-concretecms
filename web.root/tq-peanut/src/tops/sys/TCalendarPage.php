<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/30/2018
 * Time: 5:56 AM
 */

namespace Tops\sys;


use DateTime;

class TCalendarPage
{
    /**
     * @var integer
     */
    public  $year;
    /**
     * @var integer
     */
    public $month;
    /**
     * @var DateTime
     */
    public $start;
    /**
     * @var DateTime
     */
    public $end;

    public static function Create($year,$month,$pageDirection='')
    {
        $result = new TCalendarPage();
        $result->year = $year;
        $result->month = $month;
        $startDate = new DateTime(sprintf('%d-%d-1', $year, $month));
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        if ($startDate->format('D') != 'Sun') {
            $startDate->modify('last sun of last month');
        }
        if ($endDate->format('D') != 'Sat') {
            $endDate->modify('first sat of next month');
        }

        // $firstDay = $startDate->format('d');
        $endDate->modify('+ 1 day');
        if ($pageDirection =='left') {
            // todo: test for last day of prev page is last day of month.
            $tempDate = clone($endDate);
            $tempDate->modify('last day of this month');
            if ($endDate != $tempDate) {
                $endDate->modify('-7 days');
            }
        }
        else if ($pageDirection == 'right'
            && $startDate->format('d') != 1 // if first date of calendar is first of month, there is no overlap.
        ) {
            $startDate->modify('+7 days');
        }

        $result->start = $startDate;
        $result->end = $endDate;
        return $result;
    }

    /**
     * Constrain range, return false if out of range
     *
     * @param $startDate
     * @param $endDate
     * @return bool
     */
    public function update($startDate,$endDate) {
        $startDate = TDates::ToDateTime($startDate);
        if (empty($startDate)) {
            return false;
        }
        if ($startDate > $this->start) {
            $this->start = $startDate;
        }

        $endDate = TDates::ToDateTime($endDate);
        if ($endDate === false) {
            return false; // invalid date
        }
        if (empty($endDate)) {
            $endDate = $this->end;
        }
        else if ($endDate < $this->end) {
            $this->end = $endDate;
        }

        if ($endDate <= $startDate) {
            return false;
        }

        if ($startDate >= $this->end) {
            return false;
        }
        return true;
    }

    public function getFirstOfMonth() {
        return new DateTime(sprintf('%d-%d-1',$this->year,$this->month));
    }


}