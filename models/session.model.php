<?php 
/**
 * Created by PhpStorm.
 * User: Shujaa
 * Date: 27/09/2014
 * Time: 10:29
 */
 
class Session Extends Model {
    private $currSessId;

    public function __construct() {
        // Get PHPSESSID
        session_start();
        if (isset($_GET['session']) && preg_match("#^[0-9a-zA-Z]{16,}#")) {
            $this->currSessId = $_GET['session'];
        } else {
            $this->currSessId = session_id();
        }

        // Call the model class with table name "session"
        parent("session");
    }

    public function getId() {
        return $this->currSessId;
    }

    public function isAuth() {
        try {
            $dbSession = parent::get(array("id" => $this->currSessId));
        } catch(Exception $e) {
            // TODO Filtrer selon code de retour
        }

        return ($dbSession["isAuth"] == true);
    }

    public function setAuth($auth) {
        try {
            // Inserts auth status in database, if exists, insert will only update
            $dbSession = parent::insert(array("id" => $this->currSessId, "isAuth" => true));
        } catch(Exception $e) {
            // TODO Filtrer selon code de retour
        }
    }
} 
