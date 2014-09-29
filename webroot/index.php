<?php
include ("../models/model.php");
include ("../models/session.model.php");

class Main {
    public function getConfig() {
        return 60;
    }
}
$main = new Main;
$session = new Session($main);

var_dump($session->getId());
var_dump($session->isAuth());
var_dump(session_id());

?>