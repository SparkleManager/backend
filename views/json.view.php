<?php

class JsonView extends View {

  public function render(){
    echo json_encode($this->viewData);
  }

}