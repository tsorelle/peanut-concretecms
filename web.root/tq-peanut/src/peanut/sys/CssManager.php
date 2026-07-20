<?php

namespace Peanut\sys;

use Tops\sys\TConfiguration;

class CssManager
{
    public static function getStyleSheets($themePath=null) : string {
        $sheets = [];
        $ver = TConfiguration::getValue('applicationVersionNumber','peanut','no-version');
        $styles = TConfiguration::getIniSection('style-sheets');
        if (empty($styles)) {
            if (empty($themePath)) {
                return '';
            }
            $sheets = [sprintf('<link rel="stylesheet" type="text/css" href="%s/styles.css?v=%s"/>',
                $themePath,$ver)];
        }
        else {
            foreach ($styles as $id => $href) {
                $txt =  sprintf("<link rel='stylesheet' id='%s'  type='text/css' href='%s?v=%s' media='all'/>",
                    $id,$href,$ver);
                $sheets[] = $txt;
            }
        }
        return "\n".implode("\n",$sheets)."\n";
    }
}