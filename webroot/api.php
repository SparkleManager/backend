<?php

class Main {

  private $config;
  private $loadedModels = array();

  private $currentController = null;
  private $currentAction = null;

  /**
   * Constructor of Main
   */
  public function __construct(){
    define("ROOT", dirname(__DIR__));

    // Define constants
    $this->defineConstants(array(
      "CONTROLLERS" => ROOT . "/controllers",
      "MODELS" => ROOT . "/models",
      "VIEWS" => ROOT . "/views",
      "WEBROOT" => ROOT . "/views",
      "INCLUDES" => ROOT . "/includes"
    ));

    // Load configuration
    $this->loadConfig();

    // Load Logger
    require(INCLUDES . "/sparkleLogger.php");

    // Set Debug Mode
    Logger::setDebug($this->config["debug"]);

    // Loading Model
    require(ROOT . "/models/model.php");

    try {
      $this->route();
    } catch (Exception $e) {
      $this->catchExceptionAndDie($e);
    }
  }


  /**
   * Redirect the current user on the correct controller
   */
  private function route(){ 
    if(!isset($_GET["controller"]) || !isset($_GET["action"])){
      throw new Exception("invalid-request");
    }

    // Check if all characters are alphabetic
    if(
      !ctype_alpha($_GET["controller"]) ||
      !ctype_alpha($_GET["action"])
    ) {
      throw new Exception("unauthorized-symbols");
    }

    $this->controller = $_GET['controller'];
    $this->action = $_GET['action'];
  }


  /*==========  Public methods  ==========*/

    public function useModel($model){
      
    }


  /*==========  Common methods  ==========*/

    /**
     * Set HTTP Code
     * @param [Number] $code HTTP Code
     */
    private function setHTTPCode($code){
      http_response_code($code);
    }


  /*==========  Configuration methods  ==========*/

    /**
     * Load configuration
     */
    private function loadConfig(){
      require(ROOT . "/config/config.php");
    }

    /**
     * Define constants
     * @param  [Array] $constants List of constants to define
     */
    private function defineConstants($constants){
      foreach ($constants as $name => $value) {
        define($name, $value);
      }
    }

    /**
     * Get entr(y/ies) from the configuration
     * @param  Mixed $entry Entr(y/ies) needed
     * @return Mixed (Array, String)
     */
    public function getConfig($entry = null){
      if($entry == null){
        return $this->config;
      }

      if(is_array($entry)){
        $entries = array();
        
        foreach ($entry as $e)
          $entries[$e] = $this->config[$e];

        return $entries;
      }

      return $this->config[$entry];
    }


  /*==========  Exception handler methods  ==========*/

    /**
     * Log Exception (This function does not return)
     * @param Exception $e
     */
    private function catchExceptionAndDie(Exception $e){
      $this->setHTTPCode(500);
      Logger::error($e->getCode(), $e->getMessage());
      die();
    }

}

$Main = new Main();