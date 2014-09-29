<?php
error_reporting(0);
function myErrorHandler($errno, $errstr, $errfile, $errline)
{
//    if (!(error_reporting() & $errno)) {
//        // This error code is not included in error_reporting
//        return;
//    }

    switch ($errno) {
        case E_USER_ERROR:
            echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
            echo "  Fatal error on line $errline in file $errfile";
            echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
            echo "Aborting...<br />\n";
            exit(1);
            break;

        case E_USER_WARNING:
            echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
            break;

        case E_USER_NOTICE:
            echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
            break;

        default:
            echo "Unknown error type: [$errno] $errstr<br />\n";
            break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}

function fatal_handler() {
    $error = error_get_last();
    $errno   = $error["type"];
    $errfile = $error["file"];
    $errline = $error["line"];
    $errstr  = $error["message"];

    myErrorHandler($errno, $errstr, $errfile, $errline);
}

$old_error_handler = set_error_handler("myErrorHandler");
register_shutdown_function( "fatal_handler" );


trigger_error("Value at position $pos is not a number, using 0 (zero)", E_USER_ERROR);

$a = new A;