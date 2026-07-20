<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/22/2018
 * Time: 6:14 AM
 */

namespace Tops\sys;


use DateTime;

class TDateRepeater
{

    /**
     * Entry point for getRepeatingDates.  Used mostly for testing.
     *
     * @param $year
     * @param $month
     * @param $repeatSpec
     * @param string $pageDirection
     * @return array|bool
     */
    public function getDates($year, $month, $repeatSpec, $pageDirection = '')
    {
        $calendarPage = TCalendarPage::Create($year, $month, $pageDirection);
        return $this->getRepeatingDates($calendarPage,$repeatSpec);
    }

    /**
     * @param $year
     * @param $month
     * @param $repeatSpec (see below)
     * @param $pageDirection - 'right','left' or empty.  To ignore previously fetched dates on paging
     * @return array | bool
     *
     * $repeatSpec string format:
     *    Convention
     *        [pattern-string];[range-string (optional)
     *    Values
     *      Ordinal day-of-week in month = 1..6 = first, second, third, fourth, fifth, last = 6
     *      Day of week = 1..6, Sunday = 1 ... Saturday = 6
     *
     *    Pattern
     *        conventions
     *            two char type+spec
     *            no separator for single char values
     *            comma separator for multi-char values
     *            in day spec 1=sunday..5=last
     *            if no ambiguity, no separator between series of single-char values
     *
     *        Daily
     *            every x days | every weekday
     *                dd30       every thirty days
     *                dw            every weekday
     *        weekly
     *            every x weeks + set of weekdays
     *                wk3,34        every three weeks on tues and wednesday
     *
     *        monthly
     *            day x of y months | Ord day-of-week of every x months  (Ord 6 = last)
     *                md2,23        every two months on 23 if day is greated than last day of month, last day is selected.
     *                mo3,3,4        third wednesday every three months
     *        yearly
     *            every x years on date | every ord dayofweek in month
     *                yd2,2,32    Every 2 years on Feb 32
     *                yo2,4,3,11        4th tues in November
     *    Range
     *        start-date + no-end, after-x, by-date
     *
     *        [pattern-string]2017-09-2                    Start 2017-09-2, no end
     *        [pattern-string];start 2017-09-2,2018-2-12    Start 2017-09-2, End by Feb 12, 2018-2-12
     *
     *      Assumes that start date is within the repeating pattern.
     *      Use getRepeatDateRange to validate and correct the start day and to convert occurance count to an end date
     */
    public function getRepeatingDates(TCalendarPage $calendarPageIn, $repeatSpec)
    {
        // Clone calendar page to avoid unintended modifications
        $calendarPage = clone $calendarPageIn;
        $calendarPage->start = clone $calendarPage->start;
        $calendarPage->end = clone $calendarPage->end;

        // list($pattern, $range) = explode(';', $repeatSpec);
        list($pattern, $range) = TStrings::Split($repeatSpec,';');
        if (empty($range)) {
            return false;
        }
        $patternType = substr($pattern, 0, 2);
        $pattern = substr($pattern, 2);
        $dates = explode(',', $range);
        if (empty($dates)) {
            return false;
        }
        // @list($startDate, $endDate) = explode(',', $range);
        list($startDate, $endDate) = TStrings::Split($range);
        $startDate = TDates::CreateDateTime($startDate);
        if ($startDate === false) {
            return [];
        }
        switch ($patternType) {
            case 'dd' :
                return $this->getDaysSinceStart($startDate, $endDate, $calendarPage, $pattern);
                break;
            case 'dw' :
                return $this->getWeekDays($startDate, $endDate, $calendarPage); // , $pattern);
                break;
            case 'wk' :
                return $this->getWeekly($startDate, $endDate, $calendarPage, $pattern);
                break;
            case 'md' :
                return $this->monthDates($startDate, $endDate, $calendarPage, $pattern);
                break;
            case 'mo' :
                return $this->getOrdinalDaysOfMonth($startDate, $endDate, $calendarPage, $pattern);
                break;
            case 'yd' :
                return $this->getDayOfYear($startDate, $endDate, $calendarPage, $pattern);
                break;
            case 'yo' :
                return $this->getOrdinalDayOfYear($startDate, $endDate, $calendarPage, $pattern);
                break;
            default:
                return false;
        }
    }

