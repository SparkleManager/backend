<?php

final class Main {

  private $config;
  private $loadedModels = array();

  private $controller = null;
  private $action = null;

  /**
   * Constructor of Main
   */
  public function __construct(){
    define("ROOT", dirname(__DIR__));

    // Define constants
    $this->defineConstants(array(
      "CONTROLLERS" => ROOT . "/controllers",
      "MODELS"      => ROOT . "/models",
      "VIEWS"       => ROOT . "/views",
      "WEBROOT"     => ROOT . "/views",
      "INCLUDES"    => ROOT . "/includes"
    ));

    // Load configuration
    $this->loadConfig();

    // Load Model
    $this->initModel();

    // Initialisation of sparkleLogger
    $this->initLogger();

    // Load Model
    $this->initView();

    // Routing
    $this->route();
  }

  /**
   * Redirect the current user on the correct controller
   */
   private function route(){ 
    if(!isset($_GET["controller"]) || !isset($_GET["action"])){
      $this->actionNotFound();
    }

    // Check if all characters are alphabetic
    if(!ctype_alpha($_GET["controller"]) || !ctype_alpha($_GET["action"])) {
      $this->actionNotFound();
    }

    $controller = $_GET['controller'];
    $action = $_GET["action"];

    /**
     * Check if controller exist
     */
    if(
      !file_exists(CONTROLLERS . "/" . $controller . "/" . $controller . ".php")
    ){
      $this->controllerNotFound();
    }

    // Load current controller
    $this->initController();
    require(CONTROLLERS . "/" . $controller . "/" . $controller . ".php");
    
    $controller = ucfirst($controller) . "Controller";
    $controllerAttrs = get_class_vars($controller);

    // Check if it's a public controller
    if(!$controllerAttrs["isAccessible"]){
      $this->controllerNotFound();
    }

    // Check if the user needs to be authenticated to access this controller
    if($controllerAttrs["needsLogin"]){
      $session = $this->useModel("session");
      if(!$session->isAuth()){
        $this->notAuthenticated();
      }
    }

    // Check if the action exist
    if(!method_exists($controller, $action)){
      $this->actionNotFound();
    }

    // Create the controller
    $this->controller = new $controller();
    $this->action = $action;

    $this->controller->$action();
  }


  /*==========  Public methods  ==========*/
    /**
     * Return an instance of a model
     * @param  [String] $model Model name
     * @return [Model]
     */
    public function useModel($model){
      if(isset($this->loadedModels[$model])){
        return $this->loadedModels[$model];
      }

      require(MODELS . "/" . $model . ".model.php");

      $className = ucfirst($model);
      $model = new $className($this);
      $this->loadedModels[$className] = $model;

      return $model;
    }

    /**
     * Return an instance of a view
     * @param  [String] $view View name
     * @return [View]
     */
    public function useView($view){
      require(VIEWS . "/" . $view . ".view.php");

      $className = ucfirst($view);
      return new $className($this);
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

    /**
     * Define constants
     * @param  [Array] $constants List of constants to define
     */
    public function defineConstants($constants){
      foreach ($constants as $name => $value) {
        define($name, $value);
      }
    }

  /*==========  Configuration methods  ==========*/
    /**
     * Load configuration
     */
    private function loadConfig(){
      require(ROOT . "/config/config.php");
    }

    /**
     * Initialisation of sparkleLogger
     */
    private function initLogger(){
      require(INCLUDES . "/sparkleLogger.php");

      $debugLevel = sparkleLogger::LEVEL_CRIT;
      if(!empty($this->config["debugLevel"]))
        $debugLevel = $this->config["debugLevel"];

      sparkleLogger::registerHandlers(
        $this,
        $this->config["debug"],
        $debugLevel
      );
    }

    /**
     * Load abstract class Controller
     */
    private function initController(){
      require(ROOT . "/controllers/controller.php");
    }

    /**
     * Load abstract class Model
     */
    private function initModel(){
      require(ROOT . "/models/model.php");
    }

    /**
     * Load abstract class View
     */
    private function initView(){
      /**
       * TODO:
       * - Loading abstract class View
      **/
    }

  /*==========  Error methods  ==========*/
    private function controllerNotFound(){
      http_response_code(404);
      echo json_encode(array(
        "status" => false,
        "message" => "Controller not found"
      ));
      exit;
    }

    private function actionNotFound(){
      http_response_code(404);
      echo json_encode(array(
        "status" => false,
        "message" => "Action not found"
      ));
      exit;
    }

    private function notAuthenticated(){
      http_response_code(401);
      echo json_encode(array(
        "status" => false,
        "message" => "Authentification needed"
      ));
      exit;
    }
}

$Main = new Main();
