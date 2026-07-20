<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 11/4/2017
 * Time: 9:27 AM
 */

namespace Peanut\sys;


use Tops\db\TDbTranslator;
use Tops\sys\TIniFileMerge;
use Tops\sys\TIniSettings;
use Tops\sys\TIniTranslator;
use Tops\sys\TLanguage;
use Tops\sys\TPath;

class PeanutTranslator extends TIniTranslator
{
    /**
     * @throws \Exception
     */
    public static function GetPeanutTranslations(&$ini = false) {
        $peanutIniPath = DIR_PEANUT_ROOT.'/pnut/translations.ini';

        if ($ini === false) {
            $ini = TIniSettings::Open($peanutIniPath);
        }
        else {
            TIniFileMerge::Import($peanutIniPath,$ini);
        }
        $packages = ViewModelManager::getPackageList();
        if (!empty($packages)) {
            $packageDir = ViewModelManager::getPackageDir();
            foreach ($packages as $package) {
                $inipath =  $packageDir."/$package/config/translations.ini";
                TIniFileMerge::Import($inipath,$ini);
            }
        }
        return $ini;
    }

    public static function ImportPeanutTranslations($username='admin') {
        $translator = new TDbTranslator();
        $translations = self::GetPeanutTranslations();
        return $translator->importTranslations($translations,$username);
    }

    public function importIniTranslations(&$ini)
    {
        PeanutTranslator::GetPeanutTranslations($ini);
    }


}