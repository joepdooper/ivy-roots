<?php

namespace Ivy\Core;

use Ivy\Config\Environment;

final class Path
{
    private static array $paths = [];
    private static bool $initialized = false;

    private function __construct() {}

    public static function get(string $key): mixed
    {
        self::initialize();
        return self::$paths[$key] ?? null;
    }

    private static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);

        $scriptPath = str_replace('\\', '/', dirname($scriptFilename));
        $scriptPath = rtrim($scriptPath, '/');
        $scriptPath = str_replace($documentRoot, '', $scriptPath);
        $scriptPath = ltrim($scriptPath, '/');

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $domain = isset($_SERVER['SERVER_NAME']) ? $protocol . '://' . $_SERVER['SERVER_NAME'] : 'http://localhost';
        $currentUri = $_SERVER['REQUEST_URI'] ?? '/';
        $currentPage = isset($_SERVER['HTTP_HOST']) ? $protocol . '://' . $_SERVER['HTTP_HOST'] . $currentUri : 'http://localhost/';
        $currentRoute = parse_url($currentUri, PHP_URL_PATH);

        self::$paths = [
            'SUBFOLDER'    => $scriptPath !== '' ? $scriptPath . DIRECTORY_SEPARATOR : '',
            'ROOT'         => $documentRoot . DIRECTORY_SEPARATOR,
            'PROTOCOL'     => $protocol,
            'DOMAIN'       => $domain,
            'CURRENT_PAGE' => $currentPage,
            'CURRENT_ROUTE' => $currentRoute,
            'PROJECT_PATH' => dirname($documentRoot) . DIRECTORY_SEPARATOR,
            'MEDIA_PATH'   => dirname($documentRoot) . DIRECTORY_SEPARATOR . "public/media" . DIRECTORY_SEPARATOR,
            'PLUGINS_PATH'  => dirname($documentRoot) . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR,
            'PLUGINS_FOLDER'  => "plugins" . DIRECTORY_SEPARATOR,
            'TEMPLATES_PATH' => dirname($documentRoot) . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR,
            'TEMPLATES_FOLDER' => "templates" . DIRECTORY_SEPARATOR,
            'PUBLIC_PATH'  => rtrim($documentRoot . DIRECTORY_SEPARATOR . $scriptPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
        ];

        $serverPort = isset($_ENV['APP_ENV']) ? (Environment::isDev() ? self::getServerPort() : '') : self::getServerPort();

        self::$paths['BASE_PATH'] = rtrim(self::$paths['DOMAIN'] . $serverPort . DIRECTORY_SEPARATOR . self::$paths['SUBFOLDER'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        self::$paths['PUBLIC_URL'] = rtrim(self::$paths['DOMAIN'] . $serverPort . DIRECTORY_SEPARATOR . self::$paths['SUBFOLDER'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        self::$initialized = true;
    }

    private static function getServerPort(): string
    {
        $port = $_SERVER['SERVER_PORT'] ?? null;

        if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $port = $_SERVER['HTTP_X_FORWARDED_PORT'];
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if (($isHttps && $port == 443) || (!$isHttps && $port == 80)) {
            return '';
        }

        return ':' . $port;
    }
}
