<?php

namespace Tops\cms;

use Tops\services\ServiceRequestHandler;
use Tops\sys\TConfiguration;
use Tops\sys\TObjectContainer;
use Tops\sys\TUser;
use Tops\sys\TWebSite;

abstract class TRouter
{
    /**
     * @throws \Exception
     */
    public static function Execute() : bool {
        self::checkAuthorization();
        $handler = TRouteFinder::$matched['handler'] ?? null;
        switch (TRouteFinder::$matched['handler'] ?? 'notfound') {
            case 'service' :
                self::routeService();
                break;
            case 'page' :
                self::getInstance()->routePage();
                break;
            case 'cms' :
                self::getInstance()->routeCms();
                break;
            case 'notfound' :
                return false;
                //  break;
            case 'redirect' :
                $redirect = TRouteFinder::$matched['target'] ?? '/';
                header('Location: ' . $redirect);
                exit;
            default:
                throw new \Exception('Invalid configuration. Must include "handler"');
        }
        return true;
    }

    private static $instance;
    private static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = TObjectContainer::get('peanut.router');
        }
        return self::$instance;
    }

    abstract function routeCms();
    abstract function routePage();
    abstract function redirectToSignIn();

    public static function routeService() : void
    {
        $routeData = TRouteFinder::$matched;
        $method = $routeData['method'] ?? null;
        if (empty($method)) {
            throw new \Exception('Value "method" is required in service routing configuration.');
        }
        $handler = new ServiceRequestHandler();
        $argValues = $routeData['argValues'] ?? [];
        if (!empty($argValues)) {
            $handler->$method(...$argValues);
        }
        else {
            $handler->$method();
        }
        exit;
    }

    private static function checkAuthorization()
    {
        $user = TUser::getCurrent();
        $roleList = TRouteFinder::$matched['roles'] ?? '';
        $roleList = trim($roleList);
        if (!empty($roleList)) {
            $roles = explode(',',$roleList);
            $ok = false;
            foreach ($roles as $role) {
                if ($user->isMemberOf($role)) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                if (TUser::getCurrent()->isAuthenticated()) {
                    print('<p>You do not have permission to access this page.</p><p><a href="/">Return to home page.</a></p>');
                    exit;
                }
                $signInConfig = TRouteFinder::GetRoutes()['signin'] ?? [];
                $signInPage = $signInConfig['uri'] ?? null;
                self::getInstance()->redirectToSignIn($signInPage);
            }
        }
    }
    public static function RedirectToLogIn($signInPage = null) : void
    {
        self::getInstance()->redirectToSignIn($signInPage);
    }
}