<?php
/**
 * Created by PhpStorm.
 * User: Shujaa
 * Date: 01/10/2014
 * Time: 11:22
 */

session_start();

require("api.php");
$main = new Main;

include("../includes/sparkleLogger.php");
sparkleLogger::registerHandlers($main, true, sparkleLogger::LEVEL_DEBUG, sparkleLogger::LEVEL_WARN, sparkleLogger::LEVEL_WARN);


//$a = new A;
function critical()
{
//    $a = $b;
    sparkleLogger::debug(69, "Test debug", "A testing error for fun", get_defined_vars());
    sparkleLogger::info(0, "Test info", "A testing error for fun", get_defined_vars());
    sparkleLogger::notice(0, "Test notice", "A testing error for fun", get_defined_vars());
    sparkleLogger::warn(0, "Test warning", "A testing error for fun", get_defined_vars());
    sparkleLogger::error(0, "Fatal test error", "A testing error for fun", get_defined_vars());
    sparkleLogger::crit(0, "Fatal test error", "A testing error for fun", get_defined_vars());
    trigger_error("Value at position $pos is not a number, using 0 (zero)", E_USER_ERROR);
}
critical();

function MonExcaption()
{
    $c = 0;
    $a = $b;
    throw new Exception("MEHssage", 0);
}

MonExcaption();

