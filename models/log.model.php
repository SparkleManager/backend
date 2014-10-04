<?php

class Log extends Model {

    /**
     * Object of the Main class
     * @var Main
     */
    private $main;

    /**
     * Class constructor
     *
     * nothing to say
     *
     * @param Main $main
     * @return \Log
     */
    public function __construct(Main $main)
    {
        // Store pointer to main class
        $this->main = $main;

        // Call the model class with table name "session"
        parent::__construct("log", $main);
    }

    /**
     * Inserts a log entry into the DB
     *
     * @param Array $entry
     * @return void
     */
    public function insert($entry)
    {
        parent::insert($entry);
        if (sparkleLogger::LOGGER_DEBUG) echo "Inserting ".sparkleLogger::$levels[$entry['level']]."<br>";
    }
}