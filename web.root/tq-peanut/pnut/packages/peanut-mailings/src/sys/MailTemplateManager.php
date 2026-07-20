<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/12/2017
 * Time: 12:56 PM
 */

namespace Peanut\PeanutMailings\sys;


use Tops\sys\TLanguage;
use Tops\sys\TPath;
use Tops\sys\TWebSite;

class MailTemplateManager
{
    const pnutTemplateLocation = 'mail/templates';
    const appTemplateLocation = 'application/peanut/mail/templates';

    private static $instance;

    private $templateList;

    private $tokenFormat = '[[%s]]';

    public static function GetInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new MailTemplateManager();
        }
        return self::$instance;
    }

    public static function CreateMessageText($templateName,array $tokens = []) {
        $manager = self::GetInstance();
        $content = $manager->getTemplateContent($templateName);
        if ($content === false) {
            return false;
        }
        return $manager->replaceTokens($content,$tokens);
    }

    public function replaceTokens($content, array $tokens) {
        foreach ($tokens as $name=>$value) {
            $token = sprintf($this->tokenFormat,$name);
            $content = str_replace($token,$value,$content);
        }
        return $content;
    }



    private function scanTemplateDirectory($path,$result=['html' => [],'text' => []]) {
        $langs = TLanguage::GetSiteLanguageCodes();
        foreach ($langs as $language) {
            $language = strtolower($language);
            if (is_dir("$path/$language")) {
                $files = scandir("$path/$language");
                foreach ($files as $file) {
                    $parts = explode('.',$file);
                    $ext = array_pop($parts);
                    if (($ext == 'html' || $ext == 'txt')) {
                        $key = $ext == 'txt' ? 'text' : 'html';
                        if (!in_array($file,$result[$key])) {
                            $result[$key][] = $file;
                        }
                    }
                }
            }
        }
        return $result;
    }

    private function sortTemplateList(array $templates,$format) {
        $list = array_unique($templates[$format]);
        asort($list);
        $templates[$format] = $list;
    }

    public function getTemplateFileList()
    {
        if (!isset($this->templateList)) {
            // $global = PeanutSettings::FromPeanutRoot(self::pnutTemplateLocation, TPath::normalize_no_exception);
            $global = DIR_PEANUT_ROOT.'/'.self::pnutTemplateLocation;
            $local = TPath::fromFileRoot(self::appTemplateLocation);
            $templates = $this->scanTemplateDirectory($local);
            $templates =  $this->scanTemplateDirectory($global, $templates);
            $this->sortTemplateList($templates,'html');
            $this->sortTemplateList($templates,'text');
            $this->templateList = $templates;
        }
        return $this->templateList;
    }

    public function getTemplateContent($templateFileName) {
        $root = TPath::fromFileRoot(self::appTemplateLocation, TPath::normalize_no_exception);
        $templatePath = TLanguage::FindLangugeFile($root,$templateFileName,TLanguage::useSiteLanguage);
        if (empty($templatePath)) {
            // $root = PeanutSettings::FromPeanutRoot(self::pnutTemplateLocation, TPath::normalize_no_exception);
            $root = DIR_PEANUT_ROOT.'/'.self::pnutTemplateLocation;
            $templatePath = TLanguage::FindLangugeFile($root,$templateFileName,TLanguage::useSiteLanguage);
            if (empty($templatePath)) {
                return false;
            }
        }
        return @file_get_contents($templatePath);
    }

    /**
     * @param $text
     * @return string
     *
     * Replaces add site url to links in text. Typically for email text.
     * Assumes the link format is literally: <a href="
     * This is the format TinyMce uses for inserted links.
     */
    public static function ExpandLocalHrefs($text,$siteUrl=null) {
        if (!$siteUrl) {
            $siteUrl = TWebSite::GetSiteUrl();
        }
        $parts = explode(' href="',$text);
        $count = count($parts);
        if ($parts < 2) {
            return $text;
        }
        for ($i = 1; $i < $count; $i++) {
            $part = $parts[$i];
            if (substr($part,0,4) == 'http' || substr($part,0,2) == '[[') {
                continue;
            }
            else if (substr($part,0,1) == '/') {
                $parts[$i] = $siteUrl.$part;
            }
            else {
                $parts[$i] = $siteUrl.'/'.$part;
            }
        }
        return implode(' href="',$parts);
    }


}