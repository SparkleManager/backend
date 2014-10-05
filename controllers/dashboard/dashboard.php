<?php

class DashboardController extends Controller {
  public static $isAccessible = true;
  public static $needsLogin   = false;

  public function test(){
    echo "Bonjour, je suis DashboardController ! Comment-allez vous ?";
  }
}