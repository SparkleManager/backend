<?php

abstract class View {
  protected $viewData = array();

  public function __get($name) {
    if(isset($this->viewData[$name])){
      return $this->viewData[$name];
    } else {
      return null;
    }
  }

  public function __set($name, $value) {
    $this->viewData[$name] = $value;
  }
}