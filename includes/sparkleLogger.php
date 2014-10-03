<?php
/**
 * Created by PhpStorm.
 * User: Shujaa
 * Date: 01/10/2014
 * Time: 10:34
 */

/* Severities are
 *
 * 7 : debug
 * 6 : info
 * 5 : notice // No termination
 * 4 : warning // No termination, will be recorded
 * 3 : error // Script will terminate, will be recorded
 * 2 : critical // Script will terminate and errors will be shown whatever happens, will be recorded
 * 1 : alert // NOT USABLE
 * 0 : panic // NOT USABLE
 */

class sparkleLogger {
    /**
     * Error levels
     */
    const LEVEL_DEBUG = 7;
    const LEVEL_INFO = 6;
    const LEVEL_NOTICE = 5;
    const LEVEL_WARN = 4;
    const LEVEL_ERROR = 3;
    const LEVEL_CRIT = 2;

    /**
     * Is the library activated ?
     * @var bool
     */
    const LOGGER_DEBUG = false;

    /**
     * Limit level for logging to DB (min WARN)
     * @var int
     */
    private static  $LOG_DB_LEVEL = self::LEVEL_WARN; // Force log to db from Warning
    /**
     * Limit level for displaying (min CRIT)
     * @var int
     */
    private static $DISPLAY_LEVEL = self::LEVEL_CRIT; // Force display from CRITICAL
    /**
     * Limit level for halting script (Min ERROR)
     * @var int
     */
    private static $FATAL_LEVEL = self::LEVEL_ERROR; // Force stop from ERROR

    /**
     * Inverted array for nice a display of error levels
     * @var array
     */
    public static $levels = [
        "",
        "",
        "<span style='background-color: red;'>CRITICAL</span>",
        "<span style='background-color: orangered;'>ERROR</span>",
        "<span style='background-color: orange;'>WARNING</span>",
        "<span style='background-color: yellow;'>NOTICE</span>",
        "<span style='background-color: greenyellow;'>INFO</span>",
        "<span style='background-color: lightgray;'>DEBUG</span>"
    ];

    /**
     * Stores all log entries for the current run
     * @var array
     */
    static private $log = array();

    /**
     * Did we already display the stored errors ?
     * @var bool
     */
    static private $displayed = false;

    /**
     * Storage of the Main object
     * @var Main
     */
    static private $main;

    /**
     * Storage of the Model object
     * @var Log
     */
    static private $logModel;

    /**
     * Is the logger active ?
     * @var bool
     */
    static private $activated;

    /*
     * Handlers and stuff
     */
    /**
     * Initializes the library and registers the different handlers
     *
     * @param Main $main Main object
     * @param bool $active Do we activate the library ?
     * @param int $display (optional) Limit level for displaying errors (min CRIT)
     * @param int $db (optional) Limit level for storing errors in DB (min WARN)
     * @param int $fatal (optional) Limit level for stopping script (min ERROR)
     */
    static public function registerHandlers(Main $main, $active = true, $display = self::LEVEL_CRIT, $db = self::LEVEL_WARN, $fatal = self::LEVEL_ERROR) {
        self::$activated = ($active == true); // Force boolean storage of "is the logger active ?"

        // Store $main and call model
        self::$main = $main;
        self::$logModel = $main->useModel("log");

        // If active set Xdebug, else disable it
        if ($active) {
            ini_set('ignore_user_abort', 'off');
            ini_set('xdebug.var_display_max_children', '-1');
            ini_set('xdebug.var_display_max_data', '-1');
            ini_set('xdebug.var_display_max_depth', '-1');
            ini_set('display_errors', 1);

            restore_error_handler();
            set_error_handler("sparkleLogger::errorHandler");
            register_shutdown_function( "sparkleLogger::shutdownHandler" );
            set_exception_handler("sparkleLogger::exceptionHandler");

        } else {
            xdebug_disable();
        }

        // Display and log_to_db limits
        if ($display > self::$DISPLAY_LEVEL) { self::$DISPLAY_LEVEL = $display; if (self::LOGGER_DEBUG) echo "DISPLAYING from ".self::$levels[$display]."<br>"; }
        if ($db > self::$LOG_DB_LEVEL) { self::$LOG_DB_LEVEL = $db; if (self::LOGGER_DEBUG) echo "DBing from ".self::$levels[$db]."<br>"; }
        if ($fatal > self::$FATAL_LEVEL) { self::$FATAL_LEVEL = $db; if (self::LOGGER_DEBUG) echo "Halting from ".self::$levels[$fatal]."<br>"; }
    }