    /**
     * Validate and correct the start day and to convert occurance count to an end date
     *
     * @param $pattern   - see getDates()
     * @param $startDate - date string
     * @param null $end  - may be a date string or occurance count
     * @return array  where [0] is start date and optionally [1] is the end date.  Empty array indicates an error
     */
    public function getRepeatDateRange($pattern, $startDate, $end = null)
    {
        $patternType = substr($pattern, 0, 2);
        $pattern = substr($pattern, 2);
        $startDate = TDates::CreateDateTime($startDate);
        if ($startDate === false) {
            return [];
        }
        $endDate = null;
        $occurances = null;
        if (!empty($end)) {
            $endDate = null;
            if (is_numeric($end)) {
                $occurances = $end;
            } else {
                $endDate = TDates::CreateDateTime($end);
            }
        }
        switch ($patternType) {
            case 'dd' :
                if ($occurances !== null) {
                    $endDate = clone $startDate;
                    $days = ($occurances * $pattern) - ($pattern - 1);
                    $endDate->modify("+ $days days");
                }
                break;
            case 'dw' :
                /** @noinspection PhpWrongStringConcatenationInspection */
                $dowOffset = ($startDate->format('w') + 1) % 7;
                if ($dowOffset < 2) {
                    // move saturday/sunday to first week day
                    $startDate->modify(sprintf('+ %d days', 2 - $dowOffset));
                }

                if ($occurances !== null) {
                    $endDate = clone $startDate;
                    $weeks = floor($occurances / 5);
                    $extra = ($occurances % 5);
                    $increment = ($weeks * 7) + $extra;
                    $endDate->modify("+ $increment days");
                }
                break;
            case 'wk' :
                // list($interval, $days) = explode(',', $pattern);
                list($interval, $days) = TStrings::Split($pattern);
                $count = strlen($days);
                $startWeek = TDates::GetSundayThisWeek($startDate);
                $startDay = null;
                for ($i = 0; $i < $count; $i++) {
                    $day = TDates::GetDowThisWeek($startWeek, $days[$i]);
                    if ($day >= $startDate) {
                        $startDay = $days[$i];
                        break;
                    }
                }
                if ($startDay === null) {
                    $startWeek->modify('+7 days');
                    $startDay = $days[0];
                }
                TDates::SetDowThisWeek($startDate, $startDay);

                if ($occurances !== null) {
                    $endDate = $this->calculateWeeklyDowEndDate($startWeek, $startDay, $days, $occurances, $interval);
                }

                break;
            case 'md' :
                // list($interval, $day) = explode(',', $pattern);
                list($interval, $days) = TStrings::Split($pattern);
                $startDay = $startDate->format('d');
                $startMonth = TDates::GetFirstOfMonth($startDate);
                if ($startDay > $day) {
                    $startMonth->modify('+1 month');
                    $startDate = clone $startMonth;
                    TDates::SetDayOfMonth($startDate, $day, TDates::ConstrainEndOfMonth);
                }

                if ($occurances !== null) {
                    $endDate = clone $startDate;
                    $months = ($interval * $occurances) - ($interval);
                    $endDate->modify("+ $months months");
                    $endDate->modify("+ 1 day");
                }

                break;
            case 'mo' :
                // list($interval, $ordinals, $dow) = explode(',', $pattern);
                list($interval, $ordinals, $dow) = TStrings::Split($pattern,',',3);
                $startMonth = TDates::GetFirstOfMonth($startDate);
                $ordinal = substr($ordinals,0,1);
                $lastOrd = substr($ordinals,-1);
                $startDay = TDates::GetOrdinalDayOfMonth($startDate, $ordinal, $dow, TDates::ConstrainEndOfMonth);
                if ($startDay === false || $startDay < $startDate) {
                    $startMonth->modify('+1 month');
                    $startDate = TDates::GetOrdinalDayOfMonth($startMonth, $ordinal, $dow, TDates::ConstrainEndOfMonth);
                }

                if ($occurances !== null) {
                    $endDate = TDates::GetFirstOfMonth($startDate);
                    $months = ($interval * $occurances) - ($interval);
                    $endDate->modify("+ $months months");
                    TDates::SetOrdinalDayOfMonth($endDate,
                        $lastOrd, // $ordinal,
                        $dow);

                    $endDate->modify("+ 1 day");
                }

                break;
            case 'yd' :
                // list($interval, $month, $day) = explode(',', $pattern);
                list($interval, $ordinals, $dow) = TStrings::Split($pattern,',',3);
                $firstDate = TDates::CreateDateTime($startDate->format("Y-$month-$day"));
                if ($firstDate < $startDate) {
                    $startDate->modify('+1 year');
                }

                if ($occurances !== null) {
                    $endDate = clone $startDate;
                    $years = ($interval * ($occurances - 1));
                    $endDate->modify("+ $years years");
                    $endDate->modify("+ 1 day");
                }

                break;
            case 'yo' :
                list($interval, $ordinal, $dow, $month) = explode(',', $pattern);

                $firstDate = TDates::CreateDateTime($startDate->format("Y-$month-1"));
                TDates::SetOrdinalDayOfMonth($firstDate, $ordinal, $dow, TDates::ConstrainEndOfMonth);
                if ($firstDate < $startDate) {
                    $startDate->modify('+1 year');
                }

                if ($occurances !== null) {
                    $endDate = TDates::GetFirstOfMonth($startDate);
                    // $years = ($occurances - 1);
                    $years = ($interval * ($occurances - 1));
                    $endDate->modify("+ $years years");
                    TDates::SetOrdinalDayOfMonth($endDate,$ordinal,$dow);
                    $endDate->modify("+ 1 day");
                }

                break;
            default:
                return [];
        }

        $result = [$startDate->format('Y-m-d')];
        if (!empty($endDate)) {
            $result[] = $endDate->format('Y-m-d');
        }
        return $result;
    }

