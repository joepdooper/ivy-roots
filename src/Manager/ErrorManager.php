<?php

namespace Ivy\Manager;

use Ivy\Core\Path;

class ErrorManager
{
    public static function setErrorReporting()
    {
        $reporting = $_ENV['ERROR_REPORTING'] ?: 'E_ALL';
        $reporting = defined($reporting) ? constant($reporting) : E_ALL;
        error_reporting($reporting);

        ini_set('ignore_repeated_errors', $_ENV['IGNORE_REPEATED_ERRORS'] ?: 'true');
        ini_set('display_errors', $_ENV['DISPLAY_ERRORS'] ?: 'false');
        ini_set('log_errors', $_ENV['LOG_ERRORS'] ?: 'true');

        $logFile = $_ENV['ERROR_LOG_FILE'];
        if ($logFile) {
            ini_set('error_log', Path::get('BASE_PATH') . $logFile);
        }
    }
}