    /**
     *  Error handler for PHP-generated errors (error code 0)
     *
     * @param int $php_errno Internal PHP error code (Notice, Deprecated, ...)
     * @param string $errstr Error message
     * @param string $errfile File where the error occurred
     * @param int $errline Line where the error occurred
     * @param mixed[] $errcontext Context of the error ($_*, defined vars)
     */
    static public function errorHandler($php_errno, $errstr, $errfile, $errline, $errcontext) {
        // Give to each PHP error a severity
        $internalError = false;
        switch ($php_errno) {
            case E_PARSE:
            case E_COMPILE_ERROR:
                $severity = self::LEVEL_CRIT;
                $title = "PHP Parse error";
                break;

            case E_ERROR:
            case E_USER_ERROR:
            case E_CORE_ERROR:
            case E_RECOVERABLE_ERROR:
                $severity = self::LEVEL_ERROR;
                $title = "PHP Fatal error";
                break;


            case E_WARNING:
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                $severity = self::LEVEL_WARN;
                $title = "PHP Warning";
                break;

            case E_NOTICE:
            case E_USER_NOTICE:
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $severity = self::LEVEL_NOTICE;
                if ($php_errno == E_NOTICE || $php_errno == E_USER_NOTICE) $title = "PHP Notice";
                elseif ($php_errno == E_STRICT) $title = "PHP Strict";
                else $title = "PHP Deprecated";
                break;

            default:
                $internalError = true;
                $severity = self::LEVEL_ERROR;
                $title = "Unknown PHP Error";
                break;
        }

        self::log(0, $title, $errstr, $errfile, $errline, $severity, debug_backtrace(), $errcontext);
        if ($internalError) self::error(1010, "Unknown error code", "PHP raised an unknown error code ".$php_errno);
    }

