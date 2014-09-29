<?php
/**
 * Created by PhpStorm.
 * User: Shujaa
 * Date: 27/09/2014
 * Time: 10:29
 */

class Session Extends Model
{
    private $currSessId = null;
    private $main;

    public function __construct(Main $main)
    {
        // Store pointer to main class
        $this->main = $main;

        /* Get PHPSESSID,
            - first case from the $_GET['sessionId'] if [a-zA-Z0-9]{26}
            - otherwise use php's
        */
        // TODO Is it OK to rewrite the session ID like that ?
        if (isset($_GET['sessionId']))
        {
            if (preg_match("#^[0-9a-zA-Z]{26}$#", $_GET['sessionId']))
            {
                $this->currSessId = $_GET['sessionId'];
                session_id($this->currSessId);
                session_start();
            }
            else
            {
                throw new Exception("Session ID invalid", 1101);
            }
        }
        else
        {
            session_start();
            $this->currSessId = session_id();
        }

        // Call the model class with table name "session"
        parent::__construct("session");
    }

    private function cleanup()
    {
        // Clean database by removing entries older than a predefined time range in seconds
        $delay = $this->main->getConfig("sessionTTL");
        parent::query("DELETE FROM sessions WHERE timestamp < (UNIX_TIMESTAMP() - ${delay})");
    }

    public function getId()
    {
        if ($this->currSessId == null) throw new Exception("Session ID undefined", 1911);
        else return $this->currSessId;
    }

    public function isAuth()
    {
        // Cleanup DB
        $this->cleanup();

        // Get session entry in DB
        if ($this->currSessId == null) throw new Exception("Session ID undefined", 1911);
        try
        {
            // If session exists in DB, check for authentication
            if ($dbSession = parent::exists($this->currSessId))
            {
                // Session is authenticated iff it has a non-nul userId associated
                $dbSession = parent::get(array('session.id' => $this->currSessId));
                return (strlen($dbSession['userId']) > 0);
            }
            else
            {
                return false; // No DB entry
            }
        }
        catch(Exception $e)
        {
            // TODO Do we actually catch something relevant here ?
            //throw $e;
            return false;
        }
    }

    public function setAuth($auth)
    {
        // Force $auth to be a boolean
        $auth = ($auth == true);

        // Update DB or insert if not exist
        try
        {
            // Inserts auth status in database, if exists, insert will only update
            parent::insertOrUpdate(array("id" => $this->currSessId, "isAuth" => $auth));
        }
        catch(Exception $e)
        {
            // TODO Do we catch something here ?
            throw $e;
        }
    }
} 
