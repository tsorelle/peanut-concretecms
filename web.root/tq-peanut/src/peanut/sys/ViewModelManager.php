<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 7/4/2017
 * Time: 10:37 AM
 */
namespace Peanut\sys;


use Tops\services\TServiceCommand;
use Tops\sys\IUser;
use Tops\sys\TConfiguration;
use Tops\sys\TLanguage;
use Tops\sys\TPath;
use Tops\sys\TStrings;
use Tops\sys\TUser;
use Tops\sys\TWebSite;

class ViewModelManager
{
    /**
     * @var ViewModelInfo[]
     */
    private static $info;
    private static $vmSettings;
    private static $instance;
    private static $packageDir;

    private static $packageList;
    public static function getPackageList() {
        if (!isset(self::$packageList)) {
            self::$packageList = array();
            $packageDir = self::getPackageDir();
            $files = scandir($packageDir);
            foreach ($files as $file) {
                // package must be a directory containing a package.ini file.
                if ($file != '.' && $file != '..' && file_exists("$packageDir/$file/package.ini")) {
                    self::$packageList[] = $file;
                }
            }
        }
        return self::$packageList;
    }

    public static function getPackageDir() {
        if (!isset(self::$packageDir)) {
            if (!defined('DIR_PEANUT_ROOT'))  {
                throw new \Exception("URL_PEANUT_ROOT is definition is required in this version of Peanut.");
            }

            self::$packageDir = DIR_PEANUT_ROOT.'/pnut/packages';
            if (!is_dir(self::$packageDir)) {
                throw new \Exception('Package path not found: '.self::$packageDir);
            }
        }
        return self::$packageDir;
    }

    public static function getPackagePath()
    {
        $dir = self::getPackageDir();
        $offset = strlen(DIR_ROOT)+1;
        // return substr($dir,strlen(DIR_ROOT)+1);
        return substr($dir,$offset);
    }

    public static function getVmUrl($vmName,$package='') {

        if (empty($package)) {
            // $iniPath = TPath::getConfigPath().'viewmodels.ini';
            $iniPath = DIR_CONFIGURATION.'/viewmodels.ini';
        }
        else {
            $iniPath = DIR_PEANUT_ROOT.'/pnut/packages/'.$package.'/config/viewmodels.ini';
        }
        $settings = @parse_ini_file($iniPath, true);
        if (!empty($settings)) {
            foreach ($settings as $name => $section) {
                if (array_key_exists('vm',$section) && $section['vm'] === $vmName) {
                    $subpath = empty($section['location']) ?
                        TConfiguration::getValue('peanutUrl','pages',''):
                        $section['location'];
                    return empty($subpath) ? '/'.$name : '/' .$subpath.'/'.$name;
                }
            }
        }
        return false;

    }

    /**
     * @param $pathAlias
     * @return bool|ViewModelInfo
     */
    public static function getViewModelSettings($pathAlias, $context=null)
    {
        if (!isset(self::$vmSettings)) {
            // $path = TPath::getConfigPath();
            $packageDir = self::getPackageDir();
            self::$vmSettings = parse_ini_file(DIR_CONFIGURATION .DIRECTORY_SEPARATOR. 'viewmodels.ini', true);
            $packages = self::getPackageList();
            if (!empty($packages)) {
                foreach ($packages as $package) {
                    $iniPath = $packageDir . "/$package/config/viewmodels.ini";
                    if (file_exists($iniPath)) {
                        $pkgini = parse_ini_file($packageDir . "/$package/config/viewmodels.ini", true);
                        if (!empty($pkgini)) {
                            $keys = array_keys($pkgini);
                            foreach ($keys as $key) {
                                $pkgini[$key]['package'] = $package;
                            }
                            self::$vmSettings = array_merge(self::$vmSettings, $pkgini);
                        }
                    }
                }
            }
        }

        $key = strtolower($pathAlias);
        if (array_key_exists($key, self::$vmSettings)) {
            $item = self::$vmSettings[$key];

            $vmpath = explode('/', $pathAlias);
            $vmName = empty($item['vm']) ? array_pop($vmpath) : $item['vm'];
            $view = empty($item['view']) ? $vmName . '.html' : $item['view'];
            if (empty($item['package'])) {
                // $root = TConfiguration::getValue('mvvmPath', 'peanut', 'application/peanut');
                $root = URL_APPLICATION . '/peanut';
            }
            else {
                $root = ViewModelManager::getPackagePath()."/".$item['package'];
                $vmName = "@pkg/" . $item['package']."/$vmName";
            }

            $result = new ViewModelInfo();
            $result->pathAlias = $pathAlias;
            $result->vmName = $vmName;
            if ($view == 'content') {
                $result->view = $view;
            } else {
                $location = empty($item['location']) ? '': '/'.$item['location'];
                $parts = explode('/',$view);
                if (sizeof($parts) > 1) {
                    $view = array_pop($parts);
                    $subdir = join($parts);
                    $location .= '/'.join($parts);
                }

                $result->view = $root."$location/view/$view";
            }
            $result->template = empty($item['template']) ? false : $item['template'];
            $result->theme = empty($item['theme']) ? false : $item['theme'];
            $result->pageTitle =  empty($item['page-title']) ?
                TConfiguration::getValue('page-title','pages',$pathAlias) :
                $item['page-title'];

            $result->heading = empty($item['heading']) ? '' :
                '<h1>'.TLanguage::text($item['heading']).'</h1>'
            ;

            $permissions = array_key_exists('permissions', $item) ? $item['permissions'] : '';
            $result->permissions = TStrings::ListToArray($permissions);
            $roles = array_key_exists('roles', $item) ? $item['roles'] : '';
            $result->roles = TStrings::ListToArray($roles);

            if ($context) {
                $result->context = $context;
            }

            self::$info[] = $result;
            return $result;
        }
        return false;
    }

