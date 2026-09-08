<?php

namespace Ivy\Shared\Infrastructure\Manager;

use Ivy\Shared\Core\Path;

class ErrorManager
{
    public static function setErrorReporting(): void
    {
        $reporting = $_ENV['ERROR_REPORTING'] ?: 'E_ALL';
        $reporting = defined($reporting) ? constant($reporting) : E_ALL;
        error_reporting($reporting);

        ini_set(
            'ignore_repeated_errors',
            filter_var($_ENV['IGNORE_REPEATED_ERRORS'] ?? true, FILTER_VALIDATE_BOOL)
        );

        ini_set(
            'display_errors',
            filter_var($_ENV['DISPLAY_ERRORS'] ?? false, FILTER_VALIDATE_BOOL)
        );

        ini_set(
            'log_errors',
            filter_var($_ENV['LOG_ERRORS'] ?? true, FILTER_VALIDATE_BOOL)
        );

        $logFile = $_ENV['ERROR_LOG_FILE'];
        if ($logFile) {
            ini_set('error_log', Path::get('PROJECT_PATH').$logFile);
        }
    }
}
