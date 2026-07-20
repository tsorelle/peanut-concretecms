<?php

namespace Tops\cms;

class TRouteFinder
{
    public static ?array $matched = null;
    public static ?array $routes = null;

    private static function normalizeUri($uri) : string
    {
        $parts = explode('?',$uri);
        $uri = $parts[0];
        if ($uri === '' || $uri === '/') {
            $uri = 'home';
        }
        return $uri;
    }

    public static function matchWithRedirect($uri) : bool
    {
        $matched = self::match($uri);
        if (!$matched) {
            $path = parse_url($uri, PHP_URL_PATH);
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if ($ext) {
                return false;
            }
            $settings = parse_ini_file(DIR_CONFIGURATION . '/settings.ini', true);
            if (isset($settings['locations']['defaultredirect']) ) {
                $sub = $settings['locations']['defaultredirect'];
                if ($sub) {
                    $parts = explode('/', $uri);
                    if ($parts) {
                        $uri = "$sub/" . array_pop($parts);
                        return self::match($uri);
                    }
                }
            }
        }
        return $matched;
    }

    public static function GetRoutes()
    {
        if (!isset(self::$routes)) {
            self::$routes =
                parse_ini_file(DIR_CONFIGURATION . '/routing.ini', true);
        }
        return self::$routes;
    }
    public static function match($uri) : bool
    {
        $uri = self::normalizeUri($uri);
        self::GetRoutes();
        foreach (self::$routes as $matchPath => $values) {
            if (str_starts_with($uri, $matchPath)) {
                if ($uri != $matchPath && (!array_key_exists('args',$values))) {
                    continue;
                }
                $matchParts = explode('/', $matchPath);
                $matchCount = count($matchParts);
                $pathParts = explode('/', $uri);
                for ($i = 0; $i < $matchCount; $i++) {
                    if ($pathParts[$i] !== $matchParts[$i]) {
                        return false;
                    }
                }
                $handler = $values['handler'] ?? null;
/*                if ($handler === 'redirect') {
                    $uri=  self::normalizeUri($values['target'] ?? '');
                    header('Location: '.$uri);
                    exit;
                    // continue;
                }*/
                $configuration = $values;
                $pathCount = count($pathParts);
                $argCount = $pathCount - $matchCount;
                if ($argCount > 0) {
                    $argValues = array_splice($pathParts, $matchCount);
                }

                $configuration['uri'] = $uri;
                $configuration['path'] = $matchPath;
                $configuration['argValues'] = $argValues ?? [];
                self::$matched = $configuration;

                return true;
            }
        }
        return false;
    }
}