    private function getDaysSinceStart(DateTime $startDate, $endDate, TCalendarPage $calendarPage, $pattern)
    {
        if (!$calendarPage->update($startDate, $endDate)) {
            return [];
        }

        $options = new \stdClass();
        if ($pattern > 1) {
            $options->skip = $pattern;
            if ($startDate < $calendarPage->start) {
                $offset = ($startDate->diff($calendarPage->start)->days % $pattern) - 1;
                $calendarPage->start->modify("+ $offset days");
            }
        }
        return TDates::getDatesInRange($calendarPage->start, $calendarPage->end, $options);
    }

    private function getWeekDays(DateTime $startDate, $endDate, TCalendarPage $calendarPage) // , $pattern)
    {
        if (!$calendarPage->update($startDate, $endDate)) {
            return [];
        }

        $options = new \stdClass();
        $options->weekDays = true;
        return TDates::getDatesInRange($calendarPage->start, $calendarPage->end, $options);
    }

    public function calculateWeeklyDowEndDate(DateTime $startWeek, $startDow, $days, $occurances, $interval = 1)
    {
        $endDate = clone $startWeek;
        $offset = strpos($days, strval($startDow));
        $count = strlen($days);
        $occurances += $offset;
        $weeks = floor($occurances / $count);
        $extra = $occurances % $count;
        if ($extra == 0) {
            $weeks -= 1;
            $i = $days[$count - 1];
            $day = TDates::GetDowName($i);
        } else {
            $i = $days[$extra - 1];
            $day = TDates::GetDowName($i);
        }
        $weeks *= $interval;
        $endDate->modify("+ $weeks weeks");
        if ($day != 'Sun') {
            $endDate->modify($day);
        }
        $endDate->modify('+1 day');
        return $endDate;
    }

