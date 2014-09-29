<?php
/**
 * Created by PhpStorm.
 * User: Shujaa
 * Date: 27/09/2014
 * Time: 10:29
 */

class Session Extends Model
{
    /**
     * Current session ID
     * @var null|string
     */
    private $currSessId = null;
    /**
     * Object of the Main class
     * @var Main
     */
    private $main;

    /**
     * Class constructor
     *
     * Automatically fetches the current Session ID and stores the Main object
     *
     * @param Main $main
     * @throws Exception
     * @return \Session
     */
    public function __construct(Main $main)
    {
        // Store pointer to main class
        $this->main = $main;

        /* Get PHPSESSID,
            - first case from the $_GET['sessionId'] if [a-zA-Z0-9]{26}
            - otherwise use php's
        */
        if (isset($_GET['sessionId']))
        {
            if (preg_match("#^[0-9a-zA-Z]{26}$#", $_GET['sessionId']))
            {
                $this->currSessId = $_GET['sessionId'];
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
        parent::__construct("session", $main);
    }

    /**
     * Cleanup expired sessions in Database
     *
     * A session is expired after a CONFIG number of second, which removes it from the DB
     *
     * @param void
     * @return void
     * @throws Exception
     */
    private function cleanup()
    {
        // Clean database by removing entries older than a predefined time range in seconds
        $delay = $this->main->getConfig("sessionTTL");
        parent::cleanSessions($delay);
    }

    /**
     * Returns current session ID
     *
     * If a session ID is defined, returns it. If not throws exception (which should not happen if class is properly instantiated.
     *
     * @param void
     * @return null|string
     * @throws Exception
     */
    public function getId()
    {
        if ($this->currSessId == null) throw new Exception("Session ID undefined", 1911);
        else return $this->currSessId;
    }

    /**
     * Get current session's authentication status
     *
     * Returns TRUE if the current session is authenticated as some user, FALSE if not.
     *
     * @param void
     * @return bool
     * @throws Exception
     */
    public function isAuth()
    {
        // Cleanup DB
        $this->cleanup();

        // Get session entry in DB
        if ($this->currSessId == null) throw new Exception("Session ID undefined", 1911);

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

    /**
     * Set current session's authentication status using a user id
     *
     * To authenticate a user, check that the user exists and link its id to the current session
     *
     * @param User $user User to authenticate
     * @return void
     * @throws Exception
     */
    public function setAuth(User $user)
    {
        // Update DB or insert if not exist

        // Check that the user exists
        if ($user->existsInDb())
        {
            // Inserts auth status in database, if exists, insert will only update
            parent::insertOrUpdate(array("id" => $this->currSessId, "userId" => $user->getId()));
        }
        else
        {
            throw new Exception("User does not exist", 1912);
        }
    }
} 
