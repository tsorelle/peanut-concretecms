<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 8/14/2017
 * Time: 5:31 PM
 */

namespace Tops\concrete5;

use Peanut\sys\PeanutInstaller;
use Peanut\sys\PeanutSettings;
use Tops\sys\TPath;

class Concrete5PeanutInstaller extends PeanutInstaller
{
    const bootstrapStartMarker='/** Peanut Installation (do not remove comment) **/';
    const bootstrapEndMarker='/** End Peanut Installation (do not remove comment) **/';

    public function doCustomSetup($testing = false)
    {
        $packagePath = PeanutSettings::GetModulePath();
        $bootstrapPath = TPath::fromFileRoot('application/bootstrap');
        $templateFile = "$packagePath/installation/bootstrap/app-php.txt";
        if (!file_exists($templateFile)) {
            return;
        }
        $appFile = "$bootstrapPath/app.php";
        $appLines = file($appFile);
        foreach ($appLines as $line) {
            if (trim($line) == self::bootstrapStartMarker) {
                // installation already complete, skip it
                return;
            }
        }

        $templateLines = file($templateFile);
        $appLines[] = "\n" . self::bootstrapStartMarker . "\n";
        $lines = array_merge($appLines, $templateLines);
        $lines[] = "\n" . self::bootstrapEndMarker . "\n";
        $bakFile = "$bootstrapPath/app-php-backup.txt";
        if (!file_exists($bakFile)) {
            copy($appFile, $bakFile);
        }
        $output = join("", $lines);
        @file_put_contents($appFile, $output);
    }

    public function doTeardown($testing = false)
    {
        $bootstrapPath = TPath::fromFileRoot('application/bootstrap');
        $appFile = "$bootstrapPath/app.php";
        $appLines = file($appFile);
        $skipLines = false;
        $output = array();
        $changed = false;
        foreach ($appLines as $line) {
            $line = trim($line);
            if ($line == self::bootstrapStartMarker) {
                $changed = true;
                $skipLines = true;
            }
            else if ($line == self::bootstrapEndMarker) {
                $skipLines = false;
            }
            else if (!$skipLines) {
                $output[] = $line;
            }
        }
        if ($changed) {
            file_put_contents($appFile,implode("\n",$output));
        }
    }
}