    private function getWeekly(DateTime $startDate, $endDate, TCalendarPage $calendarPage, $pattern)
    {
        @list($interval, $days) = explode(',', $pattern);
        $count = strlen($days);
        $startWeek = TDates::GetSundayThisWeek($startDate);

        if (!$calendarPage->update($startDate, $endDate)) {
            return [];
        }

        $currentWeek = TDates::GetSundayThisWeek($calendarPage->start);

        if ($startWeek == $currentWeek) {
            $calendarPage->start = $startDate;
        } else if ($startWeek > $currentWeek) {
            $currentWeek = $startWeek;
        }

        $intervalDays = $interval * 7;
        if ($interval > 1 && $startWeek < $currentWeek) {
            $offset = ($startWeek->diff($currentWeek)->days % ($intervalDays - 1));
            $currentWeek->modify("+ $offset days");
        }
        $result = [];
        while ($currentWeek <= $calendarPage->end) {
            for ($i = 1; $i <= $count; $i++) {
                $dow = $days[$i - 1];
                $date = TDates::GetDowThisWeek($currentWeek, $dow);
                if ($date >= $calendarPage->end) {
                    return $result;
                }
                if ($date >= $calendarPage->start) {
                    $result[] = $date->format('Y-m-d');
                }
            }
            $currentWeek->modify("+ $intervalDays days");
        }

        return $result;

    }

    private function getMonthRange(DateTime $startMonth, TCalendarPage $calendarPage, $interval)
    {
        $result = new \stdClass();
        $month = TDates::GetFirstOfMonth($calendarPage->start);
        if ($startMonth > $month) {
            $month = $startMonth;
        }
        if ($month == $startMonth) {
            $result->start = $month;
            $result->end = TDates::GetEndOfMonth($month);
        }
        $endMonth = TDates::GetModifiedDate($calendarPage->end, '-1 day');
        TDates::SetFirstOfMonth($endMonth);
        if ($startMonth > $endMonth) {
            return false;
        }
        if ($interval > 1) {
            $offset = $startMonth->diff($month)->m % $interval;
            if ($offset > 0) {
                $offset = $interval - $offset;
                $month->modify("+ $offset months");
            }
        }

        if ($month > $endMonth) {
            return false;
        }
        $result->start = $month;
        $result->end = $endMonth;
        return $result;
    }

    private function monthDates(DateTime $startDate, $endDate, TCalendarPage $calendarPage, $pattern)
    {
        @list($interval, $day) = explode(',', $pattern);

        if (!$calendarPage->update($startDate, $endDate)) {
            return [];
        }
        $startMonth = TDates::GetFirstOfMonth($startDate);
        $monthRange = $this->getMonthRange($startMonth, $calendarPage, $interval);
        if ($monthRange === false) {
            return [];
        }
        /**
         * @var $month DateTime
         */
        $month = $monthRange->start;
        $result = [];
        while ($month <= $monthRange->end) {
            $date = clone $month;
            TDates::SetDayOfMonth($date, $day, TDates::ConstrainEndOfMonth);
            if ($date >= $calendarPage->start && $date <= $calendarPage->end) {
                $result[] = $date->format('Y-m-d');
            }
            $month->modify("+ $interval months");
        }

        return $result;
    }

