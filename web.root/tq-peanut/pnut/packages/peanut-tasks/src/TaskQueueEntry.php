<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/30/2017
 * Time: 6:11 AM
 */

namespace Peanut\PeanutTasks;


use Tops\sys\TDataTransfer;
use Tops\sys\TDates;
use Tops\sys\TInterval;
use Tops\sys\TLanguage;
use Zend\I18n\Validator\DateTime;

class TaskQueueEntry
{
    public $id;
    public $frequency;
    public $intervalType;
    public $taskname;
    public $namespace;
    public $startdate;
    public $enddate;
    public $inputs;
    public $comments;
    public $active;

    public function assignFromObject($dto) {
        $dt = new TDataTransfer($dto,$this,[
            'startdate' => TDataTransfer::dataTypeDate,
            'enddate' => TDataTransfer::dataTypeDate,
        ]);
        $dt->assignAll();
        $dt->assignDefaultValues([
            'active' => 1
        ]);

        $errors = [];
        $valid = $this->validate();
        if ($valid !== true) {
            @list($key,$value) = explode(':',$valid);
            $errors[$key] = $value;
        }

        return $errors;
    }

    public function validate()
    {
        if (empty($this->taskname)) {
            $this->taskname = '';
        }
        else {
            $this->taskname = trim($this->taskname);
        }
        if ($this->taskname === '') {
            return 'taskname:No taskname';
        }
        if (!is_numeric($this->intervalType)) {
            return "intervalType:Invalid interval type $this->intervalType";
        }
        if (empty($this->frequency)) {
            $this->frequency = '';
        } else {
            $this->frequency = trim($this->frequency);
        }

        $pieces = explode(' ',$this->frequency);
        $parts = [];
        $count = count($pieces);
        for($i=0;$i<$count;$i++) {
            $piece = trim($pieces[$i]);
            if ($piece) {
                $parts[] = $piece;
            }
        }
        $count = count($parts);
        $this->frequency = implode(' ',$parts);
        switch ($this->intervalType) {
            case 1:
                if (!empty($this->frequency)) {
                    return "frequency:Frequency should not be assigned for run on demand.";
                }
                break;
            case 2: // regular
                // $this->ready = TDates::CompareDates($lastRun,$this->frequency) == TDates::Before;
                if (empty($this->frequency)) {
                    return "frequency:Frequency not assigned.";
                }
                if (TInterval::stringToInterval($this->frequency) === false) {
                    return "frequency:Invaild frequency '$this->frequency'";
                }
                break;

            case 3: // week of month
                if ($count < 1 || $count > 4) {
                    return "frequency:Invaild frequency '$this->frequency'";
                }
                $ord = substr($parts[0],0,1);
                $dowPart = 1;
                if (is_numeric($ord)) {
                    if ($ord < 1 || $ord > 5) {
                        return "frequency:Invaild ordinal in frequency '$this->frequency'";
                    }
                }
                else {
                    $dowPart = 0;
                }
                $dow = @$parts[$dowPart];
                $dow = TDates::ToShortDow($dow);
                if ($dow === false) {
                    return "frequency:Invaild day of week in frequency '$this->frequency'";
                }
                $parts[$dowPart] = $dow;
                $timePart = $dowPart + 1;
                $time = @$parts[$dowPart + 1];
                if ($time) {
                    if ($count > $timePart + 1) {
                        $time .= ' '.array_pop($parts);
                    }
                    $h24 = TDates::To24HourTime($time);
                    if ($h24 === false) {
                        return "frequency:Invaild day of week in frequency '$this->frequency'";
                    }
                    $parts[$timePart] = $h24;
                }

                $this->frequency = implode(' ',$parts);

                break;
            case 4: // daily
                if ($this->frequency) {
                    $h24 = TDates::To24HourTime($this->frequency);
                    if ($h24 === false) {
                        return "frequency:Invaild day of week in frequency '$this->frequency'";
                    }
                    $this->frequency = $h24;
                }
                break;
            case 5:
                if (empty($this->frequency)) {
                    return "frequency:Frequency not assigned.";
                }
                if (TDates::CreateDateTimeObject($this->frequency) === false) {
                    return "frequency:Invaild date in frequency '$this->frequency'";
                }
                break;

            default:
                return "intervalType:Invalid interval type $this->intervalType";
        }
        return true;
    }

    /**
     * @param $lastRun
     * @return \stdClass
     * @throws \Exception
     */
    public function readyToRun($lastRun = false) {
        $result = new \stdClass();
        $result->error = '';
        $result->ready = false;

        if ($this->intervalType == 1) {
            $result->ready = true;
            return $result;
        }
        $lastRunDateTime = $lastRun ?  TDates::CreateDateTimeObject($lastRun) : false;
        if ($lastRunDateTime === false) {
            $lastRunDateTime = new \DateTime('2000-01-01');  // early date for comparisons
            $lastRunDate = clone( $lastRunDateTime);
        }
        else {
            $lastRunDate = new \DateTime($lastRunDateTime->format('Y-m-d'));
        }

        $now = new \DateTime();
        $today = new \DateTime($now->format('Y-m-d'));
        switch ($this->intervalType) {
            case 2: // regular
                // $this->ready = TDates::CompareDates($lastRun,$this->frequency) == TDates::Before;
                $targetDate = (clone($lastRunDateTime))->modify($this->frequency);
                $result->ready = $now >= $targetDate;
                break;
            case 3: // week of month
                @list($ordinal, $dow, $time) = explode(' ',$this->frequency);
                if (!$ordinal) {
                    $result->error ='Week ordinal missing';
                    return $result;
                }
                $ord = substr($ordinal,0,1); // assumes 1st, 2nd, ect.
                if (!is_numeric($ord)) {
                    $time = $dow;
                    $dow = $ordinal;
                    $ord = 0;
                }

                if (!$dow) {
                    $result->error ='Day of week missing';
                    return $result;
                }
                if ($ord > 0) {
                    $dow = TDates::GetDowNumber(substr($dow, 0, 3));
                    $first = TDates::GetFirstOfMonth($now);
                    $targetDate = TDates::GetOrdinalDayOfMonth($first, $ord, $dow);
                    if ($time) {
                        $targetDate = new \DateTime($targetDate->format('Y-m-d').' '.$time);
                    }
                    $result->ready =
                        (
                            $lastRunDate < $targetDate &&
                            $now >= $targetDate
                        );
                }
                else {
                    $targetDate = $time ?
                        $targetDate = new \DateTime($today->format('Y-m-d').' '.$time) :
                        $today;

                    $result->ready = (
                        substr($dow,0,3) == $today->format('D') &&
                        $lastRunDate < $today &&
                        $targetDate >= $today
                    );
                }
                break;
            case 4: // daily
                $targetDate = $this->frequency ?
                    new \DateTime(date('Y-m-d').' '.$this->frequency) :
                    $today;
                $result->ready = (
                        $lastRunDate < $today &&
                        $now >= $targetDate );
                break;
            case 5: // fixed time
                $frequecyDateTime = TDates::CreateDateTime($this->frequency);
                if ($frequecyDateTime == false) {
                    $result->error = 'Invalid date';
                    return $result;
                }
                $frequecyDate = new \DateTime($frequecyDateTime->format('Y-m-d'));

                $result->ready =
                ($lastRunDate != $today &&
                    $frequecyDate == $today &&
                    $frequecyDateTime <= $now
                );
                break;
            default:
                $result->error = 'Invalid interval type';
                break;
        }
        return $result;
    }
}