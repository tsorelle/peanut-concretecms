<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/14/2017
 * Time: 9:30 AM
 */

namespace Tops\sys;


use DateInterval;
use DateTime;

class TDates
{
    const MySqlDateFormat = 'Y-m-d';
    const MySqlDateTimeFormat = 'Y-m-d H:i:s';
    const IsoDateTimeFormat = 'Y-m-dTH:i:s';
    const FilenameTimeFormat = 'Y-m-d@H-i-s';
    const Equal = 0;
    const Before = -1;
    const After = 1;
    const DowNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    const ConstrainEndOfMonth = 'end of month';
    const ConstrainMonth = 'month';

    public static function reformatDateTime($timeString, $newFormat, $originalFormat = null)
    {
        $time = self::stringToTimestamp($timeString, $originalFormat);
        if ($time === false) {
            return $timeString;
        }
        return date($newFormat, $time);
    }

    public static function ValidDowName($name) {
        return in_array(substr($name,0,3),self::DowNames);
    }



    public static function To24HourTime($time=null,$blanks = '00:00') {
        if ($time===null) {
            return date('H:i');
        }
        $time = trim($time);
        if ($time === '') {
            if ($blanks === true) {
                return '';
            }
            if (strtolower($blanks) === 'now') {
                return date('H:i');
            }
            return $blanks;
        }
        try {
            return (new \DateTime($time))->format('H:i');
        }
        catch(\Exception $ex) {
            return false;
        }
    }

    public static function stringToTimestamp($timeString, $originalFormat = null)
    {
        if (empty($originalFormat)) {
            // assume mysql format
            $originalFormat = self::MySqlDateTimeFormat;
        }
        $dateobj = @DateTime::createFromFormat($originalFormat, $timeString);
        if (!empty($dateobj)) {
            $timeString = $dateobj->format(Datetime::ATOM);
        }
        $time = @strtotime($timeString);
        return $time;
    }

    public static function formatDate($timestamp = null, $format = self::MySqlDateFormat)
    {
        return date($format, $timestamp);
    }

    public static function formatDateTime($timestamp = null, $format = self::MySqlDateTimeFormat)
    {
        return date($format, $timestamp);
    }

    public static function formatMySqlDate($dateString, $includeTime = false)
    {
        if ($dateString === null || empty(trim($dateString))
            || $dateString === '0000-00-00' || $dateString === '0000-00-00 00:00:00') {
            return null;
        }
        $format = $includeTime ? self::MySqlDateTimeFormat : self::MySqlDateFormat;
        $formatted = self::reformatDateTime($dateString, $format);
        if (is_string($formatted) ){
            if (substr($formatted, 0, 10) == '0000-00-00') {
                return null;
            }
            if (strtotime($dateString) === false) {
                return false;
            }
        }
        return $formatted;
    }


    public static function today($format = self::MySqlDateFormat)
    {
        return date($format);
    }

    public static function now($format = self::MySqlDateTimeFormat)
    {
        return date($format);
    }

    const returnTypeMySqlString = 1;
    const returnTypeDateObject = 2;
    const returnTypeCorrectedDate = 3;
    const returnTypeTimeStamp = 4;
    const returnTypeObject = 5;
    const returnTypeInterval = 1;
    const returnTypeInterSpec = 2;


