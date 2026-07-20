<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/7/2018
 * Time: 6:55 AM
 */

namespace Tops\sys;


class TInterval
{
    public static function stringToInterval($frequency) {
        $spec = self::stringToIntervalSpec($frequency);
        if (empty($spec)) {
            return $spec;
        }
        try {
            return new \DateInterval($spec);

        } catch (\Exception $ex) {
            return false;
        }
    }

    public static function stringToIntervalSpec($frequency)
    {
        if (empty($frequency)) {
            return null;
        }
        $frequency = str_replace('and',',',$frequency);
        $parts = array_filter(explode(',', trim($frequency)));
        $count = count($parts);
        $result = 'P';
        $isTime = false;
        for ($i = 0; $i < $count; $i++) {
            list($unitCount,$unit) = array_filter(explode(' ', trim($parts[$i])));
            $part = self::getIntervalSpec($unitCount, $unit,$isTime);
            if ($isTime && strpos($result,'T') == 0) {
                $result .= 'T';
            }
            $result .= $part;
        }
        return $result;
    }


    public static function getIntervalSpec($count, $unit,&$isTime)
    {
        $isTime = false;
        if (is_numeric($count) && $count > 0) {
            $unit = trim(strtoupper($unit));
            if (empty($unit)) {
                return null;
            }
            if (substr($unit, 0, 3) == 'MI') {
                $isTime = true;
                $unit = 'M';
            } else {
                $unit = substr($unit, 0, 1);
                $isTime = ($unit == 'H' || $unit == 'S');
            }
            return $count . $unit;
        }
        return '';
    }

    public static function intervalToString($intervalSpec)
    {
        $intervalSpec = trim(strtoupper($intervalSpec));
        if (empty($intervalSpec)) {
            return '';
        }
        if (substr($intervalSpec,0,1) != 'P') {
            return 'Invalid: ' . $intervalSpec;
        }


        $spec = substr($intervalSpec,1);
        $parts = explode('T',$spec);
        $count = count($parts);
        if ($count < 1 || $count > 2) {
            return 'Invalid: ' . $intervalSpec;
        }

        $results = array();
        for($i = 0;$i<$count;$i++) {
            $spec = $parts[$i];
            $len = strlen($spec);
            if ($len == 0) {
                continue;
            }
            $isTime = $i > 0;
            $unitCount = '';
            for ($p = 0; $p < $len; $p++) {
                $c = substr($spec,$p,1);
                if (is_numeric($c)) {
                    $unitCount .= $c;
                }
                else {
                    switch ($c) {
                        case 'H' :
                            $unit = 'hour';
                            break;
                        case 'S' :
                            $unit = 'second';
                            break;
                        case 'Y' :
                            $unit = 'year';
                            break;
                        case 'M' :
                            $unit = $isTime ? 'minute' : 'month';
                            break;
                        case 'D' :
                            $unit = 'day';
                            break;
                        case 'W' :
                            $unit = 'week';
                            break;
                        default:
                            return 'Invalid: ' . $spec;
                    }
                    if ($unitCount > 1) {
                        $unit .= 's';
                    }
                    $results[] = $unitCount.' '.$unit;
                    $unitCount = '';
                }
            }
        }
        $last = array_pop($results);
        if (empty($results)) {
            return $last;
        }
        return implode(', ',$results).' and '.$last;

    }

}