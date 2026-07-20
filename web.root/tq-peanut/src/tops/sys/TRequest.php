<?php

namespace Tops\sys;

class TRequest implements IHttpRequest
{
    private static $instance;
    public static function getInstance() : TRequest {
        if (!isset(self::$instance)) {
            self::$instance = new TRequest();
        }
        return self::$instance;
    }

    public function get(string $key) : string | null {
        $result = $_GET[$key] ?? null;

        if ($result === null) {
            $result = $_POST[$key] ?? null;
        }

        return $result;
    }

    public function getMethod() : string {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

}