    /**
     * Validates and converts a date string according to the $returnType parameter
     * Valid formats are restricted to m/d/Y and Y-m-d (mysql) with a four-digit year
     *
     * @param $date string
     * @param int $returnType
     * @return bool|DateTime|false|int|null|string
     *
     * Use TDates::returnType... constants to indicate return type of
     *      MySql string, Date object, timestamp or corrected dates.
     *      Corrected dates add leading zeroes on all single digit values.
     *
     * Time parts are ignored and not included in the return value.
     */
    public static function getValidDate($date, $returnType = 1)
    {

        $date = $date == null ? '' : trim(explode(' ', $date)[0]);  // trim off time part
        if (empty($date)) {
            return $returnType == self::returnTypeDateObject ? null : '';
        }

        $formats = ['Y-m-d', 'd/m/Y'];
        $originalFormat = false;
        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $date);
            if ($dateTime !== false) {
                $sep = substr($format, 1, 1);
                $parts = explode($sep, $date);
                $y = strpos($format, 'Y') / 2;
                $year = $parts[$y];
                if (strlen($year) != 4) {
                    return false;
                }
                for ($i = 0; $i < 3; $i++) {
                    if ($i != $y && strlen($parts[$i]) == 1) {
                        $parts[$i] = '0' . $parts[$i];
                    }
                }
                $date = join($sep, $parts);
                if ($dateTime->format($format) == $date) {
                    $originalFormat = $format;
                    break;
                }
            }
        }

        if ($originalFormat === false) {
            return false;
        }

        switch ($returnType) {
            case self::returnTypeDateObject :
                return new \DateTime($date);
            case self::returnTypeCorrectedDate :
                return $date;
            case self::returnTypeTimeStamp:
                return @strtotime($date);
            default : // MySql string
                return $originalFormat === self::MySqlDateFormat ? $date :
                    self::reformatDateTime($date, self::MySqlDateFormat, $originalFormat);
        }
    }

    /**
     * @param $intervalString
     * @return bool|\DateInterval
     *
     * intervalString must be in the form of a count and a unit expressed as a string starting with the letter of the
     * unit part of an interval_spec (see http://php.net/manual/en/dateinterval.construct.php) or 'MI' for minutes
     * this is not case sensitive.  Examples:
     * '10 days', '2 years', '30 minutes', '7 months','30 seconds'
     * Note that '7 m' == '7 months', '7 mi' == '7 minutes'
     *
     * Complex intervals like one hour and thirty minutes are not supported.
     */
    public static function StringToInterval($intervalString) : DateInterval | false
    {
        $parts = array_filter(explode(' ', $intervalString));
        if (sizeof($parts) != 2
            || (!is_numeric($parts[0]))
        ) {
            return false;
        }
        $count = $parts[0];
        $unit = strtoupper($parts[1]);

        if (substr($unit, 0, 2) == 'MI') {
            $prefix = 'PT';
            $unit = 'M';
        } else {
            $unit = substr($unit, 0, 1);
            if (strpos('YMDWHS', $unit) === false) {
                return false;
            }
            $prefix = $unit == 'S' || $unit == 'H' ? 'PT' : 'P';
        }
        return new \DateInterval($prefix . $count . $unit);
    }

    /**
     * Increment the first date and compare with the second.
     *
     * @param $date1 - First (left hand) date to copare
     * @param $offset - An interval string such as '2 days', '90 seconds' etc. see StringToInterval used to
     *                   increment $date1.  Ignored if empty.
     * @param null $date2 - Second (right hand date) to compare. Defaults to current date/time
     * @return bool|int  - False on failure else const TDates::Before (-1), TDates::After (1) or TDates::Equal (0)
     */
    public static function CompareDates($date1, $offset, $date2 = null)
    {
        // reformat to ISO dates for consistent results;
        $date1 = TDates::reformatDateTime($date1, DateTime::ATOM);
        if ($date2 !== null) {
            $date2 = TDates::reformatDateTime($date2, DateTime::ATOM);
        }
        try {
            $left = new \DateTime($date1);
            $right = $date2 === null ?
                new \DateTime() :
                new \DateTime($date2);
        } catch (\Exception $ex) {
            return false;
        }

        if (!empty($offset)) {
            $interval = self::StringToInterval($offset);
            if ($interval === false) {
                return false;
            }
            $left->add($interval);
        }
        $dif = $right->diff($left);

        // all elements except micro-seconds are equal
        if ($dif->format("%R%Y%M%D%H%I%S") === '+000000000000') {
            return 0;
        }

        /*
         * If a DateInterval object was returned, you can check the 'invert' property to see if the second date is
         * before the first date or not. DateInterval::invert will be 1 if the second date is before the first date,
         * and 0 if the the second date is on or after the first date.
         */
        return $dif->invert == 0 ? TDates::After : TDates::Before;
    }

    /**
     * CompareDates with no offset
     *
     * @param $date1
     * @param null $date2
     * @return bool|int
     */
    public static function CompareTwoDates($date1, $date2 = null)
    {
        return self::CompareDates($date1, null, $date2);
    }

    /**
     * CompareDates with no offset
     *
     * @param $date1
     * @return bool|int
     */
    public static function CompareWithNow($date1)
    {
        return self::CompareDates($date1, null, null);
    }

    public static function GetMySqlDateProperty($object, $propertyName, $includeTime = false)
    {
        if (property_exists($object, $propertyName)) {
            $result = TDates::formatMySqlDate($object->$propertyName, $includeTime);
            return $result;
        }
        return false;
    }

    public static function isValidDateString($dateString) {
        return strtotime($dateString) !== false;
    }

    public static function buildCalendar($assignFunction,$month=null,$year=null,$format='Y-m-d') {
        if ($month === null ) {
            $month = date('m');
        }
        if ($year === null) {
            $year = date('Y');
        }
        $monthStart = strtotime(sprintf('%d-%d-1',$year,$month));
        $monthEnd = strtotime('last day of this month', $monthStart);// time());
        $date = date('N', $monthStart) == 7 ? $monthStart : strtotime('last Sunday', $monthStart);
        $calEnd = date('N', $monthEnd) == 6 ? strtotime('+ 1 day',$monthEnd) : strtotime('next Sunday', $monthEnd);
        $result = [];
        while ($date != $calEnd) {
            $datestr = date($format,$date);
            $result[$datestr] = $assignFunction($date);
            $date =  strtotime('+ 1 day',$date);
        }

        return $result;
    }

    /**
     * @param $year
     * @param $month
     * @param $dayofweek -- valid date format string e.g.  'Wed','wed','Wednesday' or 'wednesday' or number where 1=sunday
     */
    public static function getWeekDates($year,$month,$dayofweek) {
        if (is_numeric($dayofweek)) {
            if ($dayofweek < 1 || $dayofweek > 7) {
                // invalid input
                return false;
            }
            // since October 2017 started on sunday we can get day of week from format
            $dayofweek = date('D', mktime(0, 0, 0, 10, $dayofweek, 2017));
        }
        $ordinals = ['first', 'second', 'third', 'fourth', 'fifth'];
        $date = new DateTime(sprintf("%d-%d-1", $year, $month));
        $date->modify(sprintf("last %s of this month", $dayofweek));
        $last = clone $date;
        $result = [];
        foreach ($ordinals as $ordinal) {
            $date->modify(sprintf("%s %s of this month", $ordinal, $dayofweek));
            $result[] = $date->format('Y-m-d');
            if ($date == $last) {
                break;
            }
        }
        return $result;
    }

    public static function getDatesInRange(DateTime $startDate,DateTime $endDate,$options=null) {
        $weekDays = !empty($options->weekDays);
        $skip = empty($options->skip) ? 1 : $options->skip;
        $result = [];
        $current = clone $startDate;
        while ($current < $endDate) {
            $dow = $weekDays ? $dow = $current->format('D') : '' ;
            if ($dow != 'Sat' && $dow != 'Sun') {
                $result[] = $current->format('Y-m-d');;
            }
            $current->modify("+ $skip days");
        }
        return $result;
    }
    
    public static function GetCalendarMonth($year,$month,$pageDirection='')
    {
        $result = new \stdClass();
        $startDate = new DateTime(sprintf('%d-%d-1', $year, $month));
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        if ($startDate->format('D') != 'Sun') {
            $startDate->modify('last sun of last month');
        }
        if ($endDate->format('D') != 'Sat') {
            $endDate->modify('first sat of next month');
        }
        $endDate->modify('+ 1 day');
        if ($pageDirection =='left') {
            $endDate->modify('-7 days');
        }
        else if ($pageDirection == 'right') {
            $startDate->modify('+7 days');
        }

        $result->start = $startDate;
        $result->end = $endDate;
        return $result;
    }

    public static function DowListToArray($days) {
        $count = strlen($days);
        $result = [];
        if ($count < 8) {
            for ($i = 0; $i < $count; $i++) {
                // since October 2017 started on sunday we can get day of week from format
                $result[] = date('D', mktime(0, 0, 0, 10, $days[$i], 2017));
            }
        }
        return $result;
    }

    public static function GetDowName($n) {
        if ($n > sizeof(self::DowNames)) {
            return false;
        }
        return self::DowNames[$n-1];
    }

    public static function GetDowNumber($s) {
        return array_search($s,self::DowNames) + 1;
    }

    public static function SetDowThisWeek(DateTime $date,$dow) {
        if (!is_numeric($dow)) {
            $dow = self::GetDowNumber($dow);
        }
        $currentDow = self::GetDowNumber($date->format('D'));
        if ($currentDow !== $dow) {
            $dif = $dow - $currentDow;
            $adjustment = sprintf('%s %d days',$dif < 0 ? "-" : '+',abs($dif));
            $date->modify($adjustment);
        }
    }

    /**
     * @param DateTime $date
     * @param $dow
     * @param string $format
     * @return DateTime
     */
    public static function GetDowThisWeek(DateTime $date,$dow,$format='Y-m-d') {
        $result = clone $date;
        self::SetDowThisWeek($result,$dow);
        return $result;
    }

    /**
     *
     *
     * @param $date  - assumes format M-d-y
     * @return bool|DateTime
     */
    public static function CreateDateTime($date,$constraint=self::ConstrainEndOfMonth)
    {
        $t = strtotime($date);
        if ($t === false) {
            return false;
        }
        $d = new DateTime(date('Y-m-d',$t));
        if ($d !== false && $constraint != false) {
            // check for false positive, Any valid number produces a 'true' result but might be wrong
            $original = date_parse($date);
            $result = self::checkDateConstraint($d,$original,$constraint);
            if ($result === false) {
                return false;
            }
        }
        return $d;
    }

    public static function CreateDateObject($date) {
        return self::CreateDateTimeObject($date,true);
    }

    public static function CreateDateTimeObject($date,$stripTime = false)
    {
        try {
            $t = strtotime($date);
            if ($t === false) {
                return false;
            }
            $format = $stripTime ? 'Y-m-d' :  DATE_ATOM;
            $d = new \DateTime(date($format,$t));
            return $d;
        }
        catch(\Exception $ex) {
            return false;
        }
    }

    public static function ToShortDow($dow='today') {
        $dow = trim($dow);
        if (is_numeric($dow)) {
            $result = @self::DowNames[$dow - 1];
            return $result ?? false;
        }
        $dow = ucfirst(strtolower($dow));
        if ($dow === 'Today') {
            return date('D');
        }
        $dow = substr(ucfirst($dow),0,3);
        return in_array($dow,self::DowNames) ? $dow : false;
    }

    public static function ToDateTime($date)
    {
        if (empty($date)) {
            return null;
        }
        if (is_string($date)) {
            return self::CreateDateTime($date,self::ConstrainMonth);
        }
        else if (is_object($date)) {
            return $date;
        }
        return false;
    }

    public static function GetLastDayOfMonth($year,$month) {
        $year = ltrim($year,'0');
        $month = ltrim($month,'0');

        if (!is_numeric($year) || !is_numeric($month)) {
            return false;
        }

        $year = (int) $year;
        $month = (int) $month;

        if ($month < 1 || $month > 12) {
            return false;
        }

        if (function_exists('cal_days_in_month')) {
            return @cal_days_in_month(CAL_GREGORIAN, $month, $year);
        }
        $d = DateTime::createFromFormat('Y-m-d',"$year-$month-1");
        if ($d === false) {
            return false;
        }
        $d->modify("+ 1 month");
        $d->modify("-1 day");
        return $d->format('d');
    }

    /**
     * @param DateTime $date
     * @param $i
     * @param bool $constraint
     * @return bool|string
     *
     * In PHP a modfication by month treat a month unit as 31 days (go figure)
     * This routine accounts for diferences in number of days in a month
     */
    public static function IncrementMonth(DateTime $date,$i,$constraint=false) {
        $month = $date->format('n');
        $day = $date->format('j');
        $year = $date->format('Y');
        $original = $date->format('Y-m-d');

        $direction = $i>0 ? 'next' : 'last';
        $steps = abs($i);
        for ($i = 0; $i<$steps; $i++) {
            $date->modify("first day of $direction month");
        }
        $month = $date->format('n');
        $year = $date->format('Y');
        $last = self::GetLastDayOfMonth($year,$month);
        if ($day > $last) {
            $day = $last;
        }
        $date->setDate($year,$month,$day);
        $result = $date->format('Y-m-d');

        if ($constraint) {
            return self::checkDateConstraint($date,
                date_parse($original),
                $constraint);
        }
        return $result;
    }

    public static function IncrementDate(DateTime $date,$i,$unit,$constraint=false)
    {
        if (strtolower($unit) === 'month') {
            return self::IncrementMonth($date,$i,$constraint);
        }
        $modification = sprintf('%s %d %s', ($i < 0 ? '-' : '+'), abs($i), $unit);
        return self::ModifyDate($date, $modification,$constraint);
    }

    public static function SetSundayThisWeek(DateTime $date) {
        if ($date->format('D') !== 'Sun') {
            $date->modify('sunday last week');
        }
    }

    public static function GetSundayThisWeek($date) {
        $date = is_string($date) ?
            new DateTime($date) :
            clone $date;

        if ($date->format('D') !== 'Sun') {
            $date->modify('sunday last week');
        }
        return $date;
    }

    /**
     * @param \DateTime|NULL $date
     *
     * @return \DateTime
     * @throws \Exception
     */
    public static function GetNextSunday(DateTime $date = null) {
        return self::GetNextDayOfWeek('sunday',$date);
    }

    /**
     * @param string $dow
     * @param \DateTime|NULL $date
     *
     * @return \DateTime
     * @throws \Exception
     */
    public static function GetNextDayOfWeek($dow='saturday',DateTime $date = null) {
        if ($date === null) {
            $date = new \DateTime();
        }
        else {
            $date = is_string($date) ?
                new DateTime($date) :
                clone $date;
        }

        if ($date->format('D') !== substr(ucfirst($dow),0,3)) {
            $date->modify('next '.$dow);
        }
        return $date;
    }


    private static function checkDateConstraint(DateTime $date, $original, $constraint) {
        if ($constraint !== false) {
            $month = $original['month'];
            if ($date->format('m') != $month) {
                $day = $original['day'];
                $year = $original['year'];
                switch ($constraint) {
                    case self::ConstrainMonth :
                        $date->setDate($year, $month, $day);
                        return false;
                    case self::ConstrainEndOfMonth :
                        $day = self::GetLastDayOfMonth($original['year'], $original['month']);
                }
                $date->setDate($year, $month, $day);
            }
        }
        return $date->format('Y-m-d');
    }

    public static function ModifyDate(DateTime $date,$modification,$constraint=self::ConstrainMonth) {
        if ($constraint) {
            $original = date_parse($date->format('Y-m-d'));
            $date->modify($modification);
            return self::checkDateConstraint($date,$original,$constraint);
        }
        else {
            $date->modify($modification);
        }

        return $date->format('Y-m-d');
    }

    public static function SetDayOfMonth(DateTime $date,$day,$constraint = self::ConstrainMonth) {
        $original = date_parse($date->format('Y-m-d'));
        $date->setDate($original['year'],$original['month'],$day);
        if ($constraint) {
            return self::checkDateConstraint($date,$original,$constraint);
        }
        return $date->format('Y-m-d');
    }

    public static function GetEndOfMonth(DateTime $date) {
        $year = $date->format('Y');
        $month = $date->format('m');
        $lastday = self::GetLastDayOfMonth($year,$month);
        return new DateTime("$year-$month-$lastday");
    }

    public static function SetLastOrdinalDayOfMonth(DateTime $date,$dow) {
        $month = $date->format('m');
        $dow = self::GetDowName($dow);
        $date->modify("5 $dow");
        if ($date->format('m') !== $month) {
            $date->modify('-7 days');
        }
        return $date;
    }

    /**
     * @param DateTime|null $date
     * @return int
     * @throws \Exception
     */
    public static function GetWeekNumber(DateTime $date = null) {
        $date = $date ? clone($date) : new \DateTime();
        $n = 0;
        $m = $date->format('n');
        while(true) {
            $n++;
            $date->modify('-1 week');
            if ($date->format('n') != $m) {
                break;
            }
        }
        return $n;
    }

    public static function SetFirstOfMonth(DateTime $date) {
        $date->setDate($date->format('Y'),$date->format('m'),1);
    }

    public static function GetFirstOfMonth(DateTime $date) {
        $date = clone $date;
        $date->setDate($date->format('Y'),$date->format('m'),1);
        return $date;
    }

    public static function GetModifiedDate(DateTime $date,$modification) {
        $date = clone $date;
        $date->modify($modification);
        return $date;
    }

    public static function SetOrdinalDayOfMonth(DateTime $date, $order, $dow,$constraint = self::ConstrainMonth)
    {
        if (is_numeric($dow)) {
            if ($order == 6) {
                return self::SetLastOrdinalDayOfMonth($date,$dow);
            }
            if ($order < 1 || $order > 5) {
                return false;
            }
            $dow = self::GetDowName($dow);
            if ($dow === false) {
                return false;
            }
        }
        return self::ModifyDate($date, "$order $dow", $constraint);
    }

    public static function GetOrdinalDayOfMonth(DateTime $date, $order, $dow,$constraint = self::ConstrainMonth) {
        $date = clone $date;
        $result = self::SetOrdinalDayOfMonth($date,$order,$dow,$constraint);
        return $result === false ? false : $date;
    }

    public static function IncrementToDOW(\DateTime $date, $dow = 1,$first=1) {
        $d =  TDates::GetDowNumber($date->format('D'));
        $offset =  $dow - $d;
        if ($offset < 0) {
            $offset += 7;
        }
        $offset += ($first -1) * 7;
        if ($offset) {
            TDates::IncrementDate($date,$offset,'days');
        }
    }

    public static function offsetMinutesToOffsetString(int $minutes): string {
        $sign = $minutes >= 0 ? '+' : '-';
        $minutes = abs($minutes);
        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;
        return sprintf('%s%02d:%02d', $sign, $hours, $mins);
    }
    /**
     * @param int $minutes
     * @return \DateTimeZone
     * @throws \DateInvalidTimeZoneException
     *
     * In javascript, the offset in minutes can be obtained as:
     *      new Date().getTimezoneOffset();
     *
     * This value can be passed to a service which can use this function to convert to
     * a string that php can use to get the timezone
     */
    public static function getTimezoneForOffsetMinutes(int $minutes): \DateTimeZone {
        $offset = self::offsetMinutesToOffsetString($minutes);
        return new \DateTimeZone($offset);
    }
    public static function getLocalTimeZone(mixed $localTz)
    {
        if (is_numeric($localTz)) {
            return self::getTimezoneForOffsetMinutes($localTz);
        }
        return new \DateTimeZone($localTz);
    }
    public static function localDateTimeToUtc(string $dtString, mixed $timezone) : \DateTime
    {
        if ($timezone instanceof \DateTimeZone) {
            $localTz = $timezone;
        }
        else {
            $localTz = self::getLocalTimeZone($timezone);
        }
        $localDt =  new \DateTime($dtString, $localTz);
        $utc = clone $localDt;
        $utc->setTimezone($localTz);
        return $utc;
    }
    // not used?
    public static function numericOffsetToString(mixed $tz) : string
    {
        if (is_numeric($tz)) {
            $offset = intdiv($tz, 60);
            $hrs = intdiv($tz, 60);
            $mins = $offset % $hrs;
            return sprintf('%02d:%02d', $hrs, $mins);
        }
        return $tz;
    }
}