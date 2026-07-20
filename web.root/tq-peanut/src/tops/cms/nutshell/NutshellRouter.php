<?php

namespace Tops\cms\nutshell;
use Peanut\sys\ViewModelManager;
use Peanut\users\AccountManager;
use Tops\cms\TRouteFinder;
use Tops\cms\TRouter;
use Tops\sys\TConfiguration;
use Tops\sys\TPath;
use Tops\sys\TSession;
use Tops\sys\TUser;

class NutshellRouter extends TRouter
{
    private function setSwitchValue(&$routeData,$name,$default=1) {
        $value = $routeData[$name] ?? $default;
        $routeData[$name] =  empty($value) ? 0 : 1;
        $routeData['test'] = $name;
    }


    function routeCms()
    {
        // TODO: Implement routeCms() method.
    }

    function routePage()
    {
        /*
          Additional configuration values
                openpanel
                paneltitle
                addwrapper
                inputvalue
        */

        $routeData = TRouteFinder::$matched;
        $uri = $routeData['uri'] ?? null;
        $user = TUser::getCurrent();
        $theme = $routeData['theme'] ?? 'default';
        $routeData['theme'] = $theme;
        if (!empty($routeData['title'])) {
            $routeData['title'] = str_replace('Nutshell',
                TConfiguration::getValue('sitetitle','site'),
                $routeData['title']);
        }
        $routeData['themePath'] = URL_APPLICATION.'/themes/' . $theme;
        $extra = TPath::fromFileRoot(URL_APPLICATION.'/themes/' . $theme.'/extra.css');
        if (file_exists($extra)) {
            $routeData['extraStyles'] = true;
        }
        // $routeData['themeIncludePath'] = DIR_ROOT."/application/themes/$theme/inc";
        $routeData['themeIncludePath'] = PNUT_APPLICATION."/themes/$theme/inc";
        $user = TUser::getCurrent();
        $routeData['editorsignedin'] = $user->isAuthorized('editsongs');
        $routeData['signin'] = $user->isAuthenticated() ?
            $user->getFullName().' | '.'<a class="ms-2" href="/signout">Sign Out</a>' :
            '<a id="footer-signin-link" href="/signin">Sign in</a>';

        if ($theme === 'plain') {
            $routeData['bscdn'] = 1;
            $routeData['maincolsize'] = 12;
            $this->setSwitchValue($routeData,'siteheader',0);
            $this->setSwitchValue($routeData,'sitefooter',0);
            $this->setSwitchValue($routeData,'breadcrumbs',0);
            $this->setSwitchValue($routeData,'pageheader',0);
        }
        else {
            $routeData['bscdn'] = 0;
            $routeData['fasrc'] = TConfiguration::getValue('fontawesome','header');
            $this->setSwitchValue($routeData,'siteheader',1);
            $this->setSwitchValue($routeData,'sitefooter',1);
            $this->setSwitchValue($routeData,'pageheader',1);
            $this->setSwitchValue($routeData,'frontpage',0);
            $this->setSwitchValue($routeData,'embed',0);
            if ($uri === 'home') {
                $this->setSwitchValue($routeData, 'breadcrumbs', 0);
            }
            else {
                $this->setSwitchValue($routeData,'breadcrumbs',1);
            }
            $maincolsize = 12;
            if (isset($routeData['menu'])) {
                if (!isset($routeData['colsize'])) {
                    $routeData['colsize'] = 6;
                }
                $maincolsize -= $routeData['colsize'];
                if (!isset($routeData['menutype'])) {
                    $routeData['menutype'] = 'default';
                }
            }

            $routeData['maincolsize'] = $maincolsize;
        }

        if (isset($routeData['view'])) {
            $view = PNUT_APPLICATION . '/content/pages/' . $routeData['view'] . '.php';
        } else if (isset($routeData['mvvm'])) {
            $viewModelKey = $routeData['mvvm'];
            if (TConfiguration::getBoolean('optimize','peanut',false)) {
                $routeData['loaderScript'] = 'peanut-loader.min.js';
            }
            else {
                $routeData['loaderScript'] = 'PeanutLoader.js';
            }
            $vmInfo = ViewModelManager::getViewModelSettings($viewModelKey);

            if (empty($vmInfo)) {
                $errorMessage = "Error: Cannot find view model configuration for '$viewModelKey'</h2>";
            } else {
                $viewResult = $vmInfo->view ?? null;
                if ($viewResult == 'content') {
                    $errorMessage = 'Embedded views not supported in Nutshell';
                } else {
                    $view = DIR_ROOT . '/' . $viewResult;
                    if (!file_exists($view)) {
                        $errorMessage = "View file not found: $viewResult";
                    }
                }

                if (!isset($errorMessage)) {
                    if (array_key_exists('return',$routeData)) {
                        $return = $routeData['return'];

                        if ($return == 'referrer') {
                            $return = $_SERVER['HTTP_REFERER'] ?? null;
                        }
                        $_SESSION[AccountManager::redirectKey] = $return;
                        unset($routeData['return']);
                    }
                    $argNames = $argNames = $routeData['args'] ?? '';
                    if ($argNames) {
                        $argNames = explode(',',$argNames);
                        $argValues = $routeData['argValues'] ?? [];
                        if (!empty($routeData['argValues'])) {
                            $valueCount = count($argValues);
                            while(count($argNames) > $argValues) {
                                array_shift($argNames);
                            }

                            $pageVars = [];
                            for ($i = 0;$i < $valueCount; $i++) {
                                $pageVars[$argNames[$i]] = $argValues[$i];
                            }
                            $routeData['pageVars'] = $pageVars;
                            unset($routeData['args']);
                        }
                    }

                    $postArgName = $routeData['postarg'] ?? null;
                    if ($postArgName) {
                        $postArgValue = $_POST[$postArgName] ?? null;
                        if ($postArgValue) {
                            $pageVars = $routeData['pageVars'] ?? [];
                            $pageVars[$postArgName.'-value'] = $postArgValue;
                            $routeData['pageVars'] = $pageVars;
                            unset($_POST[$postArgName]);
                        }
                    }

                    $array = explode('/', $vmInfo->vmName);
                    $containerId = array_pop($array);
                    $routeData['containerId'] = strtolower($containerId) . "-view-container";

                    // init security token
                    TSession::Initialize();
                }

            }
            if (isset($errorMessage)) {
                $view = PNUT_APPLICATION . '/content/pages/error-page.php';
                $routeData['errorMessage'] = $errorMessage;
                unset($routeData['mvvm']);
                unset($routeData['viewcontainerid']);
                unset($routeData['inputvalue']);
                unset($routeData['paneltitle']);
                unset($routeData['openpanel']);
                unset($routeData['addwrapper']);
            }
        }

        $routeData['view'] = $view;
        $routeData['sitemap'] = new SiteMap($uri);
        extract($routeData);
        include PNUT_APPLICATION . '/content/page.php';

    }

    function redirectToSignIn($signInPage=null)
    {
        if (!$signInPage) {
            $signInPage = TConfiguration::getValue('signin', 'pages', '/signin');
        }
        if (!str_starts_with($signInPage, '/')) {
            $signInPage = '/' . $signInPage;
        }
        $signInPage= '/signin';
        $return = preg_replace("/(^\/)|(\/$)/", "", $_SERVER['REQUEST_URI']);
        if (!str_starts_with($return, '/')) {
            $return = '/' . $return;
        }
        $signInPage .= "?return=$return";// . urlencode($return);
        header('Location: ' . $signInPage);
        exit;
    }
}