<?php
/**
 * Created by PhpStorm.
 * User: Shujaa
 * Date: 29/09/2014
 * Time: 15:05
 */

class User extends Model {


    public function existsInDb() {
        // Checks that the user exists in DB
        return true;
    }

    public function getId() {
        // Returns user id
        return 0;
    }
} 