    /**
     * @return bool|ViewModelInfo[] | bool
     */
    public static function getViewModelInfo()
    {
        // todo: not used? confirm
        return isset(self::$info) ? self::$info : false;
    }

    public static function RenderMessageElements() {
        if (!empty(self::$info)) {
            print "\n<div id='service-messages-container'><service-messages></service-messages></div>\n";
        }
    }

    public static function RenderStartScript()
    {
        if (!empty(self::$info)) {

            print self::GetStartScript();
        }

    }

    // for testing
    public static function setVmInfo(array $items) {
        self::$info = $items;
    }

    public static function GetStartScript()
    {
        // todo::Enable and test.
        // return "<!-- GetStartScript() output here -->";
        /*       if (empty(self::$info)) {
                   return '';
               }
               $vmName = self::$info->vmName;
               // print "\n<!-- start script for '$vmName' goes here -->\n";

               return
               "\n<script>\n" .
                   "   Peanut.PeanutLoader.startApplication('$vmName'); \n"
                   . "</script>\n";*/

        if (empty(self::$info)) {
            return '';
        }

        $lines = array();
        $count = count(self::$info);
        $i = 1;
        $tabs = '  ';

        $lines[] = "Peanut.PeanutLoader.renderServiceMessageTags();";

        foreach(self::$info as $vmInfo) {
            $tabs .= '  ';
            $method = $i == 1 ? 'startApplication' : 'loadViewModel';
            $vmName = $vmInfo->vmName;
            if ($vmInfo->context) {
                $vmName .= '#'.$vmInfo->context;
            }
            $invoke = $tabs."Peanut.PeanutLoader.$method('$vmName'";
            if ($i < $count) {
                $invoke .= ', function() {';
            }
            else {
                $invoke .= ');';
            }
            $lines[] = $invoke;
            $i++;
        }
        for ($i = 1; $i < $count; $i++ ) {
            $tabs = substr($tabs,0,strlen($tabs) - 2);
            $lines[] = $tabs.'});';
        }


        // print "\n<!-- start script for '$vmName' goes here -->\n";
        $c=count($lines);

        return
            "\n<script>\n" .
            implode("\n",$lines)
            . "\n</script>\n";

    }


    /**
     * See if this request is related to a ViewModel.
     * Check self::$info which is set by Initialize()
     *
     * @return bool
     */
    public static function hasVm()
    {
        return !empty(self::$info);
    }

    private static $peanutVersion;
    public static function GetPeanutVersion() {
        if (isset(self::$peanutVersion)) {
            return self::$peanutVersion;
        }
        $fileRoot = TPath::getFileRoot();
        $peanutPath = TConfiguration::getValue('peanutRootPath','peanut');
        $pnutIniPath = "$fileRoot$peanutPath/dist/peanut.ini";
        if (file_exists($pnutIniPath)) {
            $pnutIni = parse_ini_file($pnutIniPath,true);
            if (empty($pnutIni['peanut']['version'])) {
                return 'error-invalid-peanut-ini';
            }
            else {
                self::$peanutVersion = $pnutIni['peanut']['version'];
                return self::$peanutVersion;
            }
        }
        return 'error-no-peanut-ini';


    }

    public static function isAuthorized(IUser $user, ViewModelInfo $viewModelInfo) {
        if ($user->isAdmin()) {
            return true;
        }
        $default = true;
        if (!empty($viewModelInfo->permissions)) {
            $default = false;
            foreach ($viewModelInfo->permissions as $permission) {
                if ($user->isAuthorized($permission)) {
                    return true;
                }
            }
        }

        if (!empty($viewModelInfo->roles)) {
            $default = false;
            foreach ($viewModelInfo->roles as $role) {
                switch ($role) {
                    case 'guest' :
                        return true;
                    case 'authenticated' :
                        return $user->isAuthenticated();
                    default :
                        if ($user->isMemberOf($role)) {
                            return true;
                        }
                        break;
                }
            }
        }
        return $default;
    }

    public static function authorize(ViewModelInfo $settings) {
        $user = TUser::getCurrent();
        $authorized = ViewModelManager::isAuthorized($user,$settings);
        if (!$authorized) {
            header('HTTP/1.0 403 Forbidden');
            $message = $user->isAuthenticated()  ?  'not-authorized' : 'not-authenticated';
            $messagePage = ViewModelPageBuilder::BuildMessagePage($message);
            print $messagePage;
            exit;
        }

    }

    public static function ExtractVmName($uri) {
        $result = parse_url(trim($uri),PHP_URL_PATH);
        if (strpos($result,'.') !== false) {
            return false; // it is a file name not a vm name
        }
        $parts = explode('/',$result);
        if (empty($parts)) {
            return false;
        }
        if (empty($parts[0])) {
            array_shift($parts);
        }
        if (empty($parts)) {
            return false;
        }
        if (empty($parts[sizeof($parts) - 1])) {
            array_pop($parts);
        }
        return join('/',$parts);
    }

}