    private function getOrdinalDaysOfMonth(DateTime $startDate, $endDate, TCalendarPage $calendarPage, $pattern)
    {
        // Outlook, but not Google, supports  day, weekday, and weekend day. Probably not needed.  Consider later.
        @list($interval, $ordinals, $dow) = explode(',', $pattern);

        if (!$calendarPage->update($startDate, $endDate)) {
            return [];
        }

        $startMonth = TDates::GetFirstOfMonth($startDate);
        $monthRange = $this->getMonthRange($startMonth, $calendarPage, $interval);
        if ($monthRange === false) {
            return [];
        }


        $result = [];
        /**
         * @var $month DateTime
         */
        $month = $monthRange->start;
        while ($month <= $monthRange->end) {
            $valid = true;
            $len = strlen($ordinals);
            for ($i = 0; $i < $len; $i++) {
                $date = clone $month;
                $ordinal = substr($ordinals, $i, 1);
            if ($ordinal == 6) {
                TDates::SetLastOrdinalDayOfMonth($date, $dow);
            } else {
                $valid = TDates::SetOrdinalDayOfMonth($date, $ordinal, $dow);
            }
            if ($valid !== false && $date >= $calendarPage->start && $date <= $calendarPage->end) {
                $result[] = $date->format('Y-m-d');
            }
            };
            $month->modify("+ $interval months");
        }

        return $result;
    }

    private function getDayOfYear(DateTime $startDate, $endDate, TCalendarPage $calendarPage, $pattern)
    {
        @list($interval, $month, $day) = explode(',', $pattern);
        if (!$calendarPage->update($startDate, $endDate)) {
            return [];
        }

        $startYear = $startDate->format('Y');
        $thisMonth = TDates::GetFirstOfMonth($calendarPage->start); //  new DateTime($calStart->format('Y-m-1'));
        $endMonth = TDates::GetFirstOfMonth($calendarPage->end); // new DateTime($endDate->format('Y-m-1'));
        $dates = [];
        while ($thisMonth <= $endMonth) {
            $thisYear = $thisMonth->format('Y');
            $currentMonth = $thisMonth->format('m');
            if (($thisYear - $startYear) % $interval == 0 && $currentMonth == $month) {
                $date = TDates::CreateDateTime(sprintf("%d-%d-%d", $thisYear, $currentMonth, $day), TDates::ConstrainMonth);
                if ($date !== false && $date >= $calendarPage->start && $date <= $calendarPage->end) {
                    $dates[] = $date->format('Y-m-d');
                }
            }
            $thisMonth->modify("+1 month");
        }
        return $dates;
    }

    private function getOrdinalDayOfYear(DateTime $startDate, $endDate, TCalendarPage $calendarPage, $pattern)
    {
        @list($interval, $ordinal, $dow, $month) = explode(',', $pattern);

        if (!$calendarPage->update($startDate, $endDate)) {
            return [];
        }

        $startYear = $startDate->format('Y');
        $thisMonth = TDates::GetFirstOfMonth($calendarPage->start); //  new DateTime($calStart->format('Y-m-1'));
        $endMonth = TDates::GetFirstOfMonth($calendarPage->end); // new DateTime($endDate->format('Y-m-1'));
        $dates = [];
        while ($thisMonth <= $endMonth) {
            $thisYear = $thisMonth->format('Y');
            $currentMonth = $thisMonth->format('m');
            if (($thisYear - $startYear) % $interval == 0 && $currentMonth == $month) {
                $date = TDates::GetOrdinalDayOfMonth($thisMonth, $ordinal, $dow, TDates::ConstrainEndOfMonth);
                if ($date !== false && $date >= $calendarPage->start && $date <= $calendarPage->end) {
                    $dates[] = $date->format('Y-m-d');
                }
            }
            $thisMonth->modify("+1 month");
        }
        return $dates;
    }

    /**
    /**
     * @param $pattern string
     * @param $startDate string
     * @param $endDate string | null | number
     * @return string
     */
    public static function AppendPatternDates($pattern,$startDate,$endDate) {
        $result = "$pattern;$startDate";
        if (!empty($endDate)) {
            $result .= ",$endDate";
        }
        return $result;
    }

}