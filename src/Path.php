<?php

namespace Ivy;

use Dotenv\Dotenv;

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
        $currentPage = isset($_SERVER['HTTP_HOST']) ? $protocol . '://' . $_SERVER['HTTP_HOST'] . ($_SERVER['REQUEST_URI'] ?? '/') : 'http://localhost/';

        self::$paths = [
            'SUBFOLDER'    => $scriptPath !== '' ? $scriptPath . DIRECTORY_SEPARATOR : '',
            'ROOT'         => $documentRoot . DIRECTORY_SEPARATOR,
            'PROTOCOL'     => $protocol,
            'DOMAIN'       => $domain,
            'CURRENT_PAGE' => $currentPage,
            'PUBLIC_PATH'  => rtrim($documentRoot . DIRECTORY_SEPARATOR . $scriptPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
            'MEDIA_PATH'   => "media" . DIRECTORY_SEPARATOR,
            'PLUGIN_PATH'  => "plugins" . DIRECTORY_SEPARATOR,
            'TEMPLATES_PATH' => "templates" . DIRECTORY_SEPARATOR
        ];

        $serverPort = $_ENV['SERVER_PORT'] ?? $_SERVER['HTTP_X_FORWARDED_PORT'] ?? $_SERVER['SERVER_PORT'] ?? 80;
        $serverPort = ($serverPort != 80) ? ':' . $serverPort : '';

        self::$paths['BASE_PATH'] = rtrim(self::$paths['DOMAIN'] . $serverPort . '/' . self::$paths['SUBFOLDER'], '/') . '/';

        self::$initialized = true;
    }
}
