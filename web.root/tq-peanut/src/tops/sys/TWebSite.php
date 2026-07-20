<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/4/2017
 * Time: 6:51 AM
 */

namespace Tops\sys;


class TWebSite
{
    private static $baseUrl=null;
    public static function GetSiteUrl() {
        global $_SERVER;
        if (isset($_SERVER['REQUEST_SCHEME'])) {
            $protocol = $_SERVER['REQUEST_SCHEME'];
        }
        else {
            if (isset($_SERVER['HTTPS'])) {
                $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
            } else {
                $protocol = 'http';
            }
        }
        return $protocol . "://" . $_SERVER['HTTP_HOST'];
    }

    public static function GetClientIp()
    {
        global $_SERVER;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            // Check if IP is from shared internet
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Check if IP is passed from a proxy
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            // Default remote address
            return $_SERVER['REMOTE_ADDR'];
        }
        return 'unknown';
    }

    public static function ExpandUrl($url) {
        if (empty($url)) {
            return self::GetBaseUrl();
        }
        $scheme = parse_url($url,4);
        if (!empty($scheme)) {
            $scheme = strtolower($url); // PHP_URL_SCHEME
        if ($scheme=='http' || $scheme =='https:') {
            return $url;
            }
        }
        $base = self::GetBaseUrl();
        if (empty($url)) {
            return $base;
        }
        return strpos($url,'/') === 0 ? $base.$url : "$base/$url";
    }
    public static function GetLocalUrl($path, $filename = ''): string
    {
        $path = trim((string)($path ?? ''));
        $filename = trim((string)($filename ?? ''));
        if ($path === '' && $filename === '') {
            return '';
        }
        $normalize = static function (string $value): string {
            $value = str_replace('\\', '/', $value);
            $value = preg_replace('#^https?://[^/]+#i', '', $value) ?? $value;
            return trim($value, " /\t\n\r\0\x0B");
        };
        $path = $normalize($path);
        $filename = $normalize($filename);
        if ($filename === '') {
            return $path === '' ? '' : '/' . $path;
        }
        if ($path === '') {
            return '/' . $filename;
        }
        return '/' . $filename . '/' . $path;
    }

    public static function GetBaseUrl(){
        if (self::$baseUrl!==null) {
            return self::$baseUrl;
        }
        self::$baseUrl = TConfiguration::getValue('url','site');
        if (empty(self::$baseUrl))  {
            self::$baseUrl = self::GetSiteUrl();
        }
        return self::$baseUrl;
    }

    public static function GetDomain() {
        global $_SERVER;
        if (!isset($_SERVER['SERVER_NAME'])) {
            return 'localdomain';
        }
        $parts = explode('.',strtolower($_SERVER['SERVER_NAME']));
        if ($parts[0] == 'www') {
            array_shift($parts);
        }
        return join('.',$parts);
    }

    public static function IsLocalHost() {
        $domain = self::GetDomain();
        return ($domain == 'localhost' || $domain == 'localdomain' || str_starts_with($domain,'local.'));
    }
    public static function reset() {
        self::$baseUrl = null;
    }

    public static function SetBaseUrl($value) {
        $baseUrl = $value;
    }

    public static function GetCmsType() {
        if (defined('C5_EXECUTE')) {
            return 'ConcreteCMS';
        }
        if (defined('ABSPATH')) {
            // WordPress
            // return  (function_exists('register_block_type') ? 'Gutenberg' : 'WordPress');
            return 'WordPress';
        }
        if (defined('NUTSHELL')) {
            return 'Nutshell';
        }
        // not supported: Drupal, Joomla, others
        // not in supported CMS
        return 'unknown';
    }
    /**
     * Get subdomain part of URL
     */
    public static function GetSubdomainName() {
        $domain = strtolower(self::GetDomain());
        $parts = explode('.',$domain);
        $count = count($parts);
        if ($count === 3) {
            if ($parts[0] == 'www') {
                return '';
            }
            return $parts[0];
        }
        return '';
    }


    /**
     * Follows a convention where first part of sub-domain indicates deployment environment.
     * e.g. staging, testing, local.  If no subdomain assume 'production'
     * Unsupported subdomains return 'unknown'
     * Override for a specific installation in settings.ini [site] section...
     * enviornment=(environment name)
     *
     * Returns 'production','staging','testing','local','unknown' or settings value
     */
    public static function GetEnvironmentName() {
        $result = TConfiguration::getValue('environment','site');
        if (!empty($result)) {
            return $result;
        }
        $domain = strtolower(self::GetDomain());
        if ($domain == 'localhost' || str_starts_with($domain,'local.')) {
            return 'local';
        }
        $parts = explode('.',$domain);
        if (count($parts) == 2) {
            return 'production';
        }
        if (count($parts) == 3) {
            $first = $parts[0];
            if ($first == 'dev' || $first == 'test' || $first == 'testing') {
                return 'testing';
            }
            if ($first == 'staging' || $first == 'stage') {
                return 'staging';
            }
        }
        return 'unknown';
    }

    public static function IsTestEnvironment() {
        $env = self::GetEnvironmentName();
        return ($env == 'testing' || $env == 'staging');
    }

    public static function AppendRequestParams($url,array $params) {
        if (substr($url,-1) == '/') {
            $url = substr($url,0,strlen($url) -1);
        }
        $delim = strpos($url,'?') === false ? '?' : '&';
        foreach ($params as $key => $value) {
            $url .= $delim.$key.'='.$value;
            $delim = '&';
        }
        return $url;
    }

// todo: compare with new version
    public static function version_GetClientIp(): ?string
    {
        $keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Standard proxy chain
            'HTTP_X_REAL_IP',        // Nginx
            'REMOTE_ADDR'            // Always present
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // X-Forwarded-For may contain multiple IPs
                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '';
    }

}