<?php

class Main {

  private $config;

  public function __construct(){
    // Define constants
    define("ROOT", dirname(__DIR__));

    // Load configuration
    require(ROOT . "config/config.inc.php");
  }

  public function useModel($string){
    
  }

  public function useView(){
    
  }

  /**
   * Get entr(y/ies) from the configuration
   * @param  Mixed $entry Entr(y/ies) needed
   * @return Mixed (Array, String)
   */
  public function getConfig($entry){
    if(is_array($entry)){
      $entries = array();
      
      foreach ($entry as $e)
        $entries[$e] = $entry[$e];

      return $entries;
    }

    return $this->config[$entry];
  }

  /**
   * Redirect the current user on the correct controller
   */
  private function route(){ 
    
  }

}