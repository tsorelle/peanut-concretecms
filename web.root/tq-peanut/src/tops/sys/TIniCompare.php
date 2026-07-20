<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/29/2019
 * Time: 7:11 AM
 */

namespace Tops\sys;


class TIniCompare
{
    private $current;
    private $linecount;

    public static function Reconcile($currentFile,$correctionsFile,$bySection = true) {
        $instance = new TIniCompare();
        return $instance->applyIniOverrides($currentFile,$correctionsFile,$bySection);
    }

    /**
     * @param $currentFile
     * @param $correctionsFile
     * @param bool $bySection
     */
    public function applyIniOverrides($currentFile,$correctionsFile,$bySection = true)
    {
        if (!$bySection) {
            return 'bySection not supported. Maybe later.';
        }
        $corrections = $result = @parse_ini_file($correctionsFile, $bySection);
        if ($corrections === false) {
            return "File: $correctionsFile not found.";
        }
        $this->current = @file($currentFile);
        if ($corrections === false) {
            return "File: $currentFile not found.";
        }
        $this->linecount = count($this->current);

        foreach ($corrections as $section => $items) {
            foreach ($items as $key => $value) {
                $value = trim($value);
                $key = trim($key);
                $newSetting = is_numeric($value) ? "$key=$value\n" : "$key='$value'\n";
                $remove =  $value === '@remove';
                $result = self::findIniLine($key,$section);
                if ($result->section === false) {
                    if ($remove) {
                        continue;
                    }
                    $this->current[] = "\n[$section]\n";
                    $this->current[] = $newSetting;
                    $this->linecount = count($this->current);
                }
                else  {
                    if ($result->line === false) {
                        if ($remove) {
                            continue;
                        }
                        array_splice($this->current,$result->section+1,null,[$newSetting]);
                        $this->linecount = count($this->current);
                    }
                    else {
                        if ($remove) {
                            unset($this->current[$result->line]);
                            $this->linecount = count($this->current);
                        }
                        else {
                            $this->current[$result->line] = $newSetting;
                        }
                    }
                }
            }
        }

        return $this->current;
    }

    private function findIniLine($key,$section=null) {
        $result = new \stdClass();
        $result->section = false;
        $result->line = false;

        for ($i = 0; $i< $this->linecount ;$i++) {
            $line = trim($this->current[$i]);
            if (!$line) {
                continue;
            }
            if (strpos($line,"[$section]") !== false) {
                if ($result->section === false) {
                    $result->section = $i;
                }
                else {
                    break;
                }
            }
            else if ($result->section !== false) {
                if (strpos($line,"[") === 0) {
                    // end of the section we are scanning
                    break;
                }
                $parts = explode(';',$line);
                $line = trim(array_shift($parts));
                if ($line) {
                    @list($lineKey, $lineValue) = explode('=', $line);
                    if ($lineValue !== null && trim($lineKey) === $key) {
                        $result->line = $i;
                        break;
                    }
                }
            }
        }

        return $result;
    }

}