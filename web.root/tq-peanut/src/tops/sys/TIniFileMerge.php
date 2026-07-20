<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/18/2017
 * Time: 3:13 PM
 */

namespace Tops\sys;


class TIniFileMerge
{
    public static function merge($source,$target) {
        $instance = new TIniFileMerge();
        $instance->mergeIni($source,$target);
    }

    public static function Import($inipath,&$ini=array()) {
        if (file_exists($inipath)) {
            $secondIni = parse_ini_file($inipath,true);
        }
        else {
            return $ini;
        }
        if (empty($ini)) {
            return $secondIni;
        }
        if (!empty($secondIni)) {
            foreach (array_keys($secondIni) as $sectionKey) {
                $items = $secondIni[$sectionKey];
                if (!empty($items)) {
                    if (!isset($ini[$sectionKey])) {
                        $ini[$sectionKey] = $items;
                    }
                    else {
                        foreach ($items as $key => $value) {
                            $ini[$sectionKey][$key] = $value;
                        }
                    }
                }
            }
        }
        return $ini;
    }


    private $sourceIni;
    private $targetIni;
    private $missing;
    private $output = array();
    private $oldSection = '';

    private function stripComment($line)
    {
        $line = trim($line);
        $p = strpos($line,';');
        return $p==false  ? $line : trim(substr($line,0,$p));
    }

    private function appendSettings($values) {
        foreach ($values as $name => $value) {
            if (!is_numeric($value)) {
                $value = "'$value'";
            }
            $this->output[] = "$name=$value\n";
        }
    }

    private function getSection($line)
    {
        $line = $this->stripComment($line);
        if (strpos($line,'[') === 0) {
            $section = str_replace('[', '', $line);
            $section = str_replace(']', '', $section);
            return trim($section);
        }
        return false;
    }

    private function appendMissingSection() {
        $lastLine = trim(array_pop($this->output));
        if (!empty($lastLine)) {
            $this->output[] = "$lastLine\n";
        }
        if ((!empty($this->oldSection)) && isset($this->missing[$this->oldSection])) {
            $this->appendSettings($this->missing[$this->oldSection]);
            unset($this->missing[$this->oldSection]);
        }
    }

    private function appendMissing() {
        $lastLine = trim(array_pop($this->output));
        if (!empty($lastLine)) {
            $this->output[] = "$lastLine\n";
        }
        foreach ($this->missing as $section => $values) {
            $this->output[] = "\n[$section]\n";
            $this->appendSettings($values);
        }
    }



    private function parseFiles($sourceFile,$targetFile) {
        $this->missing = array();
        $this->sourceIni = parse_ini_file($sourceFile, true);
        $this->targetIni = parse_ini_file($targetFile, true);
        foreach ($this->sourceIni as $section => $settings) {
            foreach ($settings as $name => $value) {
                if (!isset($this->targetIni[$section][$name])) {
                    $this->missing[$section][$name] = $value;
                }
            }
        }
        return (!empty($this->missing));
    }

    private function mergeIni($sourceFile,$targetFile)
    {
        if (file_exists($targetFile) && $this->parseFiles($sourceFile,$targetFile)) {
            $lines = file($targetFile);
            foreach ($lines as $line) {
                $sectionName = $this->getSection($line);
                if ($sectionName !== false) {
                    $this->appendMissingSection();
                    $this->oldSection = $sectionName;
                    $this->output[] = "\n";
                }
                $this->output[] = $line;
            }
            $this->appendMissing();
            $targetFile = str_replace('\\', '/', $targetFile);
            $parts = explode('/', $targetFile);
            $fileName = array_pop($parts);
            $backupFile = implode('/', $parts) . '/old-' . str_replace('.ini', '-ini.txt', $fileName);
            copy($targetFile, $backupFile);
            unlink($targetFile);
            file_put_contents($targetFile, implode($this->output));
        }
        else {
            copy($sourceFile,$targetFile);
        }
    }
}