    /**
     * Uncaught exceptions handler
     *
     * @param Exception $e The exception
     */
    static public function exceptionHandler(Exception $e) {
        self::log($e->getCode(), "Uncaught exception ".$e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), self::LEVEL_CRIT, $e->getTrace(), self::buildHTTPContext());
    }

    /**
     * PHP shutdown handler
     * Uses error_get_last() to retrieve the error
     *
     * @param void
     */
    static public function shutdownHandler() {
        $error = error_get_last();
        if ($error == null)
        {
            // Normal shutdown, we need to print the errors if this has not been done before
            if (!self::$displayed) self::displayErrors();
        }
        else
        {
            // Error shutdown, errors will be printed by self::log
            self::errorHandler($error['type'], $error['message'], $error['file'], $error['line'], self::buildHTTPContext());
        }
    }

    /*
     * Tools
     */
    /**
     * Builds an array containing $_{GET, POST, FILES, COOKIE, SERVER, SESSION}
     *
     * @param void
     * @return array
     */
    static private function buildHTTPContext() {
        $a = array(
          '_GET' => $_GET,
          '_POST' => $_POST,
          '_COOKIE' => $_COOKIE,
          '_FILES' => $_FILES,
          '_ENV' => $_ENV,
          '_REQUEST' => $_REQUEST,
          '_SERVER' => $_SERVER
        );
        if (isset($_SESSION)) $a["_SESSION"] = $_SESSION;

        return $a;
    }

    /**
     * Returns a path relative to the website ROOT
     *
     * @param string $to Original (absolute) path
     * @return string Relative path
     */
    static private function relativePath($to) {
        if (!strlen($to)) return $to;

        $from = ROOT;
        $ps = DIRECTORY_SEPARATOR;
        $arFrom = explode($ps, rtrim($from, $ps));
        $arTo = explode($ps, rtrim($to, $ps));
        while(count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0]))
        {
            array_shift($arFrom);
            array_shift($arTo);
        }
        return str_pad("", count($arFrom) * 3, '..'.$ps).implode($ps, $arTo);
    }

    /**
     * Makes all path relative to ROOT inside a callstack
     *
     * @param $stack
     * @return void
     */
    static private function sanitizePaths(&$stack) {
        foreach ($stack as $key => $call) {
            if (isset($stack[$key]['file']))
                $stack[$key]['file'] = self::relativePath($stack[$key]['file']);
        }
    }

    /*
     * Logging functions
     */
    /**
     * Logs an error coming either from a user-called function or from a handler
     *
     * @param int $errno Error code
     * @param string $errtitle Error title
     * @param string $errstr Error message
     * @param string|null $errfile File where the error occurred (can be automatically detected using call stack by setting $detectFileAndLine to true)
     * @param int|null $errline Line where the error occurred (can be automatically detected using call stack by setting $detectFileAndLine to true)
     * @param int $errlevel Error level
     * @param mixed[] $errstack Call stack
     * @param mixed[] $errcontext Call context ($_* and defined vars)
     * @param bool $detectFileAndLine If true, the algorithm will try to autodetect $errfile and $errline
     */
    static private function log($errno, $errtitle, $errstr, $errfile, $errline, $errlevel, $errstack, $errcontext, $detectFileAndLine = false) {
        // We have to be activated
        if (!self::$activated) return;

        // For when the log is triggered manually and not by any error/uncaught exception
        if ($detectFileAndLine) {
            // Detecting last call made outside the library
            $lastExternal = array();
            foreach ($errstack as $key => $call) {
                if (!isset($call['class']) || $call['class'] != __CLASS__) {
                    $lastExternal = $errstack[$key - 1];
                    break;
                }
            }
            if (!count($lastExternal) || !isset($call['line']) || !isset($call['file'])) {
                // Case where no external was made
                $errfile = "undefined";
                $errline = 0;
            } else {
                // We have a file/number
                $errline = $lastExternal['line'];
                $errfile = $lastExternal['file'];
            }
        }

        // Remove long paths from arrays
        $errfile = self::relativePath($errfile);
        self::sanitizePaths($errstack);

        // Store error in local var
        $entry = array(
            'number' => $errno,
            'title' => $errtitle,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'level' => $errlevel,
            'stack' => $errstack,
            'context' => $errcontext
        );
        self::$log[] = $entry;

        if (self::LOGGER_DEBUG) echo "Logging ".sparkleLogger::$levels[$errlevel]."<br>";

        // Log error if necessary
        if ($errlevel <= self::$LOG_DB_LEVEL) {
            if (self::LOGGER_DEBUG) echo "DBing ".sparkleLogger::$levels[$errlevel]."<br>";
            self::$logModel->insert($entry);
        }

        if ($errlevel <= self::$FATAL_LEVEL && !self::$displayed) // Interrupt script on ERROR or CRIT and calls display
        {
            if (self::LOGGER_DEBUG) echo "INTERRUPTING<br><br>";
            self::$displayed = true;
            self::displayErrors();
            die("Exiting on error");
        } elseif ($errlevel <= self::$FATAL_LEVEL) {
            if (self::LOGGER_DEBUG) echo "INTERRUPTING<br><br>";
            die("Exiting on error");
        }
    }

    /**
     * User-called : Logs an error with level: CRITICAL
     *
     * @param int $errno Error code
     * @param string $errtitle Error title
     * @param string $errstr Error message
     * @param mixed[] $errcontext Call context ($_* and defined vars)
     */
    static public function crit($errno, $errtitle, $errstr, $errcontext = array()) {
        self::log($errno, $errtitle, $errstr, null, null, self::LEVEL_CRIT, debug_backtrace(), $errcontext, true);
    }

    /**
     * User-called : Logs an error with level: ERROR
     *
     * @param int $errno Error code
     * @param string $errtitle Error title
     * @param string $errstr Error message
     * @param mixed[] $errcontext Call context ($_* and defined vars)
     */
    static public function error($errno, $errtitle, $errstr, $errcontext = array()) {
        self::log($errno, $errtitle, $errstr, null, null, self::LEVEL_ERROR, debug_backtrace(), $errcontext, true);
    }

    /**
     * User-called : Logs an error with level: WARNING
     *
     * @param int $errno Error code
     * @param string $errtitle Error title
     * @param string $errstr Error message
     * @param mixed[] $errcontext Call context ($_* and defined vars)
     */
    static public function warn($errno, $errtitle, $errstr, $errcontext = array()) {
        self::log($errno, $errtitle, $errstr, null, null, self::LEVEL_WARN, debug_backtrace(), $errcontext, true);
    }

    /**
     * User-called : Logs an error with level: NOTICE
     *
     * @param int $errno Error code
     * @param string $errtitle Error title
     * @param string $errstr Error message
     * @param mixed[] $errcontext Call context ($_* and defined vars)
     */
    static public function notice($errno, $errtitle, $errstr, $errcontext = array()) {
        self::log($errno, $errtitle, $errstr, null, null, self::LEVEL_NOTICE, debug_backtrace(), $errcontext, true);
    }

    /**
     * User-called : Logs an error with level: INFO
     *
     * @param int $errno Error code
     * @param string $errtitle Error title
     * @param string $errstr Error message
     * @param mixed[] $errcontext Call context ($_* and defined vars)
     */
    static public function info($errno, $errtitle, $errstr, $errcontext = array()) {
        self::log($errno, $errtitle, $errstr, null, null, self::LEVEL_INFO, debug_backtrace(), $errcontext, true);
    }

    /**
     * User-called : Logs an error with level: DEBUG
     *
     * @param int $errno Error code
     * @param string $errtitle Error title
     * @param string $errstr Error message
     * @param mixed[] $errcontext Call context ($_* and defined vars)
     */
    static public function debug($errno, $errtitle, $errstr, $errcontext = array()) {
        self::log($errno, $errtitle, $errstr, null, null, self::LEVEL_DEBUG, debug_backtrace(), $errcontext, true);
    }

    /*
     * Display
     */
    /**
     * Displays all errors from the log (with filtering on level)
     *
     * @param void
     * @return void
     */
    static private function displayErrors() {
        // Javascript for context
        echo '<script>
            function showHide(id) {
                if (document.getElementById("vd_" + id).style.display == "block")
                {
                    document.getElementById("vd_" + id).style.display = "none";
                    document.getElementById("b_" + id).innerHTML = "&#9654; Context";

                }
                else
                {
                    document.getElementById("vd_" + id).style.display = "block";
                    document.getElementById("b_" + id).innerHTML = "&#9660; Context";
                }
            }
        </script>';

        // Display errors if level is low enough
        foreach (self::$log as $entry) {
            if ($entry['level'] <= self::$DISPLAY_LEVEL) {
                if (self::LOGGER_DEBUG) echo "Displaying ".self::$levels[$entry['level']]."<br>";
                self:: displayError($entry);
            }
        }
    }

    /**
     * Display a specific log entry, with style !
     *
     * @param mixed[] $entry
     * @return void
     */
    static private function displayError($entry) {
        // Header
        echo "<h2>".$entry['title']." (".$entry['number']."):</h2>\n".
        "<p> <b>In file: </b>".$entry['file']."<br>\n".
        "<b>Severity: </b>".self::$levels[$entry['level']]."<br>\n".
        "<b>At line: </b>".$entry['line']."<br>\n".
        "<b>Message: </b>".$entry['message']."<br>\n".
        "<h4>Stack trace</h4>".
        "<table border=1> ".
            "<thead><td>Function</td><td>File:line</td><td>Arguments</td></thead>".
            "<tbody>";

        // Callstack
        foreach ($entry['stack'] as $call)
        {
            // Remove context ($_*) from stack trace
            foreach ($call['args'] as $key => $arg)
            {
                if (is_array($arg)) {
                    $call['args'][$key] = array_diff_key($arg, self::buildHTTPContext());
                }
            }

            // Sometimes we don't have a $line or $file (triggered by PHP itself) or $class or $type (when not object)
            if (!isset($call['line'])) $call['line'] = null;
            if (!isset($call['file'])) $call['file'] = null;
            if (!isset($call['class'])) $call['class'] = null;
            if (!isset($call['type'])) $call['type'] = null;

            // Display line
                echo
                "<tr>" .
                    "<td>" . $call['class'] . $call['type'] . $call['function'] . "</td>" .
                    "<td>" . (strlen($call['file'])?$call['file'] . ":" . $call['line'] : "PHPcore") . "</td>" .
                    "<td><pre>";
                var_dump($call['args']);
                echo
                "</pre></td>" .
                "</tr>";
        }
        echo
            "</tbody>" .
        "</table>";

        // Output retractable context
        $id = uniqid();
        echo '<br><div id="b_'.$id.'" onclick="showHide(\''.$id.'\');" style="font-weight: bolder;">&#9654; Context</div><div id="vd_'.$id.'" style="display: none;">';
        var_dump(self::buildHTTPContext());
        echo '</div><hr><hr>';